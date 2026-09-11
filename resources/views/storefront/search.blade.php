@extends('layouts.storefront')

@section('title', ($query ? 'Resultados para '.$query : 'Busca').' | Rocha Sports')
@section('meta_description', 'Busque suplementos, marcas e categorias na Rocha Sports com entrega rápida em Campos dos Goytacazes.')

@section('content')
    @php
        $homeSectionLabels = [
            'emagrecer' => 'Para emagrecer',
            'energia' => 'Para ter energia',
            'massa' => 'Para ganhar massa',
            'whey' => 'Festival Whey Protein',
            'creatina' => 'Casa da creatina',
        ];
        $selectedHomeSectionLabel = $homeSectionLabels[$selectedHomeSection] ?? null;
    @endphp

    <div class="min-h-[70vh] bg-white pb-[max(7rem,calc(5rem+env(safe-area-inset-bottom)))] md:bg-slate-50 md:pb-12" data-search-experience data-has-results="{{ $hasActiveSearch ? 'true' : 'false' }}">
        <section class="sticky top-0 z-30 border-b border-slate-100 bg-white/95 backdrop-blur md:static md:border-slate-200">
            <div class="mx-auto max-w-5xl px-4 py-4 md:px-6 md:py-8">
                <div class="hidden md:block">
                    <p class="text-sm font-semibold text-rocha-blue">Encontre seu próximo suplemento</p>
                    <h1 class="mt-1 text-3xl font-bold text-slate-950">Busca Rocha Sports</h1>
                </div>
                <form action="{{ route('search') }}" method="GET" role="search" class="flex items-center gap-3 md:mt-5" data-search-form>
                    <label class="sr-only" for="catalog-search">Buscar produtos, marcas ou categorias</label>
                    <div class="relative min-w-0 flex-1">
                        <x-rocha-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-slate-500" />
                        <input id="catalog-search" name="q" value="{{ $query }}" type="search" enterkeyhint="search" autocomplete="off" placeholder="O que você está procurando?" class="h-13 w-full rounded-2xl border-0 bg-slate-100 pl-12 pr-4 text-base text-slate-950 outline-none ring-1 ring-transparent transition placeholder:text-slate-500 focus:bg-white focus:ring-2 focus:ring-rocha-blue/30 md:h-14 md:text-lg" data-search-input>
                    </div>
                    <button type="button" class="hidden shrink-0 text-sm font-bold text-rocha-blue md:text-base" data-search-cancel>Cancelar</button>
                    <button type="submit" class="hidden h-13 shrink-0 rounded-2xl bg-rocha-blue px-6 font-bold text-white transition hover:bg-rocha-blue-dark md:inline-flex md:items-center">Buscar</button>
                </form>
            </div>
        </section>

        @if (! $hasActiveSearch)
            <div class="mx-auto max-w-5xl px-4 py-7 md:px-6 md:py-10" data-search-discovery>
                @if ($discoveryCategories->isNotEmpty())
                    <section aria-labelledby="search-categories-title">
                        <div class="flex items-center justify-between gap-4">
                            <h2 id="search-categories-title" class="text-xl font-bold text-slate-950 md:text-2xl">Explore por categoria</h2>
                            <a href="#todas-categorias" class="text-sm font-bold text-rocha-blue">Ver mais</a>
                        </div>
                        <div id="todas-categorias" class="scrollbar-hidden -mx-4 mt-5 flex snap-x gap-4 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-6 md:overflow-visible md:px-0">
                            @foreach ($discoveryCategories as $category)
                                <a href="{{ route('search', ['categoria' => $category->slug]) }}" class="group w-[5.75rem] shrink-0 snap-start text-center md:w-auto" data-search-term="{{ $category->name }}">
                                    <span class="mx-auto grid size-20 place-items-center overflow-hidden rounded-full bg-slate-100 ring-1 ring-slate-200 transition group-hover:ring-2 group-hover:ring-rocha-blue/40 md:size-24">
                                        @if ($category->discovery_image_path)
                                            <img class="h-full w-full object-contain p-2" src="{{ asset('storage/'.$category->discovery_image_path) }}" alt="" loading="lazy">
                                        @else
                                            <span class="text-lg font-bold text-rocha-blue">{{ $category->icon ?: mb_substr($category->name, 0, 2) }}</span>
                                        @endif
                                    </span>
                                    <span class="mt-2 block line-clamp-2 text-sm font-medium leading-tight text-slate-800">{{ $category->name }}</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($brands->isNotEmpty())
                    <section class="mt-10" aria-labelledby="search-brands-title">
                        <h2 id="search-brands-title" class="text-xl font-bold text-slate-950 md:text-2xl">Marcas</h2>
                        <div class="mt-4 flex flex-wrap gap-2.5">
                            @foreach ($brands->take(12) as $brand)
                                <a href="{{ route('search', ['marca' => $brand->slug]) }}" class="rounded-full border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue">{{ $brand->name }}</a>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <div class="mx-auto hidden max-w-2xl px-4 py-7 md:px-6" data-search-active-panel>
                @include('storefront.partials.search-active-panel')
            </div>
        @else
            <section class="mx-auto grid max-w-7xl gap-6 px-4 py-7 lg:grid-cols-[16rem_1fr] lg:px-6 lg:py-10">
                <aside class="hidden h-fit rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:block">@include('storefront.partials.search-filters')</aside>
                <div class="min-w-0">
                    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-rocha-blue">{{ $products->total() }} {{ Str::plural('produto', $products->total()) }} encontrados</p>
                            <h1 class="mt-1 text-xl font-bold text-slate-950 md:text-2xl">{{ $selectedHomeSectionLabel ? 'Seleção '.$selectedHomeSectionLabel : ($query ? 'Resultados para “'.$query.'”' : 'Produtos encontrados') }}</h1>
                        </div>
                        <a href="{{ route('search') }}" class="text-sm font-bold text-rocha-blue">Limpar busca</a>
                    </div>

                    <details class="mb-5 rounded-xl border border-slate-200 bg-white shadow-sm lg:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-sm font-bold text-slate-950">
                            <span class="inline-flex items-center gap-2"><x-rocha-icon name="sliders-horizontal" class="size-4 text-rocha-blue" />Filtros e ordenação</span>
                            <x-rocha-icon name="chevron-down" class="size-4 text-slate-500" />
                        </summary>
                        <div class="border-t border-slate-200 p-4">@include('storefront.partials.search-filters')</div>
                    </details>

                    @if ($products->isEmpty())
                        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center md:p-12">
                            <span class="mx-auto grid size-16 place-items-center rounded-full bg-rocha-blue/10 text-rocha-blue"><x-rocha-icon name="search" class="size-7" /></span>
                            <h2 class="mt-5 text-xl font-bold text-slate-950">Nenhum produto encontrado{{ $query ? ' para “'.$query.'”' : '' }}</h2>
                            <p class="mx-auto mt-2 max-w-md text-slate-600">Tente buscar por outro produto, marca ou categoria.</p>
                        </div>
                        @if ($popularTerms->isNotEmpty())<div class="mt-8 rounded-2xl bg-white p-5">@include('storefront.partials.search-popular-terms')</div>@endif
                    @else
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4" data-infinite-product-grid>
                            @foreach ($products as $product)@include('storefront.partials.product-card', ['product' => $product, 'compactGrid' => true])@endforeach
                        </div>
                        <div class="mt-8 flex min-h-10 items-center justify-center text-sm text-slate-500" data-infinite-scroll data-next-url="{{ $products->nextPageUrl() }}" role="status" aria-live="polite">
                            @if ($products->hasMorePages())<span data-infinite-loading>Carregando mais produtos...</span>@endif
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>
@endsection
