<?php

use App\Services\BnaExchangeRateFetcher;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Build a cotizador table like the one Banco Nación publishes.
 */
function cotizadorTable(string $id, string $compra, string $venta): string
{
    return <<<HTML
    <div id="{$id}" class="tab-pane">
        <table class="table cotizacion">
            <thead>
                <tr><td>26/8/2026</td><td>Compra</td><td>Venta</td></tr>
            </thead>
            <tbody>
                <tr>
                    <td class="tit">Dolar U.S.A</td>
                    <td class="dest">{$compra}</td>
                    <td class="dest">{$venta}</td>
                </tr>
                <tr>
                    <td class="tit">Euro</td>
                    <td class="dest">1710,00</td>
                    <td class="dest">1810,00</td>
                </tr>
            </tbody>
        </table>
    </div>
    HTML;
}

function cotizadorPage(string ...$tables): string
{
    $content = implode("\n", $tables);

    return <<<HTML
    <html><body>
        <ul class="nav">
            <li><a href="/Personas?id=billetes">Cotización Billetes</a></li>
            <li><a href="/Personas?id=divisas">Cotización Divisas</a></li>
        </ul>
        <div class="tab-content">{$content}</div>
    </body></html>
    HTML;
}

it('reads the retail billetes rate from the live cotizador', function (): void {
    Http::fake([
        '*' => Http::response(cotizadorPage(
            cotizadorTable('billetes', '1480,00', '1530,00'),
            cotizadorTable('divisas', '1509,50', '1511,50'),
        )),
    ]);

    expect(app(BnaExchangeRateFetcher::class)->fetch())
        ->toBe(['compra' => 1480.0, 'venta' => 1530.0]);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://www.bna.com.ar/Personas');
});

it('ignores the wholesale divisas table even when it is rendered first', function (): void {
    $rates = (new BnaExchangeRateFetcher)->parse(cotizadorPage(
        cotizadorTable('divisas', '1509,50', '1511,50'),
        cotizadorTable('billetes', '1480,00', '1530,00'),
    ));

    expect($rates)->toBe(['compra' => 1480.0, 'venta' => 1530.0]);
});

it('falls back to the highest selling rate when no billetes tab is present', function (): void {
    $rates = (new BnaExchangeRateFetcher)->parse(cotizadorPage(
        cotizadorTable('divisas', '1509,50', '1511,50'),
        cotizadorTable('otro', '1480,00', '1530,00'),
    ));

    expect($rates)->toBe(['compra' => 1480.0, 'venta' => 1530.0]);
});

it('parses the published amount formats', function (string $compra, string $venta, float $expected): void {
    $rates = (new BnaExchangeRateFetcher)->parse(cotizadorPage(
        cotizadorTable('billetes', $compra, $venta),
    ));

    expect($rates['venta'])->toBe($expected);
})->with([
    'comma decimals' => ['1480,00', '1530,00', 1530.0],
    'dotted thousands' => ['1.480,0000', '1.530,5000', 1530.5],
    'plain decimals' => ['1480.0000', '1530.5000', 1530.5],
    'thousands only' => ['1.480', '1.530', 1530.0],
]);

it('fails when Banco Nación does not answer', function (): void {
    Http::fake(['*' => Http::response('', 503)]);

    app(BnaExchangeRateFetcher::class)->fetch();
})->throws(RuntimeException::class, 'No se pudo consultar la cotización del Banco Nación.');

it('fails when the dollar row is missing', function (): void {
    (new BnaExchangeRateFetcher)->parse('<html><body><p>Mantenimiento</p></body></html>');
})->throws(RuntimeException::class, 'No se pudo interpretar la respuesta del Banco Nación.');

it('rejects a quote whose selling rate is below the buying rate', function (): void {
    (new BnaExchangeRateFetcher)->parse(cotizadorPage(
        cotizadorTable('billetes', '1530,00', '1480,00'),
    ));
})->throws(RuntimeException::class, 'La cotización recibida del Banco Nación no es válida.');
