<?php

namespace App\Support\Payments\MercadoPago;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Support\Orders\UpdateOrderPaymentStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SyncMercadoPagoPayment
{
    public function __construct(
        private readonly MercadoPagoClient $client,
        private readonly UpdateOrderPaymentStatus $paymentStatus,
    ) {}

    public function byOrderId(string $orderId): ?Order
    {
        $order = $this->client->getOrder($orderId);
        $paymentId = data_get($order, 'transactions.payments.0.id');

        return filled($paymentId) ? $this->byPaymentId((string) $paymentId) : null;
    }

    public function byPaymentId(string $paymentId): ?Order
    {
        $payment = $this->client->getPayment($paymentId);
        $order = Order::query()
            ->where('code', data_get($payment, 'external_reference'))
            ->orWhere('mercado_pago_payment_id', (string) data_get($payment, 'id'))
            ->first();

        if (! $order) {
            return null;
        }

        $this->applyPayment($order, $payment);

        return $order->refresh();
    }

    public function applyPayment(Order $order, array $payment): void
    {
        $status = (string) data_get($payment, 'status');
        $orderStatus = $this->orderStatus($status);
        $internalStatus = match ($status) {
            'approved' => PaymentStatus::Paid,
            'in_process', 'authorized' => PaymentStatus::Processing,
            'rejected' => PaymentStatus::Failed,
            'cancelled' => PaymentStatus::Cancelled,
            'refunded', 'charged_back' => PaymentStatus::Refunded,
            default => PaymentStatus::Pending,
        };

        $paymentRecord = Payment::query()
            ->where('provider', 'mercado_pago')
            ->where('provider_payment_id', (string) data_get($payment, 'id'))
            ->first()
            ?? ($order->payment_idempotency_key
                ? Payment::query()->where('idempotency_key', $order->payment_idempotency_key)->first()
                : null)
            ?? new Payment(['idempotency_key' => (string) Str::uuid()]);
        $paymentRecord->fill([
            'order_id' => $order->id,
            'provider' => 'mercado_pago',
            'provider_payment_id' => (string) data_get($payment, 'id'),
            'method' => data_get($payment, 'payment_type_id') === 'bank_transfer' ? 'pix' : 'credit_card',
            'amount_cents' => (int) round((float) data_get($payment, 'transaction_amount', $order->total_cents / 100) * 100),
            'status' => $internalStatus,
            'external_status' => $status,
            'paid_at' => $internalStatus === PaymentStatus::Paid ? Carbon::parse(data_get($payment, 'date_approved', now())) : null,
            'failed_at' => $internalStatus === PaymentStatus::Failed ? now() : null,
            'metadata' => ['status_detail' => data_get($payment, 'status_detail')],
        ])->save();

        $order->forceFill([
            'payment_method' => match (data_get($payment, 'payment_type_id')) {
                'bank_transfer' => 'pix',
                'credit_card', 'debit_card' => 'card',
                default => $order->payment_method,
            },
            'mercado_pago_payment_id' => (string) data_get($payment, 'id'),
            'mercado_pago_status' => $status,
            'mercado_pago_status_detail' => data_get($payment, 'status_detail'),
            'payment_status' => $status,
            'pix_qr_code' => data_get($payment, 'point_of_interaction.transaction_data.qr_code'),
            'pix_qr_code_base64' => data_get($payment, 'point_of_interaction.transaction_data.qr_code_base64'),
            'pix_ticket_url' => data_get($payment, 'point_of_interaction.transaction_data.ticket_url'),
            'pix_expires_at' => data_get($payment, 'date_of_expiration'),
        ])->save();

        ($this->paymentStatus)($order, $orderStatus);

        $order->forceFill(array_filter([
            'payment_status' => $status,
            'payment_approved_at' => $status === 'approved'
                ? Carbon::parse(data_get($payment, 'date_approved', now()))
                : null,
        ], fn ($value) => $value !== null))->save();
    }

    private function orderStatus(string $mercadoPagoStatus): string
    {
        return match ($mercadoPagoStatus) {
            'approved' => 'payment_approved',
            'rejected', 'cancelled' => 'payment_rejected',
            'refunded', 'charged_back' => 'payment_refunded',
            default => 'payment_pending',
        };
    }
}
