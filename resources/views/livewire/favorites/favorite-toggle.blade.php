<button
    wire:click="toggle"
    type="button"
    class="{{ $compact ? 'grid size-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm' : 'inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 px-4 py-3 text-sm font-black text-slate-700' }} transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue {{ $isFavorited ? 'border-rocha-blue/30 bg-rocha-blue/10 text-rocha-blue' : '' }}"
    aria-label="{{ $isFavorited ? 'Remover dos favoritos' : 'Adicionar aos favoritos' }}"
>
    <x-rocha-icon name="heart" class="{{ $compact ? 'size-5' : 'size-4' }} {{ $isFavorited ? 'fill-current' : '' }}" />
    @unless ($compact)
        {{ $isFavorited ? 'Favorito' : 'Favoritar' }}
    @endunless
</button>
