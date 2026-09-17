@extends('layouts.storefront')

@section('title', 'Favoritos | '.$storeSettings->name)
@section('meta_description', 'Produtos favoritos para recompra rápida na '.$storeSettings->name.'.')

@section('content')
    <section class="mx-auto max-w-7xl px-4 py-8 lg:px-6">
        <livewire:favorites.favorites-page />
    </section>
@endsection
