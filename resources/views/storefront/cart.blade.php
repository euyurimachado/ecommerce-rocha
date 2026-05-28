@extends('layouts.storefront')

@section('title', 'Carrinho | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-10 lg:px-6">
        <h1 class="text-3xl font-black">Carrinho</h1>
        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 text-slate-600">
            <p class="font-semibold text-slate-950">Seu carrinho ainda esta vazio.</p>
            <p class="mt-2">Na proxima etapa vamos ligar os botoes de adicionar ao carrinho com Livewire e persistencia por sessao/cliente.</p>
            <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-sky-600 px-5 py-3 font-black text-white">Continuar comprando</a>
        </div>
    </section>
@endsection
