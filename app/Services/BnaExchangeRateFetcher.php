<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class BnaExchangeRateFetcher
{
    /**
     * Fetch the USD cash ("billetes") buy/sell rates published by Banco Nación.
     *
     * @return array{compra: float, venta: float}
     */
    public function fetch(): array
    {
        $response = Http::timeout(10)
            ->get('https://www.bna.com.ar/Cotizador/MonedasHistorico', ['id' => 'billetes']);

        if (! $response->successful()) {
            throw new RuntimeException('No se pudo consultar la cotización del Banco Nación.');
        }

        if (! preg_match(
            '/Dolar U\.S\.A<\/td>\s*<td class="dest">([\d.]+)<\/td>\s*<td class="dest">([\d.]+)<\/td>/',
            $response->body(),
            $matches,
        )) {
            throw new RuntimeException('No se pudo interpretar la respuesta del Banco Nación.');
        }

        return [
            'compra' => (float) $matches[1],
            'venta' => (float) $matches[2],
        ];
    }
}
