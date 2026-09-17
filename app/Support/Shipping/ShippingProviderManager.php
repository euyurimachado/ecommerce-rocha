<?php

namespace App\Support\Shipping;

use App\Contracts\ShippingProvider;
use App\Models\IntegrationSetting;
use App\Support\Shipping\MelhorEnvio\MelhorEnvioShipping;
use RuntimeException;

class ShippingProviderManager
{
    /** @var array<string, class-string<ShippingProvider>> */
    private array $drivers = [
        'flat_rate' => FlatRateShipping::class,
        'melhor_envio' => MelhorEnvioShipping::class,
    ];

    public function active(): ShippingProvider
    {
        $integration = IntegrationSetting::active('shipping');

        if (! $integration) {
            return new FlatRateShipping;
        }

        return $this->for($integration->provider, $integration);
    }

    public function for(string $provider, ?IntegrationSetting $integration = null): ShippingProvider
    {
        $driver = $this->drivers[$provider] ?? null;

        if (! $driver) {
            throw new RuntimeException("Provider de entrega não registrado: {$provider}.");
        }

        $integration ??= IntegrationSetting::query()
            ->where('type', 'shipping')->where('provider', $provider)->first();

        if ($provider === 'flat_rate' && ! $integration) {
            return new FlatRateShipping;
        }

        if (! $integration) {
            throw new RuntimeException("Provider de entrega não configurado: {$provider}.");
        }

        return app()->make($driver, ['integration' => $integration]);
    }

    public function provider(): string
    {
        return IntegrationSetting::active('shipping')?->provider ?? 'flat_rate';
    }
}
