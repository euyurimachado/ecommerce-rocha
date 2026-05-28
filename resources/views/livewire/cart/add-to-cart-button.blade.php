<button
    wire:click="add"
    wire:loading.attr="disabled"
    wire:target="add"
    type="button"
    class="{{ $fullWidth ? 'w-full rounded-lg px-5 py-3 text-sm' : 'grid size-10 place-items-center rounded-lg text-xl' }} bg-sky-600 font-black text-white transition hover:bg-sky-700 disabled:cursor-wait disabled:opacity-70"
    aria-label="Adicionar {{ $product->name }} ao carrinho"
>
    <span wire:loading.remove wire:target="add">{{ $label }}</span>
    <span wire:loading wire:target="add">...</span>
</button>
