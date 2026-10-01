<?php

namespace App\Support\Payments\MercadoPago;

use App\Models\IntegrationSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MercadoPagoClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function getOrder(string $orderId): array
    {
        $response = $this->request()->get("/v1/orders/{$orderId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pedido de pagamento no Mercado Pago.');
        }

        return $response->json();
    }

    public function getPayment(string $paymentId): array
    {
        $response = $this->request()->get("/v1/payments/{$paymentId}");

        if ($response->failed()) {
            throw new RuntimeException('Não foi possível consultar o pagamento no Mercado Pago.');
        }

        return $response->json();
    }

    private function request(): PendingRequest
    {
        $integration = IntegrationSetting::active('payment');
        $accessToken = $integration?->provider === 'mercado_pago'
            ? $integration->credential('access_token')
            : config('services.mercado_pago.access_token');

        if (! $accessToken) {
            throw new RuntimeException('MERCADO_PAGO_ACCESS_TOKEN não configurado.');
        }

        return Http::baseUrl(self::BASE_URL)
            ->acceptJson()
            ->asJson()
            ->withToken($accessToken)
            ->timeout(20);
    }
}
