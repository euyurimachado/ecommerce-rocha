<a class="relative grid size-10 place-items-center rounded-lg bg-sky-600 text-white shadow-sm transition hover:bg-sky-700" href="{{ route('cart') }}" aria-label="Carrinho">
    <x-rocha-icon name="shopping-cart" class="size-5" />
    @if ($count > 0)
        <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-slate-950 px-1 text-[11px] font-black leading-5 text-white">{{ $count }}</span>
    @endif
</a>
