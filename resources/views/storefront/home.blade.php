@extends('layouts.storefront')

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
    @php
        $categoryIcons = [
            'whey-protein' => 'bottle-water',
            'creatina' => 'dumbbell',
            'pre-treino' => 'bolt',
            'vitaminas' => 'capsules',
            'snacks' => 'cookie',
            'acessorios' => 'flask-vial',
            'combos-e-kits' => 'boxes-stacked',
        ];
    @endphp

    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 lg:px-6">
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="flex min-w-fit items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-800 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue-dark">
                        <span class="grid size-10 place-items-center rounded-md bg-white text-rocha-blue shadow-sm ring-1 ring-slate-200">
                            <x-rocha-icon name="{{ $categoryIcons[$category->slug] ?? 'package-open' }}" class="size-5" />
                        </span>
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @php
        $placeholderSlides = collect([
            [
                'title' => 'Performance para o seu treino',
                'subtitle' => 'Whey, creatina e pré-treinos com curadoria Rocha Sports.',
                'cta_label' => 'Comprar agora',
                'url' => route('search'),
                'image_url' => asset('images/banners/home-slide-1.svg'),
            ],
            [
                'title' => 'Suplementos originais em Campos',
                'subtitle' => 'Entrega local rápida ou retirada direto na loja.',
                'cta_label' => 'Ver mais pedidos',
                'url' => '#mais-pedidos',
                'image_url' => asset('images/banners/home-slide-2.svg'),
            ],
            [
                'title' => 'Ofertas e combos selecionados',
                'subtitle' => 'Kits prontos para economizar e manter a rotina em dia.',
                'cta_label' => 'Ver ofertas',
                'url' => '#ofertas',
                'image_url' => asset('images/banners/home-slide-3.svg'),
            ],
        ]);

        $heroSlides = $banners
            ->values()
            ->map(fn ($banner, $index) => [
                'title' => $banner->title,
                'subtitle' => $banner->subtitle,
                'cta_label' => $banner->cta_label ?: 'Comprar agora',
                'url' => $banner->url ?: route('search'),
                'image_url' => $banner->image_path ? asset('storage/'.$banner->image_path) : asset('images/banners/home-slide-'.(($index % 3) + 1).'.svg'),
            ]);

        $heroSlides = $heroSlides->concat($placeholderSlides->slice($heroSlides->count()))->take(3)->values();
    @endphp

    <section class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-5 lg:px-6 lg:py-7">
            <div class="relative overflow-hidden rounded-lg bg-slate-900 shadow-lg" data-home-hero-slider>
                <div class="relative aspect-[2/1] md:aspect-[16/6]">
                    @foreach ($heroSlides as $slide)
                        <article class="{{ $loop->first ? 'opacity-100' : 'pointer-events-none opacity-0' }} absolute inset-0 transition-opacity duration-500" data-home-hero-slide>
                            <a class="block h-full w-full bg-slate-100" href="{{ $slide['url'] }}" aria-label="{{ $slide['title'] }}">
                                <img class="h-full w-full object-contain" src="{{ $slide['image_url'] }}" alt="" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                            </a>
                        </article>
                    @endforeach
                </div>

                <button class="absolute left-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-slate-900 shadow-sm transition hover:bg-white md:grid" type="button" aria-label="Banner anterior" data-home-hero-prev>
                    <x-rocha-icon name="chevron-right" class="size-5 rotate-180" />
                </button>
                <button class="absolute right-3 top-1/2 hidden size-10 -translate-y-1/2 place-items-center rounded-full bg-white/90 text-slate-900 shadow-sm transition hover:bg-white md:grid" type="button" aria-label="Próximo banner" data-home-hero-next>
                    <x-rocha-icon name="chevron-right" class="size-5" />
                </button>

                <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 gap-2">
                    @foreach ($heroSlides as $slide)
                        <button class="{{ $loop->first ? 'w-7 bg-white' : 'w-2.5 bg-white/45' }} h-2.5 rounded-full transition-all" type="button" aria-label="Ir para banner {{ $loop->iteration }}" data-home-hero-dot></button>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section id="mais-pedidos" class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Mais pedidos</h2>
                <p class="mt-1 text-sm text-slate-600">Produtos que mais saem na Rocha Sports.</p>
            </div>
            <a href="{{ route('search', ['ordenar' => 'mais-vendidos']) }}" class="inline-flex items-center gap-1 text-sm font-bold text-rocha-blue">Ver todos <x-rocha-icon name="chevron-right" class="size-4" /></a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($bestSellers as $product)
                @include('storefront.partials.product-card', ['product' => $product, 'compact' => true])
            @endforeach
        </div>
    </section>

    <section id="ofertas" class="bg-white">
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
                    @include('storefront.partials.product-card', ['product' => $product, 'compact' => true])
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <h2 class="text-xl font-bold text-slate-950 md:text-2xl">Marcas parceiras</h2>
        <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-5">
            @foreach ($brands as $brand)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-rocha-blue/30 hover:shadow-md">
                    <div class="grid aspect-video place-items-center rounded-md bg-slate-100 text-sm font-bold text-rocha-blue">{{ strtoupper(substr($brand->name, 0, 2)) }}</div>
                    <p class="mt-3 font-bold text-slate-950">{{ $brand->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $brand->description }}</p>
                </div>
            @endforeach
        </div>
    </section>

@endsection
