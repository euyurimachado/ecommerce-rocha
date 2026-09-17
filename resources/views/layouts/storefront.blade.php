<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $storeSettings->name)</title>
    <meta name="description" content="@yield('meta_description', $storeSettings->slogan)">
    <meta name="theme-color" content="{{ $storeSettings->primary_color }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $storeSettings->short_name }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <link rel="icon" href="{{ $storeSettings->assetUrl('favicon_path', 'images/pwa-icon.svg') }}">
    <link rel="apple-touch-icon" href="{{ $storeSettings->assetUrl('pwa_icon_path', 'images/pwa-icon.svg') }}">
    <meta property="og:title" content="@yield('title', $storeSettings->name)">
    <meta property="og:description" content="@yield('meta_description', $storeSettings->slogan)">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($storeSettings->font_family && $storeSettings->font_family !== 'Ubuntu')
        <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family={{ str_replace(' ', '+', $storeSettings->font_family) }}:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @endif
    <style>:root{--brand-primary:{{ $storeSettings->primary_color ?: '#0098D7' }};--brand-primary-dark:{{ $storeSettings->primary_dark_color ?: '#005D8F' }};--brand-secondary:{{ $storeSettings->secondary_color ?: '#A7A9AC' }};--brand-accent:{{ $storeSettings->accent_color ?: '#F59E0B' }};--store-background:{{ $storeSettings->background_color ?: '#F8FAFC' }};--store-font:'{{ $storeSettings->font_family ?: 'Ubuntu' }}';}</style>
    @livewireStyles
    @yield('schema')
</head>
@php($storefrontVariant = trim($__env->yieldContent('storefront_variant', 'default')))
<body class="{{ $storefrontVariant === 'product' ? 'bg-white' : '' }} text-slate-950 antialiased" style="{{ $storefrontVariant === 'product' ? '' : 'background-color:var(--store-background)' }}">
    <div class="min-h-screen {{ $storefrontVariant === 'home' ? 'pb-24' : ($storefrontVariant === 'product' ? 'pb-24 md:pb-0' : 'pb-24') }} md:pb-0">
        <header class="sticky top-0 z-40 hidden border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur md:block">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 lg:px-6">
                <a href="{{ route('home') }}" class="flex min-w-fit items-center" aria-label="{{ $storeSettings->name }}">
                    <img class="h-11 w-auto max-w-[9.5rem] object-contain md:h-12 md:max-w-[12rem]" src="{{ $storeSettings->assetUrl('logo_path', 'images/logo-rocha-sports.webp') }}" alt="{{ $storeSettings->name }}" width="280" height="80">
                </a>

                <div class="hidden items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 lg:flex">
                    <x-rocha-icon name="map-pin" class="size-4 text-rocha-blue" />
                    <span>Entrega em {{ $storeSettings->city ?: 'sua região' }}{{ $storeSettings->state ? ', '.$storeSettings->state : '' }}</span>
                </div>

                <form action="{{ route('search') }}" method="GET" role="search" class="relative min-w-0 flex-1">
                    <label class="sr-only" for="desktop-storefront-search">Buscar produtos, marcas ou categorias</label>
                    <x-rocha-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-4 -translate-y-1/2 text-slate-500" />
                    <input id="desktop-storefront-search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" type="search" enterkeyhint="search" autocomplete="off" placeholder="O que você está procurando?" class="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 pl-11 pr-4 text-sm outline-none transition focus:border-rocha-blue focus:bg-white focus:ring-2 focus:ring-rocha-blue/20">
                </form>

                <nav class="ml-auto flex items-center gap-2">
                    <a class="grid size-10 place-items-center rounded-lg border border-slate-200 text-slate-600 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue" href="#" aria-label="Conta">
                        <x-rocha-icon name="user" class="size-5" />
                    </a>
                    <livewire:favorites.favorite-badge />
                    <livewire:cart.cart-badge />
                </nav>
            </div>

            <div class="mx-auto px-4 pb-3 md:hidden">
                <p class="flex items-center gap-1.5 text-xs font-medium text-slate-600"><x-rocha-icon name="map-pin" class="size-3.5 text-rocha-blue" /> Entrega em {{ $storeSettings->city ?: 'sua região' }}{{ $storeSettings->state ? ', '.$storeSettings->state : '' }}</p>
            </div>
        </header>

        <header class="flex h-[70px] items-center justify-between bg-white px-4 md:hidden">
            <a href="{{ route('home') }}" aria-label="{{ $storeSettings->name }}">
                <img class="h-10 w-auto max-w-[150px] object-contain" src="{{ $storeSettings->assetUrl('logo_path', 'images/logo-rocha-sports.webp') }}" alt="{{ $storeSettings->name }}" width="280" height="80">
            </a>
            <livewire:cart.cart-badge variant="minimal" />
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="{{ $storefrontVariant === 'product' ? 'hidden md:block' : '' }} border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm text-slate-600 md:grid-cols-4 lg:px-6">
                <div>
                    <img class="h-12 w-auto object-contain" src="{{ $storeSettings->assetUrl('logo_path', 'images/logo-rocha-sports.webp') }}" alt="{{ $storeSettings->name }}" width="280" height="80">
                    <p class="mt-2">{{ $storeSettings->slogan }}</p>
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

    @if ($storefrontVariant !== 'product')
        <livewire:cart.sticky-cart />
    @endif
    @include('partials.cookie-consent')

    @if ($storefrontVariant !== 'product')
        <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white px-2 pt-2 pb-[max(8px,env(safe-area-inset-bottom))] md:hidden" aria-label="Navegação principal">
            <div class="mx-auto grid h-16 max-w-md grid-cols-5 text-center text-[11px] font-medium leading-tight text-slate-600">
                <a class="flex flex-col items-center justify-center gap-1 px-1 {{ request()->routeIs('home') ? 'font-bold text-rocha-blue' : '' }}" href="{{ route('home') }}"><x-rocha-icon name="home" class="size-5" />Início</a>
                <a class="flex flex-col items-center justify-center gap-1 px-1 {{ request()->routeIs('search') && request('ordenar') !== 'ofertas' ? 'font-bold text-rocha-blue' : '' }}" href="{{ route('search') }}"><x-rocha-icon name="search" class="size-5" />Busca</a>
                <a class="flex flex-col items-center justify-center gap-1 px-1 {{ request()->routeIs('search') && request('ordenar') === 'ofertas' ? 'font-bold text-rocha-blue' : '' }}" href="{{ route('search', ['ordenar' => 'ofertas']) }}"><x-rocha-icon name="tag" class="size-5" />Ofertas</a>
                <a class="flex flex-col items-center justify-center gap-1 px-1 {{ request()->routeIs('orders.*') ? 'font-bold text-rocha-blue' : '' }}" href="{{ route('orders.index') }}"><x-rocha-icon name="package" class="size-5" />Pedidos</a>
                <a class="flex flex-col items-center justify-center gap-1 px-1 {{ request()->routeIs('favorites.*') ? 'font-bold text-rocha-blue' : '' }}" href="{{ route('favorites.index') }}"><x-rocha-icon name="heart" class="size-5" />Favoritos</a>
            </div>
        </nav>
    @endif

    @livewireScripts
</body>
</html>
