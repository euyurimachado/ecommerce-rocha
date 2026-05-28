@extends('layouts.storefront')

@section('title', ($query ? 'Busca por '.$query : 'Buscar suplementos').' | Rocha Sports')
@section('meta_description', 'Busque suplementos, marcas e categorias na Rocha Sports com entrega rápida em Campos dos Goytacazes.')

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <p class="text-sm font-bold text-sky-700">Busca Rocha Sports</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">
                {{ $query ? 'Resultados para "'.$query.'"' : 'Buscar suplementos' }}
            </h1>
            <p class="mt-3 max-w-2xl text-slate-600">Encontre produtos por nome, marca, categoria ou objetivo e compre com entrega local ou retirada.</p>

            <form action="{{ route('search') }}" method="GET" class="mt-6 grid gap-3 md:grid-cols-[1fr_auto]">
                <label class="sr-only" for="search-page-input">Buscar</label>
                <input id="search-page-input" name="q" value="{{ $query }}" class="h-12 rounded-lg border border-slate-200 px-4 outline-none focus:border-sky-500" type="search" placeholder="Whey, creatina, Max Titanium...">
                <button class="rounded-lg bg-sky-600 px-6 py-3 font-black text-white" type="submit">Buscar</button>
            </form>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($popularSearches as $popularSearch)
                    <a href="{{ route('search', ['q' => $popularSearch]) }}" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-700">
                        {{ $popularSearch }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-8 lg:grid-cols-[16rem_1fr] lg:px-6">
        <aside class="h-fit rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="font-black text-slate-950">Filtros</h2>
            <form action="{{ route('search') }}" method="GET" class="mt-4 space-y-4">
                <input type="hidden" name="q" value="{{ $query }}">

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Categoria</span>
                    <select name="categoria" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-sky-500">
                        <option value="">Todas</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($selectedCategory === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Marca</span>
                    <select name="marca" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-sky-500">
                        <option value="">Todas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected($selectedBrand === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Ordenar</span>
                    <select name="ordenar" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-sky-500">
                        <option value="relevancia" @selected($selectedSort === 'relevancia')>Relevância</option>
                        <option value="mais-vendidos" @selected($selectedSort === 'mais-vendidos')>Mais vendidos</option>
                        <option value="ofertas" @selected($selectedSort === 'ofertas')>Ofertas</option>
                        <option value="menor-preco" @selected($selectedSort === 'menor-preco')>Menor preço</option>
                        <option value="maior-preco" @selected($selectedSort === 'maior-preco')>Maior preço</option>
                    </select>
                </label>

                <button class="w-full rounded-lg bg-slate-950 px-4 py-3 text-sm font-black text-white" type="submit">Aplicar filtros</button>
                <a href="{{ route('search') }}" class="block text-center text-sm font-bold text-slate-600">Limpar</a>
            </form>
        </aside>

        <div>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-semibold text-slate-600">{{ $products->total() }} produtos encontrados</p>
                @if ($query || $selectedCategory || $selectedBrand)
                    <a href="{{ route('search') }}" class="text-sm font-bold text-sky-700">Nova busca</a>
                @endif
            </div>

            @if ($products->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center">
                    <h2 class="text-xl font-black text-slate-950">Nenhum produto encontrado</h2>
                    <p class="mt-2 text-slate-600">Tente buscar por whey, creatina, marca ou objetivo.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-sky-600 px-5 py-3 font-black text-white">Voltar para a loja</a>
                </div>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4">
                    @foreach ($products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $products->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
