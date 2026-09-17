<?php

namespace App\Support\Shipping;

use App\Contracts\ShippingProvider;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Support\Integrations\ConnectionResult;
use LogicException;

class FlatRateShipping implements ShippingProvider
{
    public function __construct(private readonly ?IntegrationSetting $integration = null) {}

    public function capabilities(): ShippingCapabilities
    {
        return new ShippingCapabilities;
    }

    public function quote(ShippingQuoteRequest $request): array
    {
        $settings = $this->integration?->settings ?? [];
        $threshold = (int) data_get($settings, 'free_shipping_threshold_cents', config('commerce.shipping.free_shipping_threshold_cents', 0));
        $price = (int) data_get($settings, 'price_cents', config('commerce.shipping.local_delivery_fee_cents', 0));

        if ($threshold > 0 && $request->subtotalCents >= $threshold) {
            $price = 0;
        }

        return [new ShippingQuote(
            provider: 'flat_rate',
            serviceId: 'flat-rate',
            carrier: (string) data_get($settings, 'name', 'Entrega local'),
            serviceName: (string) data_get($settings, 'name', 'Entrega local'),
            priceCents: $price,
            deliveryDays: (int) data_get($settings, 'max_days', 2),
        )];
    }

    public function testConnection(): ConnectionResult
    {
        return ConnectionResult::success('Taxa fixa configurada.');
    }

    public function createShipment(Order $order): ShipmentResult
    {
        throw new LogicException('A taxa fixa não oferece criação de envios.');
    }

    public function purchaseLabel(Order $order): ShipmentResult
    {
        throw new LogicException('A taxa fixa não oferece etiquetas.');
    }

    public function generateLabel(Order $order): ShipmentResult
    {
        throw new LogicException('A taxa fixa não oferece etiquetas.');
    }

    public function tracking(Order $order): ShipmentResult
    {
        throw new LogicException('A taxa fixa não oferece rastreamento automático.');
    }
}
