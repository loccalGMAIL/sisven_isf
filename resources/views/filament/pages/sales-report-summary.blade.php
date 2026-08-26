<div class="fi-section-content-ctn space-y-6">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-filament::section>
            <x-slot name="heading">Total del período</x-slot>

            <p class="text-3xl font-bold text-gray-950 dark:text-white">
                {{ Illuminate\Support\Number::currency($summary['total'], 'ARS', 'es_AR') }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $summary['count'] }} {{ \Illuminate\Support\Str::plural('venta', $summary['count']) }}
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Dólares recibidos</x-slot>

            <p class="text-3xl font-bold text-gray-950 dark:text-white">
                US$ {{ number_format($summary['usdTotal'], 2) }}
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $summary['usdCount'] }} {{ \Illuminate\Support\Str::plural('venta', $summary['usdCount']) }} en billetes — para control de caja
            </p>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Totales por medio de pago</x-slot>

            @if ($summary['byPaymentMethod']->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">Sin ventas en el período seleccionado.</p>
            @else
                <ul class="space-y-2">
                    @foreach ($summary['byPaymentMethod'] as $row)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-gray-700 dark:text-gray-300">{{ $row['label'] }} ({{ $row['count'] }})</span>
                            <span class="text-right">
                                <span class="block font-medium text-gray-950 dark:text-white">{{ Illuminate\Support\Number::currency($row['total'], 'ARS', 'es_AR') }}</span>
                                @if ($row['usdTotal'] !== null)
                                    <span class="block text-xs text-gray-500 dark:text-gray-400">US$ {{ number_format($row['usdTotal'], 2) }}</span>
                                @endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">Totales por vendedor</x-slot>

        @if ($summary['byUser']->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Sin ventas en el período seleccionado.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Vendedor</th>
                        <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Ventas</th>
                        <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Dólares</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['byUser'] as $userName => $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">{{ $userName }}</td>
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">{{ $row['count'] }}</td>
                            <td class="px-2 py-2 text-right font-medium text-gray-950 dark:text-white">{{ Illuminate\Support\Number::currency($row['total'], 'ARS', 'es_AR') }}</td>
                            <td class="px-2 py-2 text-right text-gray-700 dark:text-gray-300">{{ $row['usdTotal'] > 0 ? 'US$ '.number_format($row['usdTotal'], 2) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Productos vendidos</x-slot>

        @if ($summary['products']->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">Sin ventas en el período seleccionado.</p>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Producto</th>
                        <th class="px-2 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Cantidad</th>
                        <th class="px-2 py-2 text-right font-medium text-gray-500 dark:text-gray-400">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($summary['products'] as $productName => $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">{{ $productName }}</td>
                            <td class="px-2 py-2 text-gray-700 dark:text-gray-300">{{ $row['quantity'] }}</td>
                            <td class="px-2 py-2 text-right font-medium text-gray-950 dark:text-white">{{ Illuminate\Support\Number::currency($row['subtotal'], 'ARS', 'es_AR') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-filament::section>
</div>
