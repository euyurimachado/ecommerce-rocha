@extends('layouts.storefront')

@section('title', 'Pedido '.$order->code.' | Rocha Sports')

@section('content')
    <section class="mx-auto max-w-4xl px-4 py-10 lg:px-6">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-sm font-bold text-rocha-blue">Pedido {{ $order->code }}</p>
            <h1 class="mt-2 text-2xl font-bold leading-snug text-slate-950 md:text-3xl">Pedido realizado</h1>
            <p class="mt-3 text-slate-600">{{ $order->payment_message }}</p>

            @if ($order->status === 'payment_pending' && ($order->mercado_pago_init_point || $order->mercado_pago_sandbox_init_point))
                <a
                    href="{{ config('services.mercado_pago.sandbox') ? ($order->mercado_pago_sandbox_init_point ?? $order->mercado_pago_init_point) : $order->mercado_pago_init_point }}"
                    class="mt-5 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white transition hover:bg-rocha-blue-dark"
                >
                    Pagar com Mercado Pago
                </a>
            @endif

            @if ($order->payment_method === 'pix' && $order->pix_qr_code)
                <div class="mt-6 rounded-lg border border-rocha-blue/20 bg-rocha-blue/5 p-5 text-center">
                    <h2 class="font-bold text-slate-950">Pague com PIX</h2>
                    @if ($order->pix_qr_code_base64)
                        <img src="data:image/png;base64,{{ $order->pix_qr_code_base64 }}" alt="QR Code PIX do pedido {{ $order->code }}" class="mx-auto mt-4 size-56 max-w-full rounded-lg bg-white p-2">
                    @endif
                    <label class="mt-4 block text-left text-sm font-bold text-slate-700" for="pix-code">PIX Copia e Cola</label>
                    <textarea id="pix-code" readonly class="mt-2 h-24 w-full rounded-lg border border-slate-200 bg-white p-3 text-xs">{{ $order->pix_qr_code }}</textarea>
                    <button type="button" data-copy-pix class="mt-3 rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Copiar código PIX</button>
                    <p class="mt-3 text-sm text-slate-600">O pedido começará a ser preparado após a confirmação do pagamento.</p>
                </div>
            @endif

            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Total</p>
                    <p class="mt-1 font-bold">{{ $order->formatted_total }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Recebimento</p>
                    <p class="mt-1 font-bold">{{ $order->fulfillment_method_label }}</p>
                </div>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs font-bold text-slate-500">Pagamento</p>
                    <p class="mt-1 font-bold">{{ $order->payment_method_label }}</p>
                </div>
            </div>

            @php
                $steps = ['received' => 'Pedido realizado', 'preparing' => 'Em separação', 'out_for_delivery' => 'Enviado', 'delivered' => 'Entregue'];
                $currentStep = match ($order->status) {
                    'preparing' => 1,
                    'out_for_delivery' => 2,
                    'delivered' => 3,
                    default => 0,
                };
            @endphp
            <ol class="mt-6 grid gap-2 sm:grid-cols-4" aria-label="Acompanhamento do pedido">
                @foreach ($steps as $step => $label)
                    @php($stepIndex = array_search($step, array_keys($steps), true))
                    <li class="rounded-lg border p-3 text-sm font-bold {{ $stepIndex <= $currentStep ? 'border-rocha-blue bg-rocha-blue/5 text-rocha-blue' : 'border-slate-200 text-slate-400' }}">
                        {{ $label }}
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold md:text-xl">Itens do pedido</h2>
            <div class="mt-4 space-y-3">
                @foreach ($order->items as $item)
                    <div class="flex items-center justify-between gap-4 rounded-lg bg-slate-50 p-4 text-sm">
                        <div>
                            <p class="font-semibold text-slate-950">{{ $item->product_name }}</p>
                            @if ($item->variant_summary)
                                <p class="mt-1 text-slate-500">{{ $item->variant_summary }}</p>
                            @endif
                            <p class="mt-1 text-slate-500">{{ $item->quantity }} unidade(s)</p>
                        </div>
                        <p class="font-bold">R$ {{ number_format($item->line_total_cents / 100, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <a href="{{ route('home') }}" class="mt-6 inline-flex rounded-lg bg-rocha-blue px-5 py-3 font-bold text-white">Voltar para a loja</a>
    </section>
@endsection
