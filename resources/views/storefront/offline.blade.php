@extends('layouts.storefront')

@section('title', 'Rocha Sports | Você está offline')
@section('meta_description', 'Página offline da Rocha Sports.')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-16 text-center lg:px-6">
        <div class="mx-auto grid size-16 place-items-center rounded-lg bg-rocha-blue/10 text-rocha-blue">
            <x-rocha-icon name="wifi-off" class="size-8" />
        </div>

        <h1 class="mt-6 text-2xl font-bold text-slate-950 md:text-3xl">Você está offline</h1>
        <p class="mt-3 text-slate-600">
            Não conseguimos carregar a loja agora. Verifique sua conexão e tente novamente para continuar comprando na Rocha Sports.
        </p>

        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white transition hover:bg-rocha-blue-dark">
            Tentar novamente
        </a>
    </section>
@endsection
