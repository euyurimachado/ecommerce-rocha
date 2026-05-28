@extends('layouts.storefront')

@section('title', 'Checkout | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-10 lg:px-6">
        <h1 class="text-3xl font-black">Checkout</h1>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="font-black">1. Identificacao</p>
                <p class="mt-2 text-sm text-slate-600">Login opcional ou compra como convidado.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="font-black">2. Entrega ou retirada</p>
                <p class="mt-2 text-sm text-slate-600">Endereco em Campos ou retirada na loja.</p>
            </div>
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <p class="font-black">3. Pagamento</p>
                <p class="mt-2 text-sm text-slate-600">Pix, cartao ou boleto via gateway seguro.</p>
            </div>
        </div>
    </section>
@endsection
