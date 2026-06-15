<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rocha Sports | Suplementos em Campos dos Goytacazes')</title>
    <meta name="description" content="@yield('meta_description', 'Suplementos originais com entrega rápida em Campos dos Goytacazes, RJ. Whey, creatina, pré-treino, vitaminas e combos na Rocha Sports.')">
    <meta name="theme-color" content="#0098d7">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="Rocha Sports">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="{{ asset('images/pwa-icon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('images/pwa-icon.svg') }}">
    <meta property="og:title" content="@yield('title', 'Rocha Sports')">
    <meta property="og:description" content="@yield('meta_description', 'Suplementos originais com entrega rápida em Campos dos Goytacazes.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('schema')
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <div class="min-h-screen pb-24 lg:pb-0">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 lg:px-6">
                <a href="{{ route('home') }}" class="flex min-w-fit items-center" aria-label="Rocha Sports">
                    <img class="h-11 w-auto max-w-[9.5rem] object-contain md:h-12 md:max-w-[12rem]" src="{{ asset('images/logo-rocha-sports.webp') }}" alt="Rocha Sports" width="280" height="80">
                </a>

                <form action="{{ route('search') }}" method="GET" class="hidden flex-1 md:block">
                    <label class="sr-only" for="site-search">Buscar</label>
                    <div class="flex h-12 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 shadow-inner transition focus-within:border-rocha-blue focus-within:bg-white focus-within:ring-4 focus-within:ring-rocha-blue/10">
                        <x-rocha-icon name="search" class="size-5 text-rocha-blue" />
                        <input id="site-search" name="q" value="{{ request('q') }}" class="w-full bg-transparent text-sm outline-none" type="search" placeholder="Buscar suplementos, marcas e lojas">
                    </div>
                </form>

                <div class="hidden items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 lg:flex">
                    <x-rocha-icon name="map-pin" class="size-4 text-rocha-blue" />
                    <span>Entrega em Campos dos Goytacazes, RJ</span>
                </div>

                <nav class="ml-auto flex items-center gap-2">
                    <a class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue" href="#" aria-label="Conta">
                        <x-rocha-icon name="user" class="size-5" />
                    </a>
                    <livewire:favorites.favorite-badge />
                    <livewire:cart.cart-badge />
                </nav>
            </div>

            <div class="mx-auto px-4 pb-3 md:hidden">
                <form action="{{ route('search') }}" method="GET" class="flex h-11 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 focus-within:border-rocha-blue focus-within:bg-white focus-within:ring-4 focus-within:ring-rocha-blue/10">
                    <x-rocha-icon name="search" class="size-5 text-rocha-blue" />
                    <input name="q" value="{{ request('q') }}" class="w-full bg-transparent text-sm outline-none" type="search" placeholder="Buscar suplementos, marcas e lojas">
                </form>
                <p class="mt-2 flex items-center gap-1.5 text-xs font-medium text-slate-600"><x-rocha-icon name="map-pin" class="size-3.5 text-rocha-blue" /> Entrega em Campos dos Goytacazes, RJ</p>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm text-slate-600 md:grid-cols-4 lg:px-6">
                <div>
                    <img class="h-12 w-auto object-contain" src="{{ asset('images/logo-rocha-sports.webp') }}" alt="Rocha Sports" width="280" height="80">
                    <p class="mt-2">Suplementos originais, entrega local e atendimento especializado em Campos dos Goytacazes.</p>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Compra segura</p>
                    <ul class="mt-2 space-y-1">
                        <li class="flex items-center gap-2"><x-rocha-icon name="credit-card" class="size-4 text-rocha-blue" />Pagamento seguro</li>
                        <li class="flex items-center gap-2"><x-rocha-icon name="badge-check" class="size-4 text-rocha-blue" />Produtos originais</li>
                        <li class="flex items-center gap-2"><x-rocha-icon name="store" class="size-4 text-rocha-blue" />Retirada na loja</li>
                    </ul>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Políticas</p>
                    <ul class="mt-2 space-y-1">
                        <li><a class="inline-flex items-center gap-2 hover:text-rocha-blue" href="{{ route('legal.privacy') }}"><x-rocha-icon name="shield-check" class="size-4" />Privacidade</a></li>
                        <li><a class="inline-flex items-center gap-2 hover:text-rocha-blue" href="{{ route('legal.cookies') }}"><x-rocha-icon name="cookie" class="size-4" />Cookies</a></li>
                        <li class="flex items-center gap-2"><x-rocha-icon name="package" class="size-4" />Trocas e devoluções</li>
                        <li><button class="inline-flex items-center gap-2 font-semibold text-rocha-blue" type="button" data-cookie-preferences-open><x-rocha-icon name="cookie" class="size-4" />Preferências de cookies</button></li>
                    </ul>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Atendimento</p>
                    <p class="mt-2 flex items-center gap-2"><x-rocha-icon name="message-circle" class="size-4 text-rocha-blue" />WhatsApp, retirada e entrega local.</p>
                </div>
            </div>
        </footer>
    </div>

    <livewire:cart.sticky-cart />
    @include('partials.cookie-consent')

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white px-2 py-2 md:hidden">
        <div class="mx-auto grid max-w-md grid-cols-4 text-center text-[11px] font-medium leading-tight text-slate-600">
            <a class="flex flex-col items-center gap-1 rounded-lg px-1.5 py-2 text-rocha-blue" href="{{ route('home') }}"><x-rocha-icon name="home" class="size-5" />Início</a>
            <a class="flex flex-col items-center gap-1 rounded-lg px-1.5 py-2" href="{{ route('search', ['ordenar' => 'ofertas']) }}"><x-rocha-icon name="tag" class="size-5" />Ofertas</a>
            <a class="flex flex-col items-center gap-1 rounded-lg px-1.5 py-2" href="{{ route('orders.index') }}"><x-rocha-icon name="package" class="size-5" />Pedidos</a>
            <a class="flex flex-col items-center gap-1 rounded-lg px-1.5 py-2" href="{{ route('favorites.index') }}"><x-rocha-icon name="heart" class="size-5" />Favoritos</a>
        </div>
    </nav>

    @livewireScripts
</body>
</html>
