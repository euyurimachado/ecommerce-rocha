@extends('layouts.storefront')

@section('title', $category->meta_title ?? $category->name.' | Rocha Sports')
@section('meta_description', $category->meta_description ?? $category->short_description)

@section('schema')
    <script type="application/ld+json">
        {
            "@@context": "https://schema.org",
            "@@type": "BreadcrumbList",
            "itemListElement": [
                {
                    "@@type": "ListItem",
                    "position": 1,
                    "name": "Inicio",
                    "item": "{{ route('home') }}"
                },
                {
                    "@@type": "ListItem",
                    "position": 2,
                    "name": "{{ $category->name }}",
                    "item": "{{ route('categories.show', $category) }}"
                }
            ]
        }
    </script>
@endsection

@section('content')
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
            <nav class="mb-4 text-sm text-slate-500">
                <ol class="flex flex-wrap gap-2">
                    <li><a href="{{ route('home') }}">Inicio</a></li>
                    <li>/</li>
                    <li class="text-slate-700">{{ $category->name }}</li>
                </ol>
            </nav>
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
