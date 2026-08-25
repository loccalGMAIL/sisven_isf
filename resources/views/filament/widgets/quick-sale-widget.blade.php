<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.694 2.582-7.152.107-.45-.216-.898-.678-.898H5.25M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />
                </svg>
            </div>

            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Nueva venta</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Registrá una venta y cobrá al instante.</p>
            </div>

            <x-filament::button tag="a" :href="$this->getSaleUrl()" color="primary">
                Crear venta
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
