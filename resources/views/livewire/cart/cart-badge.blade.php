<a class="relative grid size-10 place-items-center rounded-lg bg-rocha-blue text-white shadow-sm transition hover:bg-rocha-blue-dark" href="{{ route('cart') }}" aria-label="Carrinho">
    <x-rocha-icon name="shopping-cart" class="size-5" />
    @if ($count > 0)
        <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-slate-950 px-1 text-[11px] font-black leading-5 text-white">{{ $count }}</span>
    @endif
</a>
