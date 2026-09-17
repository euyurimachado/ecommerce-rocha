<?php

namespace App\Support\Shipping;

final readonly class ShippingQuote
{
    public function __construct(
        public string $provider,
        public string $serviceId,
        public string $carrier,
        public string $serviceName,
        public int $priceCents,
        public int $deliveryDays,
        public ?int $originalPriceCents = null,
        public ?string $quoteReference = null,
        public array $packages = [],
    ) {}

    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'service_id' => $this->serviceId,
            'carrier' => $this->carrier,
            'service_name' => $this->serviceName,
            'price_cents' => $this->priceCents,
            'delivery_days' => $this->deliveryDays,
            'original_price_cents' => $this->originalPriceCents,
            'quote_reference' => $this->quoteReference,
            'packages' => $this->packages,
        ];
    }
}
