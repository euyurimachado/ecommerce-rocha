<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

final readonly class PaymentResult
{
    public function __construct(
        public string $providerPaymentId,
        public PaymentStatus $status,
        public ?string $externalStatus = null,
        public ?string $redirectUrl = null,
        public ?string $pixCode = null,
        public ?string $pixQrCodeBase64 = null,
        public ?string $expiresAt = null,
        public array $metadata = [],
        public ?string $pixTicketUrl = null,
    ) {}
}
