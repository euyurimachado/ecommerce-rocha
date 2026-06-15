<?php

namespace App\Support\Payments\MercadoPago;

use App\Models\Order;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function createPreference(Order $order): array
    {
        $order->loadMissing('items');

        $payload = [
            'items' => $this->itemsPayload($order),
            'payer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => [
                    'number' => preg_replace('/\D+/', '', $order->customer_phone),
                ],
                'address' => array_filter([
                    'zip_code' => $order->postal_code,
                    'street_name' => $order->street,
                    'street_number' => $order->number,
                ]),
            ],
            'external_reference' => $order->code,
            'statement_descriptor' => config('services.mercado_pago.statement_descriptor'),
            'metadata' => [
                'order_code' => $order->code,
                'order_id' => $order->id,
            ],
        ];

        if ($this->canUsePublicCallbacks()) {
            $payload['notification_url'] = route('api.payments.mercado-pago.webhook');
            $payload['back_urls'] = [
                'success' => route('payments.mercado-pago.return', ['order' => $order->code, 'result' => 'success']),
                'pending' => route('payments.mercado-pago.return', ['order' => $order->code, 'result' => 'pending']),
                'failure' => route('payments.mercado-pago.return', ['order' => $order->code, 'result' => 'failure']),
            ];
            $payload['auto_return'] = 'approved';
        }

        $response = $this->request()->post('/checkout/preferences', $payload);

        if ($response->failed()) {
            throw new RuntimeException('Mercado Pago preference error: '.$response->body());
        }

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->request()->get("/v1/payments/{$paymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Mercado Pago payment error: '.$response->body());
        }

        return $response->json();
    }

    public function shouldUseSandboxInitPoint(): bool
    {
        return (bool) config('services.mercado_pago.sandbox');
    }

    private function request(): PendingRequest
    {
        $accessToken = config('services.mercado_pago.access_token');

        if (! $accessToken) {
            throw new RuntimeException('MERCADO_PAGO_ACCESS_TOKEN não configurado.');
        }

        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(20);
    }

    private function canUsePublicCallbacks(): bool
    {
        $appUrl = (string) config('app.url');

        return str_starts_with($appUrl, 'https://')
            && ! str_contains($appUrl, 'localhost')
            && ! str_contains($appUrl, '127.0.0.1');
    }

    private function itemsPayload(Order $order): array
    {
        if ($order->discount_cents > 0) {
            return [[
                'id' => $order->code,
                'title' => "Pedido {$order->code} - Rocha Sports",
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round($order->total_cents / 100, 2),
            ]];
        }

        $items = $order->items
            ->map(fn ($item) => [
                'id' => $item->product_sku,
                'title' => $item->product_name,
                'description' => trim(implode(' - ', array_filter([$item->brand_name, $item->category_name]))),
                'quantity' => $item->quantity,
                'currency_id' => 'BRL',
                'unit_price' => round($item->unit_price_cents / 100, 2),
            ])
            ->values()
            ->all();

        if ($order->shipping_cents > 0) {
            $items[] = [
                'id' => 'shipping',
                'title' => 'Entrega local',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round($order->shipping_cents / 100, 2),
            ];
        }

        return $items;
    }
}
