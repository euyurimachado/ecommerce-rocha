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

    public function active(): ?PaymentGateway
    {
        $integration = $this->activeIntegration();

        return $integration ? $this->for($integration->provider, $integration) : null;
    }

    public function activeIntegration(): ?IntegrationSetting
    {
        return IntegrationSetting::active('payment');
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

    public function provider(): ?string
    {
        return $this->activeIntegration()?->provider;
    }

    public function publicKey(): ?string
    {
        $integration = $this->activeIntegration();

        return $integration?->provider === 'mercado_pago'
            ? $integration->credential('public_key')
            : null;
    }
}
