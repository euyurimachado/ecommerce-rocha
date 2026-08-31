<?php

namespace App\Observers;

use App\Models\Order;
use App\Notifications\OrderStatusNotification;

class OrderObserver
{
    public function created(Order $order): void
    {
        $this->notify($order, 'created');
    }

    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status') || ! in_array($order->status, ['preparing', 'out_for_delivery', 'delivered'], true)) {
            return;
        }

        $this->notify($order, $order->status);
    }

    private function notify(Order $order, string $event): void
    {
        if (filter_var($order->customer_email, FILTER_VALIDATE_EMAIL)) {
            $order->notify(new OrderStatusNotification($order, $event));
        }
    }
}
