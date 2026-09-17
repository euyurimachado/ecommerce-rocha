<?php

namespace App\Support\Checkout;

use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\ShippingQuoteRequest;

class ShippingCalculator
{
    public function __construct(private readonly ShippingProviderManager $providers) {}

    public function calculate(string $fulfillmentMethod, int $subtotalCents): int
    {
        if ($fulfillmentMethod === 'pickup') {
            return 0;
        }

        if ($this->providers->provider() === 'flat_rate') {
            $quote = $this->providers->active()->quote(new ShippingQuoteRequest('', '', [], $subtotalCents))[0] ?? null;

            if ($quote) {
                return $quote->priceCents;
            }
        }

        $threshold = (int) config('commerce.shipping.free_shipping_threshold_cents', 0);

        if ($threshold > 0 && $subtotalCents >= $threshold) {
            return 0;
        }

        return (int) config('commerce.shipping.local_delivery_fee_cents', 0);
    }

    public function quotes(ShippingQuoteRequest $request): array
    {
        return $this->providers->active()->quote($request);
    }

    public function formatted(int $shippingCents): string
    {
        if ($shippingCents === 0) {
            return 'Grátis';
        }

        return 'R$ '.number_format($shippingCents / 100, 2, ',', '.');
    }
}
