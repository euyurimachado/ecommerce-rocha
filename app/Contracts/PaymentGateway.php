<?php

namespace App\Contracts;

use App\Support\Integrations\ConnectionResult;
use App\Support\Payments\PaymentCapabilities;
use App\Support\Payments\PaymentRequest;
use App\Support\Payments\PaymentResult;
use App\Support\Payments\WebhookResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function capabilities(): PaymentCapabilities;

    public function createPayment(PaymentRequest $request): PaymentResult;

    public function findPayment(string $providerPaymentId): PaymentResult;

    public function cancel(string $providerPaymentId): PaymentResult;

    public function refund(string $providerPaymentId, ?int $amountCents = null): PaymentResult;

    public function validateWebhook(Request $request): bool;

    public function handleWebhook(Request $request): WebhookResult;

    public function testConnection(): ConnectionResult;
}
