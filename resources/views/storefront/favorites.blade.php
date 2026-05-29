@extends('layouts.storefront')

@section('title', 'Favoritos | Rocha Sports')
@section('meta_description', 'Produtos favoritos para recompra rápida na Rocha Sports.')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <livewire:favorites.favorites-page />
    </section>
@endsection
