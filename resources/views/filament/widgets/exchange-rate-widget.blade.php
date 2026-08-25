<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            <div class="flex-1">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white">Cotización del dólar</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ \Illuminate\Support\Number::currency($this->getSetting()->dollar_rate, 'ARS', 'es_AR') }} por USD
                </p>
            </div>

            <x-filament::button
                wire:click="updateFromBna"
                wire:loading.attr="disabled"
                wire:target="updateFromBna"
                color="gray"
                icon="heroicon-o-arrow-path"
            >
                <span wire:loading.remove wire:target="updateFromBna">Actualizar</span>
                <span wire:loading wire:target="updateFromBna">Actualizando...</span>
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
