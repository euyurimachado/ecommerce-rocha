<a class="relative grid size-10 place-items-center transition {{ $variant === 'minimal' ? 'rounded-full text-slate-900 hover:bg-rocha-blue/10 hover:text-rocha-blue' : 'rounded-lg bg-rocha-blue text-white shadow-sm hover:bg-rocha-blue-dark' }}" href="{{ route('cart') }}" aria-label="Carrinho">
    <x-rocha-icon name="shopping-cart" class="{{ $variant === 'minimal' ? 'size-7' : 'size-5' }}" />
    @if ($count > 0)
        <span class="absolute {{ $variant === 'minimal' ? '-right-0.5 top-0 bg-rocha-blue' : '-right-1 -top-1 bg-slate-950' }} grid min-w-5 place-items-center rounded-full px-1 text-[11px] font-black leading-5 text-white">{{ $count }}</span>
    @endif
</a>
