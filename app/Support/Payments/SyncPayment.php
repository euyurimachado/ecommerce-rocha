<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\WebhookEvent;
use App\Support\Orders\UpdateOrderPaymentStatus;
use Illuminate\Support\Facades\DB;

class SyncPayment
{
    public function __construct(private readonly UpdateOrderPaymentStatus $orders) {}

    public function apply(string $provider, WebhookResult $result, string $payloadHash): ?Payment
    {
        return DB::transaction(function () use ($provider, $result, $payloadHash): ?Payment {
            $event = WebhookEvent::query()->firstOrCreate(
                ['provider' => $provider, 'external_id' => $result->eventId, 'event_type' => $result->eventType],
                ['payload_hash' => $payloadHash],
            );

            if ($event->processed_at) {
                return Payment::query()->where('provider', $provider)
                    ->where('provider_payment_id', $result->providerPaymentId)->first();
            }

            $payment = Payment::query()->where('provider', $provider)
                ->where('provider_payment_id', $result->providerPaymentId)
                ->first();

            if (! $payment && filled(data_get($result->metadata, 'external_reference'))) {
                $payment = Payment::query()->whereHas('order', fn ($query) => $query
                    ->where('code', data_get($result->metadata, 'external_reference')))->latest()->first();
            }

            if (! $payment) {
                return null;
            }

            $payment->forceFill([
                'provider_payment_id' => $result->providerPaymentId,
                'status' => $result->status,
                'external_status' => $result->externalStatus,
                'paid_at' => $result->status === PaymentStatus::Paid ? ($payment->paid_at ?? now()) : $payment->paid_at,
                'failed_at' => $result->status === PaymentStatus::Failed ? ($payment->failed_at ?? now()) : $payment->failed_at,
                'metadata' => array_merge($payment->metadata ?? [], $result->metadata),
            ])->save();

            ($this->orders)($payment->order, match ($result->status) {
                PaymentStatus::Paid => 'payment_approved',
                PaymentStatus::Failed, PaymentStatus::Cancelled => 'payment_rejected',
                PaymentStatus::Refunded => 'payment_refunded',
                default => 'payment_pending',
            });

            $event->forceFill(['processed_at' => now()])->save();

            return $payment->refresh();
        });
    }
}
