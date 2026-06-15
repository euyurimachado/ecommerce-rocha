@extends('layouts.storefront')

@section('title', 'Finalizar compra | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 lg:px-6">
        <h1 class="text-2xl font-bold md:text-3xl">Finalizar compra</h1>
        <livewire:checkout.checkout-page />
    </section>
@endsection
