@extends('layouts.storefront')

@section('title', 'Checkout | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 lg:px-6">
        <h1 class="text-3xl font-black">Checkout</h1>
        <livewire:checkout.checkout-page />
    </section>
@endsection
