<a class="relative grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue" href="{{ route('favorites.index') }}" aria-label="Favoritos">
    <x-rocha-icon name="heart" class="size-5" />
    @if ($count > 0)
        <span class="absolute -right-1 -top-1 grid min-w-5 place-items-center rounded-full bg-rocha-blue px-1 text-[11px] font-black leading-5 text-white">{{ $count }}</span>
    @endif
</a>
