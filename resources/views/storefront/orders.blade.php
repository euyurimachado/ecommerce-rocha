@extends('layouts.storefront')

@section('title', 'Meus pedidos | Rocha Sports')
@section('meta_description', 'Consulte seus pedidos, status de entrega e retirada na Rocha Sports.')

@section('content')
    <section class="mx-auto max-w-5xl px-4 py-8 lg:px-6">
        <div class="grid gap-6 lg:grid-cols-[0.9fr_1.4fr]">
            <aside class="h-fit rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-rocha-blue">Histórico de pedidos</p>
                <h1 class="mt-2 text-2xl font-bold leading-snug text-slate-950 md:text-3xl">Acompanhe suas compras</h1>
                <p class="mt-3 text-sm text-slate-600">
                    Informe o mesmo e-mail ou telefone usado no checkout para consultar os últimos pedidos feitos na Rocha Sports.
                </p>

                <form action="{{ route('orders.index') }}" method="GET" class="mt-5 space-y-3">
                    <label class="block">
                        <span class="text-sm font-bold text-slate-700">E-mail ou telefone</span>
                        <input name="contato" value="{{ $contact }}" class="mt-2 h-12 w-full rounded-lg border border-slate-200 px-4 outline-none transition focus:border-rocha-blue" type="text" inputmode="email" autocomplete="email" placeholder="voce@email.com ou 22999990000">
                    </label>

                    <button class="flex w-full items-center justify-center gap-2 rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white transition hover:bg-rocha-blue-dark" type="submit">
                        <x-rocha-icon name="search" class="size-5" />
                        Consultar pedidos
                    </button>
                </form>
            </aside>

            <div>
                @if ($contact === '')
                    <div class="rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
                        <div class="mx-auto grid size-14 place-items-center rounded-lg bg-rocha-blue/10 text-rocha-blue">
                            <x-rocha-icon name="package" class="size-7" />
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-slate-950 md:text-xl">Consulte seu histórico</h2>
                        <p class="mt-2 text-sm text-slate-600">Os pedidos aparecem aqui depois que você informa seu contato.</p>
                    </div>
                @elseif ($orders->isEmpty())
                    <div class="rounded-lg border border-slate-200 bg-white p-6 text-center shadow-sm">
                        <div class="mx-auto grid size-14 place-items-center rounded-lg bg-slate-100 text-slate-500">
                            <x-rocha-icon name="search" class="size-7" />
                        </div>
                        <h2 class="mt-4 text-lg font-bold text-slate-950 md:text-xl">Nenhum pedido encontrado</h2>
                        <p class="mt-2 text-sm text-slate-600">Confira o contato informado ou faça uma nova compra na loja.</p>
                        <a href="{{ route('home') }}" class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white transition hover:bg-rocha-blue-dark">Voltar para a loja</a>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($orders as $order)
                            <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-bold text-rocha-blue">Pedido {{ $order->code }}</p>
                                        <h2 class="mt-1 text-lg font-bold text-slate-950 md:text-xl">{{ $order->status_label }}</h2>
                                        <p class="mt-1 text-sm text-slate-500">{{ $order->created_at->format('d/m/Y H:i') }} - {{ $order->fulfillment_method_label }}</p>
                                    </div>
                                    <p class="rounded-lg bg-slate-50 px-4 py-2 text-sm font-bold text-slate-950">{{ $order->formatted_total }}</p>
                                </div>

                                <div class="mt-4 space-y-2">
                                    @foreach ($order->items->take(3) as $item)
                                        <div class="flex justify-between gap-4 text-sm text-slate-600">
                                            <span>{{ $item->quantity }}x {{ $item->product_name }}</span>
                                            <span class="font-bold text-slate-950">R$ {{ number_format($item->line_total_cents / 100, 2, ',', '.') }}</span>
                                        </div>
                                    @endforeach
                                </div>

                                <a href="{{ route('orders.status', ['order' => $order->code]) }}" class="mt-5 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-bold text-slate-700 transition hover:border-rocha-blue/30 hover:bg-rocha-blue/5 hover:text-rocha-blue">
                                    Ver detalhes
                                    <x-rocha-icon name="chevron-right" class="size-4" />
                                </a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endsection
