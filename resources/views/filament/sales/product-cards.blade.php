@php
    $colors = [
        'bg-rose-50 border-rose-200 hover:border-rose-400 dark:bg-rose-500/10 dark:border-rose-500/20 dark:hover:border-rose-400/60',
        'bg-amber-50 border-amber-200 hover:border-amber-400 dark:bg-amber-500/10 dark:border-amber-500/20 dark:hover:border-amber-400/60',
        'bg-emerald-50 border-emerald-200 hover:border-emerald-400 dark:bg-emerald-500/10 dark:border-emerald-500/20 dark:hover:border-emerald-400/60',
        'bg-sky-50 border-sky-200 hover:border-sky-400 dark:bg-sky-500/10 dark:border-sky-500/20 dark:hover:border-sky-400/60',
        'bg-violet-50 border-violet-200 hover:border-violet-400 dark:bg-violet-500/10 dark:border-violet-500/20 dark:hover:border-violet-400/60',
        'bg-fuchsia-50 border-fuchsia-200 hover:border-fuchsia-400 dark:bg-fuchsia-500/10 dark:border-fuchsia-500/20 dark:hover:border-fuchsia-400/60',
        'bg-lime-50 border-lime-200 hover:border-lime-400 dark:bg-lime-500/10 dark:border-lime-500/20 dark:hover:border-lime-400/60',
        'bg-cyan-50 border-cyan-200 hover:border-cyan-400 dark:bg-cyan-500/10 dark:border-cyan-500/20 dark:hover:border-cyan-400/60',
    ];
@endphp

<div>
    <p class="fi-fo-field-wrp-label mb-2 text-sm font-medium text-gray-950 dark:text-white">
        Productos disponibles
    </p>

    <div class="grid grid-cols-4 gap-2 sm:gap-3">
        @forelse ($products as $product)
            <button
                type="button"
                wire:click="mountAction('addProductToSale', { product: {{ $product->id }} })"
                class="flex flex-col items-start gap-1 rounded-lg border p-2 text-left shadow-sm transition hover:shadow-md sm:p-4 {{ $colors[$loop->index % count($colors)] }}"
            >
                <span class="text-xs font-medium text-gray-950 sm:text-sm dark:text-white">{{ $product->name }}</span>
                <span class="text-xs text-gray-600 dark:text-gray-300">{{ \Illuminate\Support\Number::currency($product->price) }}</span>
            </button>
        @empty
            <p class="col-span-full text-sm text-gray-500 dark:text-gray-400">
                No hay productos activos disponibles.
            </p>
        @endforelse
    </div>
</div>
