@extends('layouts.storefront')

@section('title', $category->meta_title ?? $category->name.' | Rocha Sports')
@section('meta_description', $category->meta_description ?? $category->short_description)

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <p class="text-sm font-bold text-sky-700">Categoria</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $category->name }}</h1>
            <p class="mt-3 max-w-2xl text-slate-600">{{ $category->seo_description ?? $category->short_description }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <p class="text-sm font-semibold text-slate-600">{{ $products->total() }} produtos encontrados</p>
            <button class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-bold">Filtros</button>
        </div>
        <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
            @foreach ($products as $product)
                @include('storefront.partials.product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-8">
            {{ $products->links() }}
        </div>
    </section>
@endsection
