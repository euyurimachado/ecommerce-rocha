<?php

namespace App\Support\Payments;

use App\Contracts\PaymentGateway;
use App\Models\IntegrationSetting;
use App\Support\Payments\Asaas\AsaasGateway;
use App\Support\Payments\MercadoPago\MercadoPagoGateway;
use RuntimeException;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private array $drivers = [
        'mercado_pago' => MercadoPagoGateway::class,
        'asaas' => AsaasGateway::class,
    ];

    public function active(): PaymentGateway
    {
        $integration = IntegrationSetting::active('payment');

        if (! $integration) {
            $integration = new IntegrationSetting([
                'type' => 'payment',
                'provider' => 'mercado_pago',
                'enabled' => true,
                'environment' => config('services.mercado_pago.sandbox') ? 'sandbox' : 'production',
                'credentials' => [
                    'access_token' => config('services.mercado_pago.access_token'),
                    'public_key' => config('services.mercado_pago.public_key'),
                    'webhook_secret' => config('services.mercado_pago.webhook_secret'),
                ],
                'settings' => ['statement_descriptor' => config('services.mercado_pago.statement_descriptor')],
            ]);
        }

        return $this->for($integration->provider, $integration);
    }

    public function for(string $provider, ?IntegrationSetting $integration = null): PaymentGateway
    {
        $driver = $this->drivers[$provider] ?? null;

        if (! $driver) {
            throw new RuntimeException("Gateway de pagamento não registrado: {$provider}.");
        }

        $integration ??= IntegrationSetting::query()
            ->where('type', 'payment')
            ->where('provider', $provider)
            ->first();

        if (! $integration) {
            throw new RuntimeException("Gateway de pagamento não configurado: {$provider}.");
        }

        return app()->make($driver, ['integration' => $integration]);
    }

    public function provider(): string
    {
        return IntegrationSetting::active('payment')?->provider ?? 'mercado_pago';
    }
}
