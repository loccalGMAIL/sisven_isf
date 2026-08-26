<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Informe de ventas</title>
    <style>
        @page {
            margin: 28px 32px;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #1f2937;
        }

        h1 {
            font-size: 18px;
            margin: 0 0 4px;
        }

        .subtitle {
            color: #6b7280;
            margin: 0 0 20px;
        }

        h2 {
            font-size: 13px;
            margin: 18px 0 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #d1d5db;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        th, td {
            padding: 5px 6px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        th {
            color: #6b7280;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }

        .text-right {
            text-align: right;
        }

        .totals-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 10px 14px;
            margin-bottom: 12px;
        }

        .totals-box .label {
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
        }

        .totals-box .value {
            font-size: 20px;
            font-weight: bold;
        }

        .empty {
            color: #6b7280;
            font-style: italic;
        }

        .footer-total td {
            font-weight: bold;
            border-top: 2px solid #1f2937;
            border-bottom: none;
        }

        .totals-row {
            width: 100%;
        }

        .totals-row .totals-box {
            display: inline-block;
            width: 48%;
            box-sizing: border-box;
            vertical-align: top;
        }

        .totals-row .totals-box + .totals-box {
            margin-left: 4%;
        }

        .usd-note {
            font-size: 10px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <h1>Informe de ventas</h1>
    <p class="subtitle">
        Período:
        {{ $summary['dateFrom'] ? \Illuminate\Support\Carbon::parse($summary['dateFrom'])->format('d/m/Y') : '—' }}
        al
        {{ $summary['dateTo'] ? \Illuminate\Support\Carbon::parse($summary['dateTo'])->format('d/m/Y') : '—' }}
        &nbsp;·&nbsp; Generado el {{ now(config('app.display_timezone'))->format('d/m/Y H:i') }}
    </p>

    <div class="totals-row">
        <div class="totals-box">
            <div class="label">Total del período ({{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('venta', $summary['count']) }})</div>
            <div class="value">{{ \Illuminate\Support\Number::currency($summary['total'], 'ARS', 'es_AR') }}</div>
        </div>
        <div class="totals-box">
            <div class="label">Dólares recibidos ({{ $summary['usdCount'] }} {{ \Illuminate\Support\Str::plural('venta', $summary['usdCount']) }})</div>
            <div class="value">US$ {{ number_format($summary['usdTotal'], 2) }}</div>
        </div>
    </div>

    <h2>Totales por medio de pago</h2>
    @if ($summary['byPaymentMethod']->isEmpty())
        <p class="empty">Sin ventas en el período seleccionado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Medio de pago</th>
                    <th>Ventas</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dólares recibidos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['byPaymentMethod'] as $row)
                    <tr>
                        <td>{{ $row['label'] }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td class="text-right">{{ \Illuminate\Support\Number::currency($row['total'], 'ARS', 'es_AR') }}</td>
                        <td class="text-right">{{ $row['usdTotal'] !== null ? 'US$ '.number_format($row['usdTotal'], 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>Totales por vendedor</h2>
    @if ($summary['byUser']->isEmpty())
        <p class="empty">Sin ventas en el período seleccionado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Vendedor</th>
                    <th>Ventas</th>
                    <th class="text-right">Total</th>
                    <th class="text-right">Dólares recibidos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['byUser'] as $userName => $row)
                    <tr>
                        <td>{{ $userName }}</td>
                        <td>{{ $row['count'] }}</td>
                        <td class="text-right">{{ \Illuminate\Support\Number::currency($row['total'], 'ARS', 'es_AR') }}</td>
                        <td class="text-right">{{ $row['usdTotal'] > 0 ? 'US$ '.number_format($row['usdTotal'], 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="footer-total">
                    <td colspan="2">Total general</td>
                    <td class="text-right">{{ \Illuminate\Support\Number::currency($summary['total'], 'ARS', 'es_AR') }}</td>
                    <td class="text-right">US$ {{ number_format($summary['usdTotal'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <h2>Productos vendidos</h2>
    @if ($summary['products']->isEmpty())
        <p class="empty">Sin ventas en el período seleccionado.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($summary['products'] as $productName => $row)
                    <tr>
                        <td>{{ $productName }}</td>
                        <td>{{ $row['quantity'] }}</td>
                        <td class="text-right">{{ \Illuminate\Support\Number::currency($row['subtotal'], 'ARS', 'es_AR') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
