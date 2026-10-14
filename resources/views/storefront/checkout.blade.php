@extends('layouts.storefront')

@section('title', 'Finalizar compra | '.$storeSettings->name)

@section('content')
    @php($checkoutCart = app(\App\Support\Cart\CartManager::class))
    @php($checkoutMetaItems = $checkoutCart->items())
    <section class="mx-auto max-w-5xl px-4 py-10 lg:px-6">
        <h1 class="text-2xl font-bold md:text-3xl">Finalizar compra</h1>
        @if ($checkoutMetaItems->isNotEmpty())
            <div hidden data-meta-initiate-checkout data-value="{{ number_format($checkoutCart->totalCents() / 100, 2, '.', '') }}" data-num-items="{{ $checkoutCart->count() }}" data-content-ids="{{ $checkoutMetaItems->map(fn (array $item) => $item['product']->id)->implode(',') }}" data-currency="BRL"></div>
        @endif
        <livewire:checkout.checkout-page />
    </section>
@endsection
