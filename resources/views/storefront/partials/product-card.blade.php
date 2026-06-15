@php
    $compact = $compact ?? false;
    $productImage = $product->image_path ? asset('storage/'.$product->image_path) : asset('images/products/placeholder.svg');
@endphp

@if ($compact)
    <a href="{{ route('products.show', $product) }}" class="group block h-full overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-rocha-blue/30 hover:shadow-md">
        <div class="aspect-square overflow-hidden bg-slate-100">
            <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy">
        </div>
        <div class="p-3">
            <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-slate-950">{{ $product->name }}</h3>
            <div class="mt-3">
                @if ($product->formatted_compare_at_price)
                    <p class="text-xs text-slate-400 line-through">{{ $product->formatted_compare_at_price }}</p>
                @endif
                <p class="text-base font-bold text-rocha-blue md:text-lg">{{ $product->formatted_price }}</p>
            </div>
        </div>
    </a>
@else
<article class="group flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-rocha-blue/30 hover:shadow-md">
    <div class="relative">
        <div class="absolute right-3 top-3 z-10">
            <livewire:favorites.favorite-toggle :product="$product" :compact="true" :key="'favorite-product-card-'.$product->id" />
        </div>
        <a href="{{ route('products.show', $product) }}" class="block">
            <div class="aspect-square overflow-hidden bg-slate-100">
                <img class="h-full w-full object-cover transition duration-300 group-hover:scale-105" src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy">
            </div>
        </a>
    </div>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="font-semibold text-rocha-blue">{{ $product->brand?->name }}</span>
            <span class="inline-flex items-center gap-1 text-slate-500"><x-rocha-icon name="star" class="size-3.5 text-rocha-blue" />{{ $product->rating }}</span>
        </div>

        <a href="{{ route('products.show', $product) }}" class="mt-2 line-clamp-2 min-h-11 text-sm font-semibold text-slate-950">
            {{ $product->name }}
        </a>

        <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $product->short_description }}</p>

        <div class="mt-auto pt-4">
            @if ($product->formatted_compare_at_price)
                <p class="text-xs text-slate-400 line-through">{{ $product->formatted_compare_at_price }}</p>
            @endif
            <div class="mt-1 flex items-center justify-between gap-3">
                <p class="text-base font-bold text-slate-950 md:text-lg">{{ $product->formatted_price }}</p>
                <livewire:cart.add-to-cart-button :product="$product" :key="'add-product-card-'.$product->id" />
            </div>
            <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><x-rocha-icon name="truck" class="size-3.5" />Entrega rápida ou retirada</p>
        </div>
    </div>
</article>
@endif
