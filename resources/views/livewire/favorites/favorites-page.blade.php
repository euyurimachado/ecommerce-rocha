<div>
    @if ($products->isEmpty())
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
            <div class="mx-auto grid size-14 place-items-center rounded-lg bg-rocha-blue/10 text-rocha-blue">
                <x-rocha-icon name="heart" class="size-7" />
            </div>
            <h1 class="mt-4 text-2xl font-black text-slate-950">Sua lista está vazia</h1>
            <p class="mt-2 text-sm text-slate-600">Favorite produtos para montar sua próxima compra com mais agilidade.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-black text-white transition hover:bg-rocha-blue-dark">Explorar produtos</a>
        </div>
    @else
        <div class="flex items-end justify-between gap-4">
            <div>
                <p class="text-sm font-bold text-rocha-blue">Favoritos</p>
                <h1 class="mt-1 text-3xl font-black text-slate-950">Produtos salvos</h1>
                <p class="mt-1 text-sm text-slate-600">Itens separados para recompra rápida ou comparação.</p>
            </div>
            <span class="rounded-lg bg-slate-100 px-3 py-2 text-sm font-black text-slate-700">{{ $products->count() }} {{ $products->count() === 1 ? 'item' : 'itens' }}</span>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    @endif
</div>
