@extends('layouts.storefront')
@section('storefront_variant', 'product')
@section('title', $product->meta_title ?? $product->name.' | Rocha Sports')
@section('meta_description', $product->meta_description ?? $product->short_description)

@section('schema')
<script type="application/ld+json">{"@@context":"https://schema.org","@@type":"Product","name":"{{ $product->name }}","brand":"{{ $product->brand?->name }}","description":"{{ $product->short_description }}","offers":{"@@type":"Offer","priceCurrency":"BRL","price":"{{ number_format($product->price_cents / 100, 2, '.', '') }}","availability":"https://schema.org/{{ $product->available_quantity > 0 ? 'InStock' : 'OutOfStock' }}"}}</script>
<script type="application/ld+json">{"@@context":"https://schema.org","@@type":"BreadcrumbList","itemListElement":[{"@@type":"ListItem","position":1,"name":"Início","item":"{{ route('home') }}"},{"@@type":"ListItem","position":2,"name":"{{ $product->category->name }}","item":"{{ route('categories.show', $product->category) }}"},{"@@type":"ListItem","position":3,"name":"{{ $product->name }}","item":"{{ route('products.show', $product) }}"}]}</script>
@endsection

@section('content')
@php
    $galleryImages = collect($product->galleryImageUrls());
    $variationGroups = collect($product->variationOptions());
    $defaultVariantSelections = $variationGroups->mapWithKeys(fn (array $variation): array => [$variation['name'] => $variation['values'][0] ?? ''])->filter()->all();
    $displayPriceCents = $product->priceCentsForSelections($defaultVariantSelections);
    $displayCompareAtPriceCents = $product->compareAtPriceCentsForSelections($defaultVariantSelections);
    $displayAvailableQuantity = $product->availableQuantityForSelections($defaultVariantSelections);
    $hasDiscount = $displayCompareAtPriceCents && $displayCompareAtPriceCents > $displayPriceCents;
    $discountPercentage = $hasDiscount ? round((1 - ($displayPriceCents / $displayCompareAtPriceCents)) * 100) : null;
@endphp

<div class="bg-white" data-product-page>
    <div class="relative h-[400px] bg-[#f4f4f4] md:mx-auto md:mt-8 md:grid md:h-auto md:max-w-7xl md:grid-cols-[minmax(0,1fr)_24rem] md:gap-8 md:bg-transparent md:px-4 lg:px-6">
        <div class="relative grid h-full min-w-0 place-items-center overflow-hidden md:aspect-[1.05/1] md:h-auto md:rounded-xl md:bg-slate-100">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('home') }}" class="absolute left-4 top-4 z-20 grid size-9 place-items-center rounded-full bg-white text-slate-900 md:hidden" aria-label="Voltar"><x-rocha-icon name="chevron-left" class="size-4" /></a>
            <div class="scrollbar-hidden flex h-full w-full snap-x snap-mandatory overflow-x-auto overscroll-x-contain md:hidden" data-product-mobile-gallery>
                @foreach ($galleryImages as $image)
                    <div class="grid h-full min-w-full snap-center place-items-center overflow-hidden" data-product-mobile-slide="{{ $image }}">
                        <img class="h-full w-full object-contain" src="{{ $image }}" alt="{{ $loop->first ? $product->name : 'Imagem '.$loop->iteration.' de '.$product->name }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}">
                    </div>
                @endforeach
            </div>
            <img class="hidden h-full w-full object-contain md:block" src="{{ $galleryImages->first() }}" alt="{{ $product->name }}" data-product-main-image>
            @if ($galleryImages->count() > 1)
                <div class="absolute bottom-4 left-1/2 z-20 flex -translate-x-1/2 gap-1.5 md:hidden" aria-label="Posição na galeria">
                    @foreach ($galleryImages as $image)
                        <span class="h-1.5 rounded-full transition-all {{ $loop->first ? 'w-5 bg-rocha-blue' : 'w-1.5 bg-slate-300' }}" data-product-mobile-dot></span>
                    @endforeach
                </div>
            @endif
            <div class="absolute bottom-4 left-4 hidden gap-2 md:flex" data-product-gallery>
                @foreach ($galleryImages as $image)
                    <button class="{{ $loop->first ? 'border-rocha-blue ring-2 ring-rocha-blue/20' : 'border-slate-200' }} grid size-16 place-items-center overflow-hidden rounded-lg border bg-white" type="button" data-product-gallery-thumb="{{ $image }}" aria-label="Ver imagem {{ $loop->iteration }}"><img class="h-full w-full object-cover" src="{{ $image }}" alt=""></button>
                @endforeach
            </div>
        </div>
        <aside class="hidden md:block md:sticky md:top-24 md:self-start">
            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-rocha-blue"><x-rocha-icon name="arrow-left" class="size-4" />Voltar</a>
                <div class="flex gap-2"><livewire:favorites.favorite-toggle :product="$product" :compact="true" :key="'favorite-product-desktop-'.$product->id" /><button class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 hover:text-rocha-blue" type="button" aria-label="Compartilhar produto" data-share-product><x-rocha-icon name="share-2" class="size-5" /></button></div>
            </div>
            <div class="mt-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-bold text-rocha-blue">{{ $product->brand?->name ?? $product->category->name }}</p>
                <h1 class="mt-2 text-3xl font-bold leading-tight">{{ $product->name }}</h1>
                <p class="mt-3 text-sm text-slate-600">{{ $product->short_description }}</p>
                <div class="mt-4 flex items-center gap-2 text-sm"><x-rocha-icon name="star" class="size-4 text-rocha-blue" /><strong>{{ $product->rating }}</strong><span class="text-slate-400">({{ $product->reviews_count }})</span></div>
                <div class="mt-5 flex flex-wrap items-center gap-3"><p class="text-3xl font-bold" data-product-price>{{ $product->formattedPriceForSelections($defaultVariantSelections) }}</p><p class="{{ $hasDiscount ? '' : 'hidden' }} text-sm text-slate-400 line-through" data-product-compare-price>{{ $hasDiscount ? $product->formattedCompareAtPriceForSelections($defaultVariantSelections) : '' }}</p></div>
                <p class="mt-2 text-sm font-semibold {{ $displayAvailableQuantity > 0 ? 'text-emerald-700' : 'text-rose-700' }}" data-product-availability>{{ $displayAvailableQuantity > 0 ? 'Disponível para entrega local ou retirada' : 'Produto indisponível no momento' }}</p>
                <div class="mt-6 grid gap-3"><livewire:cart.add-to-cart-button :product="$product" label="Comprar agora" :full-width="true" :redirect-to-checkout="true" :key="'buy-product-page-'.$product->id" /><livewire:cart.add-to-cart-button :product="$product" label="Adicionar ao carrinho" :full-width="true" :key="'add-product-page-'.$product->id" /></div>
            </div>
        </aside>
    </div>

    <div class="relative z-10 -mt-0.5 rounded-t-2xl bg-white px-5 pb-4 pt-5 md:mx-auto md:mt-5 md:max-w-7xl md:rounded-none md:px-4 md:pt-0 lg:px-6">
        <div class="md:max-w-[calc(100%-26rem)]">
            <div class="md:hidden">
                <h1 class="line-clamp-2 text-[25px] font-bold leading-[1.08]">{{ $product->name }}</h1>
                <p class="mt-2 line-clamp-3 text-base leading-[1.2]">{{ $product->short_description }}</p>
                @if ($product->is_offer || $hasDiscount)<span class="mt-3 inline-flex rounded-full bg-rocha-blue/10 px-3 py-1 text-xs font-bold text-rocha-blue-dark">Promoção</span>@endif
                <div class="mt-4 flex items-center gap-3"><p class="text-lg font-bold" data-product-price>{{ $product->formattedPriceForSelections($defaultVariantSelections) }}</p><p class="{{ $hasDiscount ? '' : 'hidden' }} text-sm text-slate-400 line-through" data-product-compare-price>{{ $hasDiscount ? $product->formattedCompareAtPriceForSelections($defaultVariantSelections) : '' }}</p><span class="{{ $discountPercentage ? '' : 'hidden' }} rounded-full bg-rocha-blue/10 px-2 py-1 text-xs font-bold text-rocha-blue-dark" data-product-discount>{{ $discountPercentage ? '-'.$discountPercentage.'%' : '' }}</span></div>
                <p class="sr-only" data-product-availability>{{ $displayAvailableQuantity > 0 ? 'Disponível' : 'Indisponível' }}</p>
            </div>

            @if ($variationGroups->isNotEmpty())
            <div class="mt-5 border-y border-slate-200 md:mt-0 md:rounded-lg md:border md:p-5" data-product-variations data-base-price="{{ $product->formatted_price }}" data-base-price-cents="{{ $product->price_cents }}" data-base-compare-price="{{ $product->formatted_compare_at_price }}" data-base-compare-price-cents="{{ $product->compare_at_price_cents }}" data-base-available="{{ $product->available_quantity }}">
                @foreach ($variationGroups as $variation)
                <section x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }" class="border-b border-slate-200 last:border-b-0">
                    <button class="flex w-full items-center justify-between py-4 text-left text-base font-bold" type="button" x-on:click="open = !open" :aria-expanded="open.toString()">{{ $variation['name'] }}<x-rocha-icon name="chevron-down" class="size-4 transition-transform" x-bind:class="open && 'rotate-180'" /></button>
                    <div x-show="open">
                    @foreach ($variation['options'] as $option)
                        @php
                            $optionSelections = [$variation['name'] => $option['value']];
                            $optionPriceCents = $product->priceCentsForSelections($optionSelections);
                            $priceDifference = $optionPriceCents - $product->price_cents;
                        @endphp
                        <button class="group flex min-h-[98px] w-full items-center gap-3 border-t border-slate-100 px-4 py-3 text-left md:min-h-0 md:rounded-lg md:border md:px-3" type="button" role="radio" data-product-variation-option data-variation-name="{{ $variation['name'] }}" data-variation-value="{{ $option['value'] }}" data-variation-price="{{ $product->formattedPriceForSelections($optionSelections) }}" data-variation-price-cents="{{ $optionPriceCents }}" data-variation-compare-price="{{ $product->formattedCompareAtPriceForSelections($optionSelections) }}" data-variation-compare-price-cents="{{ $product->compareAtPriceCentsForSelections($optionSelections) }}" data-variation-available="{{ $product->availableQuantityForSelections($optionSelections) }}" data-variation-has-price="{{ $option['price_cents'] !== null ? 'true' : 'false' }}" data-variation-has-compare-price="{{ $option['compare_at_price_cents'] !== null ? 'true' : 'false' }}" data-variation-controls-stock="{{ $option['stock_quantity'] !== null ? 'true' : 'false' }}" @if ($option['image_url']) data-variation-image="{{ $option['image_url'] }}" @endif aria-checked="{{ $loop->first ? 'true' : 'false' }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
                            <span class="min-w-0 flex-1"><span class="block font-medium">{{ $option['value'] }}</span>@if ($priceDifference !== 0)<span class="mt-1 block text-sm text-slate-500">{{ $priceDifference > 0 ? '+' : '-' }}R$ {{ number_format(abs($priceDifference) / 100, 2, ',', '.') }}</span>@endif</span>
                            <span class="grid size-[65px] shrink-0 place-items-center overflow-hidden rounded-lg bg-[#f4f4f4]"><img class="h-full w-full object-cover" src="{{ $option['image_url'] ?: asset('images/products/placeholder.svg') }}" alt="" loading="lazy"></span>
                            <span class="grid size-[18px] shrink-0 place-items-center rounded-full border-2 border-slate-300 group-aria-checked:border-rocha-blue"><span class="size-2 rounded-full bg-transparent group-aria-checked:bg-rocha-blue"></span></span>
                        </button>
                    @endforeach
                    </div>
                </section>
                @endforeach
            </div>
            @endif

            <article class="py-6 md:mt-6 md:rounded-lg md:border md:border-slate-200 md:p-5">
                <h2 class="text-lg font-bold">Descrição</h2>
                @php
                    $description = $product->description ?: $product->short_description;
                    $descriptionHasHtml = $description && $description !== strip_tags($description);
                @endphp
                <div class="mt-3 text-slate-800 [&_a]:font-semibold [&_a]:text-rocha-blue [&_blockquote]:border-l-4 [&_blockquote]:border-rocha-blue/30 [&_blockquote]:pl-4 [&_h2]:mt-6 [&_h2]:text-xl [&_h2]:font-bold [&_h3]:mt-5 [&_h3]:text-lg [&_h3]:font-bold [&_img]:my-5 [&_img]:h-auto [&_img]:max-w-full [&_img]:rounded-lg [&_li]:ml-5 [&_ol]:list-decimal [&_p]:my-3 [&_p]:leading-relaxed [&_strong]:font-bold [&_ul]:list-disc">
                    @if ($descriptionHasHtml)
                        {!! \Filament\Forms\Components\RichEditor\RichContentRenderer::make($description)
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsVisibility('public')
                            ->toHtml() !!}
                    @else
                        <p class="whitespace-pre-line">{{ $description }}</p>
                    @endif
                </div>
                @if ($product->benefits)<h3 class="mt-7 font-bold">Visão rápida</h3><ul class="mt-3 space-y-2 text-sm text-slate-700">@foreach ($product->benefits as $benefit)<li class="flex gap-2"><x-rocha-icon name="badge-check" class="mt-0.5 size-4 text-rocha-blue" />{{ $benefit }}</li>@endforeach</ul>@endif
            </article>
            <aside class="border-t border-slate-200 py-6 md:mt-6 md:rounded-lg md:border md:p-5">
                <h2 class="text-lg font-bold">Detalhes</h2><dl class="mt-4 grid gap-3 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Marca</dt><dd class="font-bold">{{ $product->brand?->name ?? 'Rocha Sports' }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Categoria</dt><dd class="font-bold">{{ $product->category->name }}</dd></div></dl>
                @foreach ([['Modo de uso', $product->usage_instructions], ['Ingredientes', $product->ingredients], ['Alergênicos', $product->allergen_info]] as [$heading, $text])@if ($text)<h3 class="mt-7 font-bold">{{ $heading }}</h3><p class="mt-2 text-sm leading-relaxed text-slate-700">{{ $text }}</p>@endif @endforeach
                @if ($product->nutrition_facts)<h3 class="mt-7 font-bold">Tabela nutricional</h3>@if ($product->serving_size)<p class="mt-2 text-sm">Porção: {{ $product->serving_size }}</p>@endif<dl class="mt-3 text-sm">@foreach ($product->nutrition_facts as $nutrient => $amount)<div class="flex justify-between border-b border-slate-100 py-2"><dt>{{ $nutrient }}</dt><dd class="font-bold">{{ $amount }}</dd></div>@endforeach</dl>@endif
            </aside>
        </div>
    </div>
</div>

<div class="fixed inset-x-0 bottom-0 z-50 bg-white px-4 pt-4 pb-[max(16px,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(15,23,42,0.10)] md:hidden"><div class="mx-auto max-w-md"><livewire:cart.product-purchase-bar :product="$product" :key="'purchase-product-page-mobile-'.$product->id" /></div></div>
@endsection
