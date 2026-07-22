<div class="{{ $fullWidth ? '' : 'contents' }}">
<button
    x-data
    x-on:click.prevent="$wire.add(window.rochaProductVariantSelections || {})"
    wire:loading.attr="disabled"
    wire:target="add"
    type="button"
        data-add-to-cart-button
        class="{{ $fullWidth ? 'w-full rounded-lg px-4 py-3 text-sm' : 'grid size-10 place-items-center rounded-lg text-xl' }} bg-rocha-blue font-bold text-white transition hover:bg-rocha-blue-dark disabled:cursor-wait disabled:opacity-70"
        aria-label="Adicionar {{ $product->name }} ao carrinho"
        @disabled($product->availableQuantityForSelections() <= 0)
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

@if ($fullWidth && $stockError)
    <p class="mt-2 text-sm font-semibold text-rose-700">{{ $stockError }}</p>
@endif
</div>
