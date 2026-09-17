<?php

namespace App\Support\Shipping;

final readonly class ShippingQuoteRequest
{
    /** @param array<int, ShippingItem> $items */
    public function __construct(
        public string $originPostalCode,
        public string $destinationPostalCode,
        public array $items,
        public int $subtotalCents,
    ) {}
}
