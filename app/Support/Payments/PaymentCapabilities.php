<?php

namespace App\Support\Payments;

final readonly class PaymentCapabilities
{
    public function __construct(
        public bool $pix = false,
        public bool $creditCard = false,
        public bool $debitCard = false,
        public bool $installments = false,
        public bool $refund = false,
        public bool $webhooks = true,
    ) {}

    public function methods(): array
    {
        return array_keys(array_filter([
            'pix' => $this->pix,
            'credit_card' => $this->creditCard,
            'debit_card' => $this->debitCard,
        ]));
    }
}
