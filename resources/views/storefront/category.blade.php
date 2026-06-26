@extends('layouts.storefront')

@section('title', $category->meta_title ?? $category->name.' | Rocha Sports')
@section('meta_description', $category->meta_description ?? $category->short_description)

@section('schema')
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@@type": "ListItem",
                    "position": 1,
                    "name": "Início",
                    "item": "{{ route('home') }}"
                },
                {
                    "@@type": "ListItem",
                    "position": 2,
                    "name": "{{ $category->name }}",
                    "item": "{{ route('categories.show', $category) }}"
                }
            ]
        }
    </script>
@endsection

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <nav class="mb-4 text-sm text-slate-500">
                <ol class="flex flex-wrap gap-2">
                    <li><a href="{{ route('home') }}">Início</a></li>
                    <li>/</li>
                    <li class="text-slate-700">{{ $category->name }}</li>
                </ol>
            </nav>
            <p class="text-sm font-semibold text-rocha-blue">Categoria</p>
            <h1 class="mt-2 text-2xl font-bold leading-snug text-slate-950 md:text-3xl">{{ $category->name }}</h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ $category->seo_description ?? $category->short_description }}</p>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-8 lg:grid-cols-[16rem_1fr] lg:px-6">
        <aside class="hidden h-fit rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:block">
            <h2 class="font-bold text-slate-950">Filtros</h2>
            <form action="{{ route('categories.show', $category) }}" method="GET" class="mt-4 space-y-4">
                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Marca</span>
                    <select name="marca" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue">
                        <option value="">Todas</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected($selectedBrand === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block">
                    <span class="text-sm font-bold text-slate-700">Ordenar</span>
                    <select name="ordenar" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue">
                        <option value="relevancia" @selected($selectedSort === 'relevancia')>Relevância</option>
                        <option value="mais-vendidos" @selected($selectedSort === 'mais-vendidos')>Mais vendidos</option>
                        <option value="ofertas" @selected($selectedSort === 'ofertas')>Ofertas</option>
                        <option value="menor-preco" @selected($selectedSort === 'menor-preco')>Menor preço</option>
                        <option value="maior-preco" @selected($selectedSort === 'maior-preco')>Maior preço</option>
                    </select>
                </label>

                <button class="w-full rounded-lg bg-slate-950 px-4 py-3 text-sm font-bold text-white" type="submit">Aplicar filtros</button>
                <a href="{{ route('categories.show', $category) }}" class="block text-center text-sm font-bold text-slate-600">Limpar</a>
            </form>
        </aside>

        <div>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                <p class="text-sm font-semibold text-slate-600">{{ $products->total() }} produtos encontrados</p>
                @if ($selectedBrand || $selectedSort !== 'relevancia')
                    <a href="{{ route('categories.show', $category) }}" class="text-sm font-bold text-rocha-blue">Limpar filtros</a>
                @endif
            </div>

            <details class="mb-5 rounded-lg border border-slate-200 bg-white shadow-sm lg:hidden">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-bold text-slate-950">
                    <span class="inline-flex items-center gap-2"><x-rocha-icon name="sliders-horizontal" class="size-4 text-rocha-blue" />Filtros e ordenação</span>
                    <x-rocha-icon name="chevron-right" class="size-4 text-slate-500" />
                </summary>
                <form action="{{ route('categories.show', $category) }}" method="GET" class="space-y-4 border-t border-slate-200 p-4">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Marca</span>
                        <select name="marca" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue">
                            <option value="">Todas</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->slug }}" @selected($selectedBrand === $brand->slug)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">Ordenar</span>
                        <select name="ordenar" class="mt-2 h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm outline-none focus:border-rocha-blue">
                            <option value="relevancia" @selected($selectedSort === 'relevancia')>Relevância</option>
                            <option value="mais-vendidos" @selected($selectedSort === 'mais-vendidos')>Mais vendidos</option>
                            <option value="ofertas" @selected($selectedSort === 'ofertas')>Ofertas</option>
                            <option value="menor-preco" @selected($selectedSort === 'menor-preco')>Menor preço</option>
                            <option value="maior-preco" @selected($selectedSort === 'maior-preco')>Maior preço</option>
                        </select>
                    </label>

                    <button class="w-full rounded-lg bg-slate-950 px-4 py-3 text-sm font-bold text-white" type="submit">Aplicar filtros</button>
                </form>
            </details>

            @if ($products->isEmpty())
                <div class="rounded-lg border border-slate-200 bg-white p-8 text-center">
                    <h2 class="text-lg font-bold text-slate-950 md:text-xl">Nenhum produto encontrado</h2>
                    <p class="mt-2 text-slate-600">Tente limpar os filtros ou escolher outra marca.</p>
                    <a href="{{ route('categories.show', $category) }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Limpar filtros</a>
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
