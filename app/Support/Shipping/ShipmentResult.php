<?php

namespace App\Support\Shipping;

use App\Enums\ShippingStatus;

final readonly class ShipmentResult
{
    public function __construct(
        public string $externalId,
        public ShippingStatus $status,
        public ?string $externalStatus = null,
        public ?string $trackingCode = null,
        public ?string $labelUrl = null,
        public array $metadata = [],
    ) {}
}
