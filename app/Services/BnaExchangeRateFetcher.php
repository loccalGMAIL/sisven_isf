<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BnaExchangeRateFetcher
{
    private const ENDPOINT = 'https://www.bna.com.ar/Personas';

    /**
     * Fetch the USD cash ("billetes") buy/sell rates published by Banco Nación.
     *
     * Reads the live cotizador on the public site, which is the same table a
     * customer sees at the branch, instead of the historical cotizador.
     *
     * @return array{compra: float, venta: float}
     */
    public function fetch(): array
    {
        $response = Http::timeout(10)->get(self::ENDPOINT);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo consultar la cotización del Banco Nación.');
        }

        return $this->parse($response->body());
    }

    /**
     * Pick the "billetes" USD rate out of the Banco Nación cotizador markup.
     *
     * The cotizador renders both the retail ("Cotización Billetes") and the
     * wholesale ("Cotización Divisas") tables, so the row has to be chosen
     * explicitly instead of taking whichever one comes first in the document.
     *
     * @return array{compra: float, venta: float}
     */
    public function parse(string $html): array
    {
        $rows = $this->dollarRows($html);

        if ($rows === []) {
            throw new RuntimeException('No se pudo interpretar la respuesta del Banco Nación.');
        }

        $row = $this->rowInBilletesSection($html, $rows) ?? $this->rowWithHighestSellingRate($rows);

        if ($row['compra'] <= 0 || $row['venta'] <= 0 || $row['venta'] < $row['compra']) {
            throw new RuntimeException('La cotización recibida del Banco Nación no es válida.');
        }

        return [
            'compra' => $row['compra'],
            'venta' => $row['venta'],
        ];
    }

    /**
     * Every "Dolar U.S.A" row found in the document, in document order.
     *
     * @return list<array{offset: int, compra: float, venta: float}>
     */
    private function dollarRows(string $html): array
    {
        $matched = preg_match_all(
            '/Dolar\s+U\.?\s*S\.?\s*A\.?\s*<\/td>\s*<td[^>]*>\s*([\d.,]+)\s*<\/td>\s*<td[^>]*>\s*([\d.,]+)\s*<\/td>/i',
            $html,
            $matches,
            PREG_OFFSET_CAPTURE,
        );

        if (! $matched) {
            return [];
        }

        $rows = [];

        foreach ($matches[0] as $index => $match) {
            $rows[] = [
                'offset' => $match[1],
                'compra' => $this->toFloat($matches[1][$index][0]),
                'venta' => $this->toFloat($matches[2][$index][0]),
            ];
        }

        return $rows;
    }

    /**
     * The first dollar row that belongs to the "billetes" tab, when the tab is
     * present in the markup.
     *
     * @param  list<array{offset: int, compra: float, venta: float}>  $rows
     * @return array{offset: int, compra: float, venta: float}|null
     */
    private function rowInBilletesSection(string $html, array $rows): ?array
    {
        if (! preg_match('/id\s*=\s*["\']billetes["\']/i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $sectionOffset = $matches[0][1];

        foreach ($rows as $row) {
            if ($row['offset'] > $sectionOffset) {
                return $row;
            }
        }

        return null;
    }

    /**
     * Fallback for markup without an identifiable "billetes" tab: the retail
     * rate is always the highest of the published USD selling rates.
     *
     * @param  non-empty-list<array{offset: int, compra: float, venta: float}>  $rows
     * @return array{offset: int, compra: float, venta: float}
     */
    private function rowWithHighestSellingRate(array $rows): array
    {
        usort($rows, fn (array $a, array $b): int => $b['venta'] <=> $a['venta']);

        return $rows[0];
    }

    /**
     * Convert a published amount to a float, accepting both the Argentine
     * format ("1.530,0000") and the plain format ("1530.0000").
     */
    private function toFloat(string $value): float
    {
        $value = trim($value);

        if (str_contains($value, ',')) {
            return (float) str_replace(',', '.', str_replace('.', '', $value));
        }

        if (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            return (float) str_replace('.', '', $value);
        }

        return (float) $value;
    }
}
