@extends('layouts.storefront')

@section('storefront_variant', 'home')

@section('title', 'Rocha Sports | Suplementos com entrega rápida em Campos')
@section('meta_description', 'Compre whey, creatina, pré-treino e suplementos originais com entrega rápida em Campos dos Goytacazes ou retirada na Rocha Sports.')

@section('schema')
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "LocalBusiness",
            "name": "Rocha Sports",
            "url": "{{ url('/') }}",
            "address": {
                "@@type": "PostalAddress",
                "addressLocality": "Campos dos Goytacazes",
                "addressRegion": "RJ",
                "addressCountry": "BR"
            },
            "areaServed": "Campos dos Goytacazes, RJ"
        }
    </script>
@endsection

@section('content')
    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 pb-4 pt-3 md:py-4 lg:px-6">
            <div class="grid grid-cols-5 gap-x-2 gap-y-3 md:flex md:gap-3 md:overflow-x-auto md:pb-1">
                @foreach ($categories->take(10) as $category)
                    @php
                        $categoryProduct = $category->products->first();
                    @endphp
                    <a href="{{ route('categories.show', $category) }}" class="group flex min-w-0 flex-col items-center gap-1.5 text-center text-[10px] font-medium leading-[1.2] text-slate-800 md:min-w-fit md:flex-row md:gap-2 md:rounded-lg md:border md:border-slate-200 md:bg-slate-50 md:px-3 md:py-2.5 md:text-left md:text-sm md:font-semibold">
                        <span class="grid aspect-square w-full place-items-center overflow-hidden rounded-lg bg-[#f4f4f4] p-1 md:size-11 md:rounded-full md:border md:border-slate-200 md:bg-white">
                            @if ($categoryProduct?->image_path)
                                <img class="h-full w-full object-contain md:rounded-full" src="{{ asset('storage/'.$categoryProduct->image_path) }}" alt="" loading="lazy">
                            @else
                                <x-rocha-icon name="package-open" class="size-5 text-rocha-blue" />
                            @endif
                        </span>
                        <span class="line-clamp-2 min-h-6 w-full overflow-hidden whitespace-normal break-normal md:min-h-0 md:w-auto">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $heroSlides = $banners
            ->values()
            ->map(fn ($banner) => [
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'cta_label' => $banner->cta_label ?: 'Comprar agora',
                'url' => $banner->url ?: route('search'),
                'image_url' => asset('storage/'.$banner->image_path),
            ]);
    @endphp

    @if ($heroSlides->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto max-w-7xl px-4 pb-5 pt-1 lg:px-6 lg:py-7">
                <div class="relative overflow-hidden rounded-lg bg-slate-900 md:shadow-lg" data-home-hero-slider>
                    <div class="relative aspect-[1.93/1] md:aspect-[16/6]">
                        @foreach ($heroSlides as $slide)
                            <article class="{{ $loop->first ? 'opacity-100' : 'pointer-events-none opacity-0' }} absolute inset-0 transition-opacity duration-500" data-home-hero-slide>
                                <a class="block h-full w-full bg-slate-100" href="{{ $slide['url'] }}" aria-label="{{ $slide['title'] }}">
                                    <img class="h-full w-full object-cover" src="{{ $slide['image_url'] }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                                </a>
                            </article>
                        @endforeach
                    </div>

                    @if ($heroSlides->count() > 1)
                        <button class="absolute left-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-slate-900 shadow-sm transition hover:bg-white md:grid" type="button" aria-label="Banner anterior" data-home-hero-prev>
                            <x-rocha-icon name="chevron-right" class="size-5 rotate-180" />
                        </button>
                        <button class="absolute right-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-slate-900 shadow-sm transition hover:bg-white md:grid" type="button" aria-label="Próximo banner" data-home-hero-next>
                            <x-rocha-icon name="chevron-right" class="size-5" />
                        </button>

                        <div class="absolute bottom-2 left-4 flex gap-2 md:bottom-4 md:left-1/2 md:-translate-x-1/2">
                            @foreach ($heroSlides as $slide)
                                <button class="h-2 w-5 rounded-full bg-white/55 transition-all aria-[current=true]:bg-rocha-blue md:h-2.5 md:w-2.5 md:bg-white/45 md:aria-[current=true]:w-7 md:aria-[current=true]:bg-white" type="button" aria-label="Ir para banner {{ $loop->iteration }}" data-home-hero-dot></button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    <section id="mais-pedidos" class="mx-auto max-w-7xl bg-white py-5 md:bg-transparent md:px-4 md:py-8 lg:px-6">
        <div class="flex items-end justify-between gap-4 px-4 md:px-0">
            <div class="min-w-0 pr-2">
                <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Os mais pedidos</h2>
                <p class="mt-0.5 text-xs text-slate-800 md:mt-1 md:text-sm md:text-slate-600">Produtos em alta para você aproveitar</p>
            </div>
            <a href="{{ route('search', ['ordenar' => 'mais-vendidos']) }}" class="shrink-0 whitespace-nowrap text-base font-medium text-slate-950 md:text-sm md:font-bold md:text-rocha-blue">Ver mais <x-rocha-icon name="chevron-right" class="hidden size-4 md:inline" /></a>
        </div>

        <div class="scrollbar-hidden mt-4 flex gap-2.5 overflow-x-auto px-4 pb-1 md:grid md:grid-cols-3 md:gap-4 md:px-0 lg:grid-cols-6">
            @foreach ($bestSellers as $product)
                @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
            @endforeach
        </div>
    </section>

    <section class="bg-white py-5 md:hidden">
        <div class="flex items-end justify-between gap-4 px-4">
            <div class="min-w-0 pr-2">
                <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Mais ofertas para você</h2>
                <p class="mt-0.5 text-xs text-slate-800">Preços especiais para aproveitar</p>
            </div>
            <a href="{{ route('search', ['ordenar' => 'ofertas']) }}" class="shrink-0 whitespace-nowrap text-base font-medium text-slate-950">Ver mais</a>
        </div>
        <div class="scrollbar-hidden mt-4 flex gap-2.5 overflow-x-auto px-4 pb-1">
            @foreach ($offers as $product)
                @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
            @endforeach
        </div>
    </section>

    @if ($weightLossProducts->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
                <div class="grid gap-5 md:grid-cols-[18rem_1fr]">
                    <div class="rounded-lg bg-slate-950 p-5 text-white">
                        <p class="text-xs font-bold text-rocha-blue">Objetivo</p>
                        <h2 class="mt-2 text-xl font-bold md:text-2xl">Para emagrecer</h2>
                        <p class="mt-3 text-sm leading-relaxed text-slate-200">Termogênicos, cafeína e fórmulas para foco em treinos mais intensos.</p>
                        <a href="{{ route('search', ['secao' => 'emagrecer']) }}" class="mt-5 inline-flex rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-950">Ver seleção</a>
                    </div>
                    <div class="scrollbar-hidden -mx-4 flex gap-2.5 overflow-x-auto px-4 pb-1 md:mx-0 md:grid md:grid-cols-2 md:gap-4 md:px-0 lg:grid-cols-4">
                        @foreach ($weightLossProducts as $product)
                            @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    @if ($energyProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Para ter energia</h2>
                    <p class="mt-1 text-sm text-slate-600">Géis, pré-treinos e reposição para não quebrar no meio do treino.</p>
                </div>
                <a href="{{ route('search', ['categoria' => 'energia']) }}" class="inline-flex items-center gap-1 text-sm font-bold text-rocha-blue">Ver energia <x-rocha-icon name="chevron-right" class="size-4" /></a>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-[1.05fr_0.95fr]">
                @if ($energyBanner)
                    <a href="{{ $energyBanner->url ?: route('search', ['categoria' => 'energia']) }}" class="block overflow-hidden rounded-lg bg-slate-100 shadow-sm">
                        <img class="aspect-[16/9] h-full w-full object-cover" src="{{ asset('storage/'.$energyBanner->image_path) }}" alt="{{ $energyBanner->title }}" loading="lazy">
                    </a>
                @endif
                <div class="scrollbar-hidden -mx-4 flex gap-2.5 overflow-x-auto px-4 pb-1 md:mx-0 md:grid md:grid-cols-2 md:gap-4 md:px-0 {{ $energyBanner ? '' : 'lg:col-span-2 lg:grid-cols-4' }}">
                    @foreach ($energyProducts->take($energyBanner ? 4 : 8) as $product)
                        @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($massProducts->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Para ganhar massa</h2>
                        <p class="mt-1 text-sm text-slate-600">Hipercalóricos, whey e creatinas para dieta de volume.</p>
                    </div>
                    <a href="{{ route('search', ['q' => 'massa whey creatina']) }}" class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap text-sm font-bold text-rocha-blue">Montar combo <x-rocha-icon name="chevron-right" class="size-4" /></a>
                </div>
                <div class="scrollbar-hidden -mx-4 mt-5 flex gap-2.5 overflow-x-auto px-4 pb-2 md:mx-0 md:grid md:grid-cols-3 md:gap-4 md:px-0 lg:grid-cols-6">
                    @foreach ($massProducts as $product)
                        @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($wheyFestival->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <div class="md:rounded-lg md:border md:border-slate-200 md:bg-white md:p-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold text-rocha-blue">Festival</p>
                        <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Whey protein</h2>
                        <p class="mt-1 text-sm text-slate-600">Compare marcas, preços e sabores com estoque real.</p>
                    </div>
                    <a href="{{ route('search', ['categoria' => 'whey-protein']) }}" class="inline-flex items-center gap-1 text-sm font-bold text-rocha-blue">Ver festival <x-rocha-icon name="chevron-right" class="size-4" /></a>
                </div>
                <div class="scrollbar-hidden -mx-4 mt-5 flex gap-2.5 overflow-x-auto px-4 pb-1 md:mx-0 md:grid md:grid-cols-3 md:gap-4 md:px-0 lg:grid-cols-5">
                    @foreach ($wheyFestival as $product)
                        @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($creatineHouse->isNotEmpty())
        <section class="bg-white">
            <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 text-slate-950 lg:grid-cols-[0.85fr_1.15fr] lg:px-6">
                <div>
                    <p class="text-xs font-bold text-rocha-blue">Força todo dia</p>
                    <h2 class="mt-2 text-xl font-bold md:text-2xl">Rocha Sports, a casa da creatina</h2>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Potes, sticks, Creapure e monohidratadas para colocar constância na rotina.</p>
                    <a href="{{ route('search', ['categoria' => 'creatina']) }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-4 py-2 text-sm font-bold text-white">Comprar creatina</a>
                </div>
                <div class="scrollbar-hidden -mx-4 flex gap-2.5 overflow-x-auto px-4 pb-1 md:mx-0 md:grid md:grid-cols-3 md:gap-4 md:px-0 lg:grid-cols-5">
                    @foreach ($creatineHouse as $product)
                        @include('storefront.partials.product-card', ['product' => $product, 'mobileCarousel' => true])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="ofertas" class="hidden bg-white md:block">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Ofertas para você</h2>
                    <p class="mt-1 text-sm text-slate-600">Promoções, combos e itens com alto giro.</p>
                </div>
                <a href="{{ route('search', ['ordenar' => 'ofertas']) }}" class="inline-flex items-center gap-1 text-sm font-bold text-rocha-blue">Ver ofertas <x-rocha-icon name="chevron-right" class="size-4" /></a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($offers as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <section class="overflow-hidden bg-white py-8">
        <div class="mx-auto max-w-7xl px-4 lg:px-6">
            <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Marcas parceiras</h2>
        </div>

        <div class="brand-logo-marquee mt-5" aria-label="Marcas parceiras Rocha Sports">
            <div class="brand-logo-track">
                @foreach ($brands->concat($brands) as $brand)
                    <a href="{{ route('search', ['marca' => $brand->slug]) }}" class="brand-logo-item" aria-label="Ver produtos {{ $brand->name }}">
                        @if ($brand->logo_path)
                            <img src="{{ asset('storage/'.$brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy">
                        @else
                            <span>{{ $brand->name }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    </section>

@endsection
