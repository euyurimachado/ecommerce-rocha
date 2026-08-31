<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:24px 12px"><tr><td align="center">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#fff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#075985;padding:24px;color:#fff;font-size:22px;font-weight:700">Rocha Sports</td></tr>
        <tr><td style="padding:28px">
            <p style="margin:0 0 8px;color:#64748b;font-size:14px">Pedido {{ $order->code }}</p>
            <h1 style="margin:0 0 18px;font-size:26px">{{ $headline }}</h1>
            <p style="margin:0 0 16px;line-height:1.6">Olá, {{ $order->customer_name }}.</p>
            <p style="margin:0 0 24px;line-height:1.6">{{ $bodyText }}</p>
            <p style="margin:0 0 24px"><strong>Estado atual:</strong> {{ $order->status_label }}<br><strong>Total:</strong> {{ $order->formatted_total }}</p>
            <a href="{{ route('orders.status', ['order' => $order->code]) }}" style="display:inline-block;background:#075985;color:#fff;text-decoration:none;font-weight:700;padding:13px 18px;border-radius:8px">Acompanhar pedido</a>
        </td></tr>
    </table>
</td></tr></table>
</body>
</html>
