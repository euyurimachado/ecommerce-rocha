<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedido {{ $order->code }} - Entrega</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Ubuntu, ui-sans-serif, system-ui, sans-serif;
            font-size: 14px;
            line-height: 1.4;
        }
        .page {
            width: min(760px, 100%);
            margin: 24px auto;
            background: #fff;
            padding: 28px;
            border: 1px solid #e2e8f0;
        }
        .toolbar {
            width: min(760px, 100%);
            margin: 24px auto 0;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        button {
            border: 0;
            border-radius: 6px;
            background: #0098d7;
            color: #fff;
            cursor: pointer;
            font-weight: 700;
            padding: 10px 14px;
        }
        h1, h2, p { margin: 0; }
        h1 { font-size: 24px; }
        h2 {
            margin-bottom: 10px;
            font-size: 13px;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .muted { color: #64748b; }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 18px;
        }
        .code {
            font-size: 18px;
            font-weight: 700;
        }
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 18px;
        }
        .box {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 14px;
        }
        .full { grid-column: 1 / -1; }
        .line { margin-top: 6px; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border-bottom: 1px solid #e2e8f0;
            padding: 9px 6px;
            text-align: left;
            vertical-align: top;
        }
        th {
            color: #475569;
            font-size: 12px;
            text-transform: uppercase;
        }
        .right { text-align: right; }
        .total {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 18px;
            font-weight: 700;
        }
        .signature {
            margin-top: 34px;
            padding-top: 42px;
            border-top: 1px solid #0f172a;
            text-align: center;
        }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .page {
                width: 100%;
                margin: 0;
                border: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir</button>
    </div>

    <main class="page">
        <header class="header">
            <div>
                <h1>Rocha Sports</h1>
                <p class="muted">Separação e entrega de pedido</p>
            </div>
            <div>
                <p class="muted">Pedido</p>
                <p class="code">{{ $order->code }}</p>
                <p class="muted">{{ $order->created_at?->format('d/m/Y H:i') }}</p>
            </div>
        </header>

        <section class="grid">
            <div class="box">
                <h2>Cliente</h2>
                <p><strong>{{ $order->customer_name }}</strong></p>
                <p class="line">{{ $order->customer_phone }}</p>
                <p class="line">{{ $order->customer_email }}</p>
            </div>

            <div class="box">
                <h2>Pagamento</h2>
                <p><strong>{{ $order->payment_method_label }}</strong></p>
                <p class="line">{{ $order->status_label }}</p>
                <p class="line">Total: <strong>{{ $order->formatted_total }}</strong></p>
            </div>

            <div class="box full">
                <h2>{{ $order->fulfillment_method === 'pickup' ? 'Retirada' : 'Endereço de entrega' }}</h2>
                @if ($order->fulfillment_method === 'pickup')
                    <p>Retirada na loja Rocha Sports.</p>
                @else
                    <p>
                        <strong>{{ $order->street }}, {{ $order->number }}</strong>
                        @if ($order->complement)
                            - {{ $order->complement }}
                        @endif
                    </p>
                    <p class="line">{{ $order->neighborhood }} - {{ $order->city }}/{{ $order->state }}</p>
                    <p class="line">CEP: {{ $order->postal_code }}</p>
                @endif
            </div>

            <div class="box full">
                <h2>Itens</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>SKU</th>
                            <th class="right">Qtd.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    <strong>{{ $item->product_name }}</strong>
                                    @if ($item->variant_summary)
                                        <br><span class="muted">{{ $item->variant_summary }}</span>
                                    @endif
                                    @if ($item->brand_name || $item->category_name)
                                        <br><span class="muted">{{ trim(collect([$item->brand_name, $item->category_name])->filter()->implode(' - ')) }}</span>
                                    @endif
                                </td>
                                <td>{{ $item->product_sku }}</td>
                                <td class="right">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($order->notes)
                <div class="box full">
                    <h2>Observações</h2>
                    <p>{{ $order->notes }}</p>
                </div>
            @endif
        </section>

        <div class="signature">
            <p>Assinatura / confirmação de recebimento</p>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
