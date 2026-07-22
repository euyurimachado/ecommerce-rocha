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
                "availability": "https://schema.org/{{ $product->availableQuantityForSelections() > 0 ? 'InStock' : 'OutOfStock' }}"
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
    @php
        $galleryImages = collect($product->galleryImageUrls());
        $variationGroups = collect($product->variationOptions());
        $defaultVariantSelections = $variationGroups
            ->mapWithKeys(fn (array $variation): array => [$variation['name'] => $variation['values'][0] ?? ''])
            ->filter()
            ->all();
        $displayPriceCents = $product->priceCentsForSelections($defaultVariantSelections);
        $displayCompareAtPriceCents = $product->compareAtPriceCentsForSelections($defaultVariantSelections);
        $defaultAvailableQuantity = $product->availableQuantityForSelections($defaultVariantSelections);
        $hasDiscount = $displayCompareAtPriceCents && $displayCompareAtPriceCents > $displayPriceCents;
        $discountPercentage = $hasDiscount ? round((1 - ($displayPriceCents / $displayCompareAtPriceCents)) * 100) : null;
    @endphp

    <section class="bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-4 md:grid-cols-[minmax(0,1fr)_24rem] md:py-8 lg:px-6">
            <div class="min-w-0">
                <div class="relative">
                    <div class="absolute inset-x-0 top-3 z-20 flex items-center justify-between px-3 md:hidden">
                        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="grid size-11 place-items-center rounded-full bg-white/92 text-slate-900 shadow-sm ring-1 ring-slate-200" aria-label="Voltar">
                            <x-rocha-icon name="arrow-left" class="size-5" />
                        </a>
                        <div class="flex items-center gap-2">
                            <livewire:favorites.favorite-toggle :product="$product" :compact="true" :key="'favorite-product-mobile-'.$product->id" />
                            <button class="grid size-11 place-items-center rounded-full bg-white/92 text-slate-700 shadow-sm ring-1 ring-slate-200 transition hover:text-rocha-blue" type="button" aria-label="Compartilhar produto" data-share-product>
                                <x-rocha-icon name="share-2" class="size-5" />
                            </button>
                            <livewire:cart.cart-badge />
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg bg-slate-100 md:rounded-xl">
                        <div class="grid aspect-square place-items-center p-6 md:aspect-[1.05/1] md:p-10">
                            <img class="max-h-full w-full max-w-xl object-contain" src="{{ $galleryImages->first() }}" alt="{{ $product->name }}" data-product-main-image>
                        </div>
                    </div>
                </div>

                <div class="mt-4 flex gap-3 overflow-x-auto pb-1" data-product-gallery>
                    @foreach ($galleryImages as $image)
                        <button class="{{ $loop->first ? 'border-rocha-blue ring-2 ring-rocha-blue/20' : 'border-slate-200' }} grid size-16 shrink-0 place-items-center overflow-hidden rounded-lg border bg-white p-1 transition hover:border-rocha-blue md:size-20" type="button" data-product-gallery-thumb="{{ $image }}" aria-label="Ver imagem {{ $loop->iteration }}">
                            <img class="h-full w-full object-contain" src="{{ $image }}" alt="">
                        </button>
                    @endforeach
                </div>

                <div class="mt-6 md:hidden">
                    <p class="text-xs font-bold uppercase text-rocha-blue">{{ $product->brand?->name ?? $product->category->name }}</p>
                    <h1 class="mt-2 text-xl font-bold leading-snug text-slate-950">{{ $product->name }}</h1>
                </div>
            </div>

            <aside class="md:sticky md:top-24 md:self-start">
                <div class="hidden items-center justify-between gap-3 md:flex">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 transition hover:text-rocha-blue">
                        <x-rocha-icon name="arrow-left" class="size-4" />
                        Voltar
                    </a>
                    <div class="flex items-center gap-2">
                        <livewire:favorites.favorite-toggle :product="$product" :compact="true" :key="'favorite-product-desktop-'.$product->id" />
                        <button class="grid size-10 place-items-center rounded-lg border border-slate-200 bg-white text-slate-600 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue" type="button" aria-label="Compartilhar produto" data-share-product>
                            <x-rocha-icon name="share-2" class="size-5" />
                        </button>
                    </div>
                </div>

                <div class="mt-0 rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:mt-4">
                    <p class="hidden text-xs font-bold uppercase text-rocha-blue md:block">{{ $product->brand?->name ?? $product->category->name }}</p>
                    <h1 class="hidden text-3xl font-bold leading-tight text-slate-950 md:mt-2 md:block">{{ $product->name }}</h1>

                    <div class="mt-3 flex flex-wrap items-center gap-3 text-sm">
                        <span class="inline-flex items-center gap-1 font-bold text-slate-700">
                            <x-rocha-icon name="star" class="size-4 text-rocha-blue" />
                            {{ $product->rating }}
                        </span>
                        <span class="text-slate-400">|</span>
                        <a href="{{ route('categories.show', $product->category) }}" class="font-bold text-rocha-blue">{{ $product->category->name }}</a>
                        @if ($discountPercentage)
                            <span class="rounded-md bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">{{ $discountPercentage }}% OFF</span>
                        @endif
                    </div>

                    <div class="mt-5">
                        <p class="{{ $hasDiscount ? '' : 'hidden' }} text-sm text-slate-400 line-through" data-product-compare-price>
                            {{ $hasDiscount ? $product->formattedCompareAtPriceForSelections($defaultVariantSelections) : '' }}
                        </p>
                        <p class="text-2xl font-bold text-slate-950 md:text-3xl" data-product-price>{{ $product->formattedPriceForSelections($defaultVariantSelections) }}</p>
                        <p class="mt-2 text-sm font-semibold {{ $defaultAvailableQuantity > 0 ? 'text-emerald-700' : 'text-rose-700' }}" data-product-stock>
                            {{ $defaultAvailableQuantity > 0 ? $defaultAvailableQuantity.' unidade(s) em estoque' : 'Produto sem estoque' }}
                        </p>
                    </div>

                    @if ($variationGroups->isNotEmpty())
                        <div
                            class="mt-6 space-y-5"
                            data-product-variations
                            data-base-price="{{ $product->formatted_price }}"
                            data-base-compare-price="{{ $product->formatted_compare_at_price }}"
                            data-base-stock="{{ $product->stock_quantity }}"
                        >
                            @foreach ($variationGroups as $variation)
                                <div>
                                    <p class="text-sm font-bold text-slate-950">{{ $variation['name'] }}</p>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($variation['options'] as $option)
                                            <button
                                                class="{{ $loop->first ? 'border-rocha-blue bg-rocha-blue/5 text-rocha-blue' : 'border-slate-200 bg-white text-slate-600' }} rounded-full border px-4 py-2 text-sm font-semibold transition hover:border-rocha-blue hover:text-rocha-blue"
                                                type="button"
                                                data-product-variation-option
                                                data-variation-name="{{ $variation['name'] }}"
                                                data-variation-value="{{ $option['value'] }}"
                                                data-variation-price="{{ $product->formattedPriceForSelections([$variation['name'] => $option['value']]) }}"
                                                data-variation-compare-price="{{ $product->formattedCompareAtPriceForSelections([$variation['name'] => $option['value']]) }}"
                                                data-variation-has-price="{{ $option['price_cents'] !== null ? 'true' : 'false' }}"
                                                data-variation-has-compare-price="{{ $option['compare_at_price_cents'] !== null ? 'true' : 'false' }}"
                                                data-variation-stock="{{ $option['stock_quantity'] ?? '' }}"
                                                @if ($option['image_url'])
                                                    data-variation-image="{{ $option['image_url'] }}"
                                                @endif
                                                aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                            >
                                                {{ $option['value'] }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        @if ($product->weight)
                            <div class="mt-6">
                                <p class="text-sm font-bold text-slate-950">Tamanho</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <button class="rounded-full border border-rocha-blue bg-rocha-blue/5 px-4 py-2 text-sm font-semibold text-rocha-blue" type="button">{{ $product->weight }}</button>
                                </div>
                            </div>
                        @endif
                    @endif

                    <div class="mt-6 hidden gap-3 md:grid">
                        <livewire:cart.add-to-cart-button :product="$product" label="Comprar agora" :full-width="true" :redirect-to-checkout="true" :key="'buy-product-page-'.$product->id" />
                        <livewire:cart.add-to-cart-button :product="$product" label="Adicionar ao carrinho" :full-width="true" :key="'add-product-page-'.$product->id" />
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-28 md:pb-10 lg:px-6">
        <div class="grid gap-6 md:grid-cols-[minmax(0,1fr)_24rem]">
            <article class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-950 md:text-xl">Descrição</h2>
                @php
                    $description = $product->description ?: $product->short_description;
                    $descriptionHasHtml = $description && $description !== strip_tags($description);
                @endphp
                <div class="mt-3 text-slate-600 [&_a]:font-semibold [&_a]:text-rocha-blue [&_blockquote]:border-l-4 [&_blockquote]:border-rocha-blue/30 [&_blockquote]:pl-4 [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mt-5 [&_h3]:text-lg [&_h3]:font-bold [&_img]:my-5 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg [&_li]:ml-5 [&_ol]:list-decimal [&_p]:my-3 [&_p]:leading-relaxed [&_strong]:font-bold [&_ul]:list-disc">
                    @if ($descriptionHasHtml)
                        {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($description)
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsVisibility('public')
                            ->toHtml() !!}
                    @else
                        <p class="whitespace-pre-line">{{ $description }}</p>
                    @endif
                </div>

                @if ($product->benefits)
                    <h3 class="mt-7 font-bold text-slate-950">Visão rápida</h3>
                    <ul class="mt-3 grid gap-3 text-sm text-slate-700 sm:grid-cols-2">
                        @foreach ($product->benefits as $benefit)
                            <li class="flex items-center gap-3 rounded-lg bg-slate-50 p-3">
                                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-rocha-blue/10 text-rocha-blue">
                                    <x-rocha-icon name="badge-check" class="size-4" />
                                </span>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <aside class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="text-lg font-bold text-slate-950 md:text-xl">Detalhes</h2>
                <dl class="mt-4 grid gap-3 text-sm">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Marca</dt>
                        <dd class="font-bold text-slate-900">{{ $product->brand?->name ?? 'Rocha Sports' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <dt class="text-slate-500">Categoria</dt>
                        <dd class="font-bold text-slate-900">{{ $product->category->name }}</dd>
                    </div>
                </dl>

                @if ($product->usage_instructions)
                    <h3 class="mt-7 font-bold text-slate-950">Modo de uso</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $product->usage_instructions }}</p>
                @endif

                @if ($product->ingredients)
                    <h3 class="mt-7 font-bold text-slate-950">Ingredientes</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $product->ingredients }}</p>
                @endif

                @if ($product->nutrition_facts)
                    <h3 class="mt-7 font-bold text-slate-950">Tabela nutricional</h3>
                    @if ($product->serving_size)
                        <p class="mt-2 text-sm font-semibold text-slate-600">Porção: {{ $product->serving_size }}</p>
                    @endif
                    <dl class="mt-3 overflow-hidden rounded-lg border border-slate-200 text-sm">
                        @foreach ($product->nutrition_facts as $nutrient => $amount)
                            <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-3 py-2 last:border-b-0">
                                <dt class="text-slate-600">{{ $nutrient }}</dt>
                                <dd class="font-bold text-slate-950">{{ $amount }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif

                @if ($product->allergen_info)
                    <h3 class="mt-7 font-bold text-slate-950">Alergênicos</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $product->allergen_info }}</p>
                @endif

            </aside>
        </div>
    </section>

    <div class="fixed inset-x-0 bottom-16 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-12px_28px_rgba(15,23,42,0.12)] backdrop-blur md:hidden">
        <div class="mx-auto grid max-w-md grid-cols-2 gap-3">
            <livewire:cart.add-to-cart-button :product="$product" label="Comprar agora" :full-width="true" :redirect-to-checkout="true" :key="'buy-product-page-mobile-'.$product->id" />
            <livewire:cart.add-to-cart-button :product="$product" label="Adicionar" :full-width="true" :key="'add-product-page-mobile-'.$product->id" />
        </div>
    </div>
@endsection
