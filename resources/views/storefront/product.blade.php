@extends('layouts.storefront')

@section('title', $product->meta_title ?? $product->name.' | Rocha Sports')
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('schema')
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "Product",
            "name": "{{ $product->name }}",
            "brand": "{{ $product->brand?->name }}",
            "description": "{{ $product->short_description }}",
            "offers": {
                "@@type": "Offer",
                "priceCurrency": "BRL",
                "price": "{{ number_format($product->price_cents / 100, 2, '.', '') }}",
                "availability": "https://schema.org/{{ $product->available_quantity > 0 ? 'InStock' : 'OutOfStock' }}"
            }
        }
    </script>
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
                    "name": "{{ $product->category->name }}",
                    "item": "{{ route('categories.show', $product->category) }}"
                },
                {
                    "@@type": "ListItem",
                    "position": 3,
                    "name": "{{ $product->name }}",
                    "item": "{{ route('products.show', $product) }}"
                }
            ]
        }
    </script>
@endsection

@section('content')
    <section class="mx-auto grid max-w-7xl gap-8 px-4 py-8 md:grid-cols-2 lg:px-6">
        <div class="rounded-lg border border-slate-200 bg-white p-6">
            <div class="grid aspect-square place-items-center rounded-lg bg-slate-100">
                <div class="grid size-44 place-items-center rounded-lg border border-slate-200 bg-white text-center shadow-inner">
                    <span class="text-lg font-black text-rocha-blue">{{ $product->category->icon ?? 'RS' }}</span>
                    <span class="text-sm font-semibold text-slate-500">{{ $product->weight }}</span>
                </div>
            </div>
        </div>

        <div>
            <nav class="text-sm text-slate-500">
                <ol class="flex flex-wrap gap-2">
                    <li><a href="{{ route('home') }}">Início</a></li>
                    <li>/</li>
                    <li><a href="{{ route('categories.show', $product->category) }}">{{ $product->category->name }}</a></li>
                    <li>/</li>
                    <li class="text-slate-700">{{ $product->name }}</li>
                </ol>
            </nav>
            <h1 class="mt-3 text-3xl font-black text-slate-950">{{ $product->name }}</h1>
            <p class="mt-2 text-sm font-bold text-rocha-blue">{{ $product->brand?->name }} - {{ $product->weight }}</p>
            <p class="mt-4 text-slate-600">{{ $product->short_description }}</p>

            <div class="mt-6 rounded-lg border border-slate-200 bg-white p-5">
                @if ($product->formatted_compare_at_price)
                    <p class="text-sm text-slate-400 line-through">{{ $product->formatted_compare_at_price }}</p>
                @endif
                <p class="text-3xl font-black text-slate-950">{{ $product->formatted_price }}</p>
                <p class="mt-2 text-sm font-semibold text-emerald-700">Entrega local ou retirada na loja</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <button class="rounded-lg bg-rocha-blue px-5 py-3 font-black text-white">Comprar agora</button>
                    <livewire:cart.add-to-cart-button :product="$product" label="Adicionar ao carrinho" :full-width="true" :key="'add-product-page-'.$product->id" />
                </div>
                <div class="mt-3">
                    <livewire:favorites.favorite-toggle :product="$product" :key="'favorite-product-page-'.$product->id" />
                </div>
            </div>

            <div class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-lg border border-slate-200 bg-white p-4 font-semibold">Produto original</div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 font-semibold">Pagamento seguro</div>
                <div class="rounded-lg border border-slate-200 bg-white p-4 font-semibold">Suporte especializado</div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-10 lg:px-6">
        <div class="grid gap-6 md:grid-cols-3">
            <article class="rounded-lg border border-slate-200 bg-white p-5 md:col-span-2">
                <h2 class="text-xl font-black">Descrição</h2>
                <p class="mt-3 text-slate-600">{{ $product->description }}</p>
                <h3 class="mt-6 font-black">Benefícios</h3>
                <ul class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                    @foreach ($product->benefits ?? [] as $benefit)
                        <li class="rounded-md bg-slate-50 p-3">{{ $benefit }}</li>
                    @endforeach
                </ul>
            </article>
            <aside class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-xl font-black">Modo de uso</h2>
                <p class="mt-3 text-sm text-slate-600">{{ $product->usage_instructions }}</p>
                <h3 class="mt-6 font-black">Ingredientes</h3>
                <p class="mt-3 text-sm text-slate-600">{{ $product->ingredients }}</p>
            </aside>
        </div>
    </section>
@endsection
