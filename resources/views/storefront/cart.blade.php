@extends('layouts.storefront')

@section('title', 'Carrinho | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-10 lg:px-6">
        <h1 class="text-2xl font-bold md:text-3xl">Carrinho</h1>
        <livewire:cart.cart-page />
    </section>
@endsection
