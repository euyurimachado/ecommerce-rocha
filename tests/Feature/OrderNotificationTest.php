<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Notifications\OrderStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_lifecycle_notifies_customer_once_per_real_transition(): void
    {
        Notification::fake();
        $order = $this->createOrder();

        Notification::assertSentTo($order, OrderStatusNotification::class, fn ($notification) => $notification->event === 'created');

        $order->update(['status' => 'preparing']);
        $order->update(['notes' => 'Sem alteração de status']);
        $order->update(['status' => 'out_for_delivery']);
        $order->save();
        $order->update(['status' => 'delivered']);

        foreach (['preparing', 'out_for_delivery', 'delivered'] as $event) {
            Notification::assertSentToTimes($order, OrderStatusNotification::class, 4);
            Notification::assertSentTo($order, OrderStatusNotification::class, fn ($notification) => $notification->event === $event);
        }
    }

    public function test_notification_is_sent_to_guest_checkout_email(): void
    {
        Notification::fake();
        $order = $this->createOrder(['customer_email' => 'guest@example.com']);

        Notification::assertSentTo($order, OrderStatusNotification::class, function ($notification, $channels, $notifiable): bool {
            return $notifiable->routeNotificationForMail() === 'guest@example.com';
        });
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'code' => 'RS-NOTIFY-001',
            'status' => 'payment_pending',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'pix',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
            'subtotal_cents' => 8990,
            'shipping_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 8990,
            'privacy_accepted_at' => now(),
        ], $overrides));
    }
}
