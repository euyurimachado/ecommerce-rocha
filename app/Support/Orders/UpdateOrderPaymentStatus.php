<?php

namespace App\Support\Orders;

use App\Models\Order;
use InvalidArgumentException;

class UpdateOrderPaymentStatus
{
    public function __invoke(Order $order, string $status): Order
    {
        if (! in_array($status, ['payment_pending', 'payment_approved', 'payment_rejected', 'payment_refunded'], true)) {
            throw new InvalidArgumentException('Status de pagamento inválido.');
        }

        $shouldMarkInventoryAsSold = $status === 'payment_approved'
            && $order->payment_method === 'mercado_pago'
            && ! $order->payment_approved_at;

        $order->forceFill([
            'status' => $status,
            'payment_approved_at' => $status === 'payment_approved'
                ? ($order->payment_approved_at ?? now())
                : ($status === 'payment_pending' ? null : $order->payment_approved_at),
        ])->save();

        if ($shouldMarkInventoryAsSold) {
            $this->markInventoryAsSold($order);
        }

        return $order->refresh();
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
