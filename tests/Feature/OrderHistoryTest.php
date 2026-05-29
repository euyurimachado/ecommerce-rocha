<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_order_history_lookup(): void
    {
        $this->get('/pedidos')
            ->assertOk()
            ->assertSee('Acompanhe suas compras')
            ->assertSee('Consultar pedidos');
    }

    public function test_customer_can_find_recent_orders_by_email(): void
    {
        $order = $this->createOrder([
            'code' => 'RS260529ABC01',
            'customer_email' => 'cliente@exemplo.com',
            'customer_phone' => '22999990000',
        ]);

        $order->items()->create([
            'product_name' => 'Creatina Monohidratada 300g',
            'product_sku' => 'CR-001',
            'quantity' => 1,
            'unit_price_cents' => 8990,
            'line_total_cents' => 8990,
        ]);

        $this->get('/pedidos?contato=CLIENTE@EXEMPLO.COM')
            ->assertOk()
            ->assertSee('Pedido RS260529ABC01')
            ->assertSee('Creatina Monohidratada 300g')
            ->assertSee('R$ 89,90');
    }

    public function test_customer_can_find_recent_orders_by_phone_without_punctuation(): void
    {
        $this->createOrder([
            'code' => 'RS260529PHONE',
            'customer_email' => 'telefone@exemplo.com',
            'customer_phone' => '22999990000',
        ]);

        $this->get('/pedidos?contato=(22)%2099999-0000')
            ->assertOk()
            ->assertSee('Pedido RS260529PHONE');
    }

    public function test_order_history_does_not_show_orders_from_another_contact(): void
    {
        $this->createOrder([
            'code' => 'RS260529SAFE1',
            'customer_email' => 'cliente@exemplo.com',
            'customer_phone' => '22999990000',
        ]);

        $this->createOrder([
            'code' => 'RS260529SAFE2',
            'customer_email' => 'outro@exemplo.com',
            'customer_phone' => '22888880000',
        ]);

        $this->get('/pedidos?contato=cliente@exemplo.com')
            ->assertOk()
            ->assertSee('Pedido RS260529SAFE1')
            ->assertDontSee('RS260529SAFE2');
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'code' => 'RS260529TEST1',
            'status' => 'received',
            'customer_name' => 'Cliente Rocha',
            'customer_email' => 'cliente@exemplo.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'delivery',
            'payment_method' => 'pix',
            'subtotal_cents' => 8990,
            'shipping_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 8990,
            'privacy_accepted_at' => now(),
        ], $overrides));
    }
}
