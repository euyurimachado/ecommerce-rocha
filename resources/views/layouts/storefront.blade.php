<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rocha Sports | Suplementos em Campos dos Goytacazes')</title>
    <meta name="description" content="@yield('meta_description', 'Suplementos originais com entrega rapida em Campos dos Goytacazes, RJ. Whey, creatina, pre-treino, vitaminas e combos na Rocha Sports.')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', 'Rocha Sports')">
    <meta property="og:description" content="@yield('meta_description', 'Suplementos originais com entrega rapida em Campos dos Goytacazes.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('schema')
</head>
<body class="bg-slate-50 text-slate-950 antialiased">
    <div class="min-h-screen pb-24 lg:pb-0">
        <header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center gap-4 px-4 py-3 lg:px-6">
                <a href="{{ route('home') }}" class="flex min-w-fit items-center gap-3" aria-label="Rocha Sports">
                    <span class="grid size-11 place-items-center rounded-lg bg-sky-600 font-black text-white shadow-sm">R</span>
                    <span class="leading-none">
                        <span class="block text-lg font-black text-sky-700">ROCHA</span>
                        <span class="block text-xs font-bold tracking-wide text-slate-500">SPORTS</span>
                    </span>
                </a>

                <form action="#" class="hidden flex-1 md:block">
                    <label class="sr-only" for="site-search">Buscar</label>
                    <div class="flex h-12 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 shadow-inner">
                        <span class="text-slate-400">Buscar</span>
                        <input id="site-search" class="w-full bg-transparent text-sm outline-none" type="search" placeholder="Buscar suplementos, marcas e lojas">
                    </div>
                </form>

                <div class="hidden items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 lg:flex">
                    <span class="text-sky-600">Local</span>
                    <span>Entrega em Campos dos Goytacazes, RJ</span>
                </div>

                <nav class="ml-auto flex items-center gap-2">
                    <a class="grid size-10 place-items-center rounded-lg border border-slate-200 text-sm font-bold text-slate-600" href="#" aria-label="Conta">Conta</a>
                    <livewire:cart.cart-badge />
                </nav>
            </div>

            <div class="mx-auto px-4 pb-3 md:hidden">
                <div class="flex h-11 items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4">
                    <span class="text-slate-400">Buscar</span>
                    <input class="w-full bg-transparent text-sm outline-none" type="search" placeholder="Buscar suplementos, marcas e lojas">
                </div>
                <p class="mt-2 text-xs font-medium text-slate-600">Entrega em Campos dos Goytacazes, RJ</p>
            </div>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 text-sm text-slate-600 md:grid-cols-4 lg:px-6">
                <div>
                    <p class="font-black text-slate-950">Rocha Sports</p>
                    <p class="mt-2">Suplementos originais, entrega local e atendimento especializado em Campos dos Goytacazes.</p>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Compra segura</p>
                    <ul class="mt-2 space-y-1">
                        <li>Pagamento seguro</li>
                        <li>Produtos originais</li>
                        <li>Retirada na loja</li>
                    </ul>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Politicas</p>
                    <ul class="mt-2 space-y-1">
                        <li><a href="{{ route('legal.privacy') }}">Privacidade</a></li>
                        <li><a href="{{ route('legal.cookies') }}">Cookies</a></li>
                        <li>Trocas e devolucoes</li>
                    </ul>
                </div>
                <div>
                    <p class="font-bold text-slate-950">Atendimento</p>
                    <p class="mt-2">WhatsApp, retirada e entrega local.</p>
                </div>
            </div>
        </footer>
    </div>

    <livewire:cart.sticky-cart />

    <nav class="fixed inset-x-0 bottom-0 z-50 border-t border-slate-200 bg-white px-2 py-2 md:hidden">
        <div class="mx-auto grid max-w-md grid-cols-5 text-center text-xs font-semibold text-slate-600">
            <a class="rounded-lg px-2 py-2 text-sky-700" href="{{ route('home') }}">Inicio</a>
            <a class="rounded-lg px-2 py-2" href="#">Busca</a>
            <a class="rounded-lg px-2 py-2" href="#">Ofertas</a>
            <a class="rounded-lg px-2 py-2" href="#">Pedidos</a>
            <a class="rounded-lg px-2 py-2" href="#">Conta</a>
        </div>
    </nav>

    @livewireScripts
</body>
</html>
