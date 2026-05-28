<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_orders_table(): void
    {
        $admin = User::factory()->create([
            'email' => 'gestor@rochasports.com.br',
        ]);

        Order::create([
            'code' => 'RS260528TEST',
            'status' => 'received',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'pix',
            'subtotal_cents' => 8990,
            'shipping_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 8990,
            'privacy_accepted_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders')
            ->assertOk()
            ->assertSee('RS260528TEST')
            ->assertSee('Cliente Teste');
    }
}
