<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

final readonly class WebhookResult
{
    public function __construct(
        public string $eventId,
        public string $eventType,
        public string $providerPaymentId,
        public PaymentStatus $status,
        public ?string $externalStatus = null,
        public array $metadata = [],
    ) {}
}
