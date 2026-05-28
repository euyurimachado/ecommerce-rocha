<div>
    @if ($count > 0)
        <a href="{{ route('cart') }}" class="fixed bottom-20 left-4 right-4 z-40 flex items-center justify-between rounded-lg bg-sky-600 px-4 py-3 font-black text-white shadow-xl md:hidden">
            <span class="inline-flex items-center gap-2"><x-rocha-icon name="shopping-cart" class="size-5" />Carrinho</span>
            <span>{{ $count }} {{ $count === 1 ? 'item' : 'itens' }} - {{ $subtotal }}</span>
        </a>
    @endif
</div>
