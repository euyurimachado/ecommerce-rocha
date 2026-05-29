@extends('layouts.storefront')

@section('title', 'Pedido '.$order->code.' | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-10 lg:px-6">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-bold text-rocha-blue">Pedido {{ $order->code }}</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">{{ $order->status_label }}</h1>
            <p class="mt-3 text-slate-600">Recebemos seu pedido. A equipe Rocha Sports entrará em contato para confirmar pagamento e {{ $order->fulfillment_method === 'pickup' ? 'retirada' : 'entrega' }}.</p>

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase text-slate-500">Total</p>
                    <p class="mt-1 font-black">{{ $order->formatted_total }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase text-slate-500">Recebimento</p>
                    <p class="mt-1 font-black">{{ $order->fulfillment_method_label }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold uppercase text-slate-500">Pagamento</p>
                    <p class="mt-1 font-black">{{ $order->payment_method_label }}</p>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-xl font-black">Itens do pedido</h2>
            <div class="mt-4 space-y-3">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 p-4 text-sm">
                        <div>
                            <p class="font-black text-slate-950">{{ $item->product_name }}</p>
                            <p class="mt-1 text-slate-500">{{ $item->quantity }} unidade(s)</p>
                        </div>
                        <p class="font-black">R$ {{ number_format($item->line_total_cents / 100, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-black text-white">Voltar para a loja</a>
    </section>
@endsection
