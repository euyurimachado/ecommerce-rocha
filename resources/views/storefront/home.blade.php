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
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-4 lg:px-6">
            <div class="flex gap-3 overflow-x-auto pb-1">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="flex min-w-fit items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-bold text-slate-800">
                        <span class="grid size-8 place-items-center rounded-md bg-sky-600 text-xs text-white">{{ $category->icon }}</span>
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="bg-slate-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 md:grid-cols-[1.5fr_0.8fr] lg:px-6 lg:py-10">
            <div class="rounded-lg border border-white/10 bg-slate-900 p-6 shadow-xl md:p-8">
                <p class="text-sm font-bold text-sky-300">Rocha Sports em Campos dos Goytacazes</p>
                <h1 class="mt-3 max-w-2xl text-3xl font-black leading-tight md:text-5xl">
                    {{ $banner?->title ?? 'Suplementos originais com entrega rápida em Campos' }}
                </h1>
                <p class="mt-4 max-w-2xl text-base text-slate-300 md:text-lg">
                    {{ $banner?->subtitle ?? 'Whey, creatina, pré-treino e combos selecionados por especialistas Rocha Sports.' }}
                </p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="#ofertas" class="rounded-lg bg-sky-500 px-5 py-3 text-sm font-black text-white transition hover:bg-sky-400">Ver ofertas</a>
                    <a href="#mais-pedidos" class="rounded-lg border border-white/15 px-5 py-3 text-sm font-black text-white">Mais pedidos</a>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 md:grid-cols-1">
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="font-black">Pagamento seguro</p>
                    <p class="mt-1 text-sm text-slate-300">Pix, cartão e checkout protegido.</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="font-black">Entrega rápida</p>
                    <p class="mt-1 text-sm text-slate-300">Operação local em Campos.</p>
                </div>
                <div class="rounded-lg border border-white/10 bg-white/5 p-4">
                    <p class="font-black">Produtos originais</p>
                    <p class="mt-1 text-sm text-slate-300">Curadoria e suporte especializado.</p>
                </div>
            </div>
        </div>
    </section>

    <section id="mais-pedidos" class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-slate-950">Mais pedidos</h2>
                <p class="mt-1 text-sm text-slate-600">Produtos que mais saem na Rocha Sports.</p>
            </div>
            <a href="#" class="text-sm font-bold text-sky-700">Ver todos</a>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($bestSellers as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>

    <section id="ofertas" class="bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-950">Ofertas para você</h2>
                    <p class="mt-1 text-sm text-slate-600">Promoções, combos e itens com alto giro.</p>
                </div>
                <a href="#" class="text-sm font-bold text-sky-700">Ver ofertas</a>
            </div>

            <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                @foreach ($offers as $product)
                    @include('storefront.partials.product-card', ['product' => $product])
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <h2 class="text-2xl font-black text-slate-950">Marcas parceiras</h2>
        <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-5">
            @foreach ($brands as $brand)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid aspect-video place-items-center rounded-md bg-slate-100 text-sm font-black text-sky-700">{{ strtoupper(substr($brand->name, 0, 2)) }}</div>
                    <p class="mt-3 font-bold text-slate-950">{{ $brand->name }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $brand->description }}</p>
                </div>
            @endforeach
        </div>
    </section>

@endsection
