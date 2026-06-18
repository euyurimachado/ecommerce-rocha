<button
    x-data
    x-on:click.prevent="$wire.add(window.rochaProductVariantSelections || {})"
    wire:loading.attr="disabled"
    wire:target="add"
    type="button"
    class="{{ $fullWidth ? 'w-full rounded-lg px-4 py-3 text-sm' : 'grid size-10 place-items-center rounded-lg text-xl' }} bg-rocha-blue font-bold text-white transition hover:bg-rocha-blue-dark disabled:cursor-wait disabled:opacity-70"
    aria-label="Adicionar {{ $product->name }} ao carrinho"
>
    <span wire:loading.remove wire:target="add" class="inline-flex items-center justify-center gap-2">
        @if ($label === '+')
            <x-rocha-icon name="plus" class="size-5" />
        @else
            <x-rocha-icon name="shopping-cart" class="size-4" />
            {{ $label }}
        @endif
    </span>
    <span wire:loading wire:target="add">...</span>
</button>
