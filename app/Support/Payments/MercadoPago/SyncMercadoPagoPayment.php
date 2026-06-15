<?php

namespace App\Support\Payments\MercadoPago;

use App\Models\Order;
use Illuminate\Support\Carbon;

class SyncMercadoPagoPayment
{
    public function __construct(private readonly MercadoPagoClient $client) {}

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
        $shouldMarkInventoryAsSold = $status === 'approved' && ! $order->payment_approved_at;

        $order->forceFill([
            'status' => $this->orderStatus($status),
            'mercado_pago_payment_id' => (string) data_get($payment, 'id'),
            'mercado_pago_status' => $status,
            'mercado_pago_status_detail' => data_get($payment, 'status_detail'),
            'payment_approved_at' => $status === 'approved'
                ? Carbon::parse(data_get($payment, 'date_approved', now()))
                : $order->payment_approved_at,
        ])->save();

        if ($shouldMarkInventoryAsSold) {
            $this->markInventoryAsSold($order);
        }
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

    private function markInventoryAsSold(Order $order): void
    {
        $order->loadMissing('items.product');

        foreach ($order->items as $item) {
            if (! $item->product) {
                continue;
            }

            $quantityToDecrement = min($item->quantity, $item->product->stock_quantity);

            if ($quantityToDecrement > 0) {
                $item->product->decrement('stock_quantity', $quantityToDecrement);
            }

            $item->product->increment('sales_count', $item->quantity);
        }
    }
}
