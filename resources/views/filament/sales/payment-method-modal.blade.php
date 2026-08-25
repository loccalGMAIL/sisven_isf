@if ($paymentModalOpen)
    <div class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        <div
            class="fixed inset-0 bg-gray-950/50"
            wire:click="closePaymentModal"
        ></div>

        <div class="relative z-10 w-full max-w-lg rounded-xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-950 dark:text-white">
                    Elegí el método de pago
                </h2>

                <button
                    type="button"
                    wire:click="closePaymentModal"
                    class="rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-3">
                <button
                    type="button"
                    wire:click="chargeWith('pesos')"
                    wire:loading.attr="disabled"
                    wire:target="chargeWith"
                    class="flex w-full items-center justify-between rounded-lg border border-gray-200 p-4 text-left transition hover:border-primary-400 disabled:opacity-50 dark:border-gray-700"
                >
                    <span class="font-medium text-gray-950 dark:text-white">Pesos</span>
                    <span class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ \Illuminate\Support\Number::currency($total, 'ARS', 'es_AR') }}
                    </span>
                </button>

                <button
                    type="button"
                    wire:click="chargeWith('dolares')"
                    wire:loading.attr="disabled"
                    wire:target="chargeWith"
                    class="flex w-full items-center justify-between rounded-lg border border-gray-200 p-4 text-left transition hover:border-primary-400 disabled:opacity-50 dark:border-gray-700"
                >
                    <span class="font-medium text-gray-950 dark:text-white">
                        Dólares
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                            Cambio: {{ \Illuminate\Support\Number::currency($dollarRate, 'ARS', 'es_AR') }}
                        </span>
                    </span>
                    <span class="text-lg font-semibold text-gray-950 dark:text-white">
                        US$ {{ number_format($usdAmount, 2) }}
                    </span>
                </button>

                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="font-medium text-gray-950 dark:text-white">Transferencia</span>
                        <span class="text-lg font-semibold text-gray-950 dark:text-white">
                            {{ \Illuminate\Support\Number::currency($total, 'ARS', 'es_AR') }}
                        </span>
                    </div>

                    @if ($paymentReceipt)
                        <div class="mb-3 flex items-center gap-2 text-xs text-success-600 dark:text-success-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Comprobante adjuntado
                        </div>
                    @endif

                    <div wire:loading wire:target="paymentReceipt" class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                        Subiendo comprobante...
                    </div>

                    <div class="flex gap-2">
                        <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-gray-300 p-2 text-center text-sm text-gray-600 hover:border-primary-400 dark:border-gray-600 dark:text-gray-300">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C3.048 7.58 2.25 8.507 2.25 9.574v9.176c0 1.035.84 1.875 1.875 1.875h15.75c1.035 0 1.875-.84 1.875-1.875V9.574c0-1.067-.799-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                            {{ $paymentReceipt ? 'Cambiar comprobante' : 'Adjuntar comprobante' }}

                            <input
                                type="file"
                                accept="image/*"
                                capture="environment"
                                wire:model="paymentReceipt"
                                class="hidden"
                            >
                        </label>

                        <button
                            type="button"
                            wire:click="chargeWith('transferencia')"
                            wire:loading.attr="disabled"
                            wire:target="chargeWith"
                            @disabled(! $paymentReceipt)
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-primary-500 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            Cobrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
