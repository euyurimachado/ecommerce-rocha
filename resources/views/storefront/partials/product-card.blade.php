<article class="group flex h-full flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-rocha-blue/30 hover:shadow-md">
    <a href="{{ route('products.show', $product) }}" class="block">
        <div class="flex aspect-square items-center justify-center bg-slate-100 p-5">
            <div class="grid size-28 place-items-center rounded-lg border border-slate-200 bg-white text-center shadow-inner transition group-hover:border-rocha-blue/30">
                <span class="text-xs font-bold uppercase text-rocha-blue">{{ $product->category->icon ?? 'RS' }}</span>
                <span class="mt-1 text-[11px] font-semibold text-slate-500">{{ $product->weight }}</span>
            </div>
        </div>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <div class="flex items-center justify-between gap-2 text-xs">
            <span class="font-semibold text-rocha-blue">{{ $product->brand?->name }}</span>
            <span class="inline-flex items-center gap-1 text-slate-500"><x-rocha-icon name="star" class="size-3.5 text-rocha-blue" />{{ $product->rating }}</span>
        </div>

        <a href="{{ route('products.show', $product) }}" class="mt-2 line-clamp-2 min-h-11 text-sm font-bold text-slate-950">
            {{ $product->name }}
        </a>

        <p class="mt-2 line-clamp-2 text-xs text-slate-500">{{ $product->short_description }}</p>

        <div class="mt-auto pt-4">
            @if ($product->formatted_compare_at_price)
                <p class="text-xs text-slate-400 line-through">{{ $product->formatted_compare_at_price }}</p>
            @endif
            <div class="mt-1 flex items-center justify-between gap-3">
                <p class="text-lg font-black text-slate-950">{{ $product->formatted_price }}</p>
                <livewire:cart.add-to-cart-button :product="$product" :key="'add-product-card-'.$product->id" />
            </div>
            <p class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-emerald-700"><x-rocha-icon name="truck" class="size-3.5" />Entrega rápida ou retirada</p>
        </div>
    </div>
</article>
