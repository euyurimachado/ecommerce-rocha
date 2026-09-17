<?php

namespace App\Support\Shipping;

final readonly class ShippingCapabilities
{
    public function __construct(
        public bool $quotes = true,
        public bool $shipments = false,
        public bool $labels = false,
        public bool $tracking = false,
        public bool $webhooks = false,
    ) {}
}
