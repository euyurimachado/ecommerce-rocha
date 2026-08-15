@php
    $mobileCarousel = $mobileCarousel ?? false;
    $compactGrid = $compactGrid ?? false;
    $productImage = $product->image_path ? asset('storage/'.$product->image_path) : asset('images/products/placeholder.svg');
    $hasDiscount = $product->compare_at_price_cents && $product->compare_at_price_cents > $product->price_cents;
    $discount = $hasDiscount ? round((1 - ($product->price_cents / $product->compare_at_price_cents)) * 100) : null;
@endphp

<a href="{{ route('products.show', $product) }}" class="group block h-full {{ $mobileCarousel ? 'w-[112px] shrink-0 md:w-auto' : 'w-full min-w-0' }}">
    <div class="grid aspect-square overflow-hidden rounded-lg bg-[#f4f4f4]">
        <img class="h-full w-full object-contain transition duration-300 group-hover:scale-105" src="{{ $productImage }}" alt="{{ $product->name }}" loading="lazy">
    </div>
    <div class="pt-2">
        <p class="{{ $compactGrid || $mobileCarousel ? 'text-sm md:text-[17px]' : 'text-[17px]' }} font-bold leading-tight text-slate-950">{{ $product->formatted_price }}</p>
        @if ($hasDiscount)
            <div class="mt-1 flex flex-wrap items-center gap-1.5 {{ $compactGrid || $mobileCarousel ? 'text-[10px] md:text-[11px]' : 'text-[11px]' }} leading-none">
                <span class="text-slate-400 line-through">{{ $product->formatted_compare_at_price }}</span>
                <span class="rounded-full bg-rocha-blue/10 px-1.5 py-0.5 font-bold text-rocha-blue-dark">-{{ $discount }}%</span>
            </div>
        @endif
        <h3 class="mt-2 line-clamp-2 {{ $compactGrid ? 'min-h-[30px] text-xs font-normal leading-[15px] md:min-h-10 md:text-[15px] md:font-medium md:leading-5' : ($mobileCarousel ? 'min-h-10 text-[13px] font-normal leading-[17px]' : 'min-h-10 text-[15px] font-medium leading-5') }} text-slate-950">{{ $product->name }}</h3>
        <div class="mt-1 flex items-center gap-1 text-xs">
            <x-rocha-icon name="star" class="size-3 text-rocha-blue" />
            <span class="font-bold text-slate-800">{{ $product->rating }}</span>
            <span class="text-slate-400">({{ $product->reviews_count }})</span>
        </div>
    </div>
</a>
