<?php

namespace App\Support\Payments\MercadoPago;

use App\Models\Order;
use App\Support\Orders\UpdateOrderPaymentStatus;
use Illuminate\Support\Carbon;

class SyncMercadoPagoPayment
{
    public function __construct(
        private readonly MercadoPagoClient $client,
        private readonly UpdateOrderPaymentStatus $paymentStatus,
    ) {}

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
