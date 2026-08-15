<div class="flex w-full items-center gap-3" data-purchase-bar data-maximum-quantity="{{ $maximumQuantity }}">
    <div class="grid h-14 w-[140px] shrink-0 grid-cols-3 overflow-hidden rounded-xl border border-slate-300 bg-white">
        <button class="grid place-items-center text-2xl font-medium text-slate-700 transition hover:bg-slate-50 disabled:text-slate-300" type="button" wire:click="decrement" @disabled($quantity <= 1) aria-label="Diminuir quantidade">−</button>
        <output class="grid place-items-center text-lg font-bold text-slate-950" aria-live="polite">{{ $quantity }}</output>
        <button class="grid place-items-center text-2xl font-medium text-slate-700 transition hover:bg-slate-50 disabled:text-slate-300" type="button" wire:click="increment" @disabled($maximumQuantity <= 0 || $quantity >= $maximumQuantity) aria-label="Aumentar quantidade">+</button>
    </div>
    @if ($added)
    <a
        href="{{ route('cart') }}"
        class="grid h-14 min-w-0 flex-1 place-items-center rounded-xl bg-rocha-blue px-3 text-[15px] font-bold text-white transition hover:bg-rocha-blue-dark"
    >
        Ver carrinho
    </a>
    @else
    <button
        wire:click="add"
        wire:loading.attr="disabled"
        wire:target="add"
        type="button"
        data-add-to-cart-button
        class="h-14 min-w-0 flex-1 rounded-xl bg-rocha-blue px-3 text-[15px] font-bold text-white transition hover:bg-rocha-blue-dark disabled:cursor-not-allowed disabled:bg-slate-300"
        aria-label="Adicionar {{ $product->name }} ao carrinho"
        @disabled($maximumQuantity <= 0)
    >
        <span wire:loading.remove wire:target="add">Adicionar ao carrinho</span>
        <span wire:loading wire:target="add">Adicionando...</span>
    </button>
    @endif
</div>
