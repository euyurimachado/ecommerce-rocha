<?php

namespace App\Support\Checkout;

class ShippingCalculator
{
    public function calculate(string $fulfillmentMethod, int $subtotalCents): int
    {
        if ($fulfillmentMethod === 'pickup') {
            return 0;
        }

        $threshold = (int) config('commerce.shipping.free_shipping_threshold_cents', 0);

        if ($threshold > 0 && $subtotalCents >= $threshold) {
            return 0;
        }

        return (int) config('commerce.shipping.local_delivery_fee_cents', 0);
    }

    public function formatted(int $shippingCents): string
    {
        if ($shippingCents === 0) {
            return 'Grátis';
        }

        return 'R$ '.number_format($shippingCents / 100, 2, ',', '.');
    }
}
