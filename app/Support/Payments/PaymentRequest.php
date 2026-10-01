<?php

namespace App\Support\Payments;

use App\Models\Order;
use App\Models\Payment;

final readonly class PaymentRequest
{
    public function __construct(
        public Order $order,
        public Payment $payment,
        public string $method,
        public ?string $token = null,
        public ?string $paymentMethodId = null,
        public int $installments = 1,
        public ?string $issuerId = null,
        public ?string $identificationType = null,
        public ?string $identificationNumber = null,
    ) {}
}
