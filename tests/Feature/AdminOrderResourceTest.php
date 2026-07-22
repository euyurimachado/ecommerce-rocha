<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Orders\UpdateOrderPaymentStatus;
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

    public function test_admin_can_open_printable_delivery_order(): void
    {
        $admin = User::factory()->create();
        $order = $this->createOrder();

        $order->items()->create([
            'product_name' => 'Creatina Monohidratada 300g',
            'product_sku' => 'CRE-300',
            'quantity' => 2,
            'unit_price_cents' => 8990,
            'line_total_cents' => 17980,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.print', $order))
            ->assertOk()
            ->assertSee('Separação e entrega de pedido')
            ->assertSee('RS260528TEST')
            ->assertSee('Creatina Monohidratada 300g')
            ->assertSee('Assinatura / confirmação de recebimento');
    }

    public function test_manual_mercado_pago_approval_records_sale_once(): void
    {
        $product = $this->createProduct();
        $order = $this->createOrder([
            'status' => 'payment_pending',
            'payment_method' => 'mercado_pago',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'category_name' => $product->category->name,
            'quantity' => 2,
            'unit_price_cents' => $product->price_cents,
            'line_total_cents' => $product->price_cents * 2,
        ]);

        $updater = app(UpdateOrderPaymentStatus::class);

        $updater($order, 'payment_approved');
        $updater($order->refresh(), 'payment_approved');

        $this->assertSame('payment_approved', $order->refresh()->status);
        $this->assertNotNull($order->payment_approved_at);
        $product->refresh();
        $this->assertSame(2, $product->sales_count);
        $this->assertSame(8, $product->stock_quantity);
    }

    private function createOrder(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'code' => 'RS260528TEST',
            'status' => 'received',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'delivery',
            'postal_code' => '28000-000',
            'street' => 'Rua Teste',
            'number' => '123',
            'neighborhood' => 'Centro',
            'city' => 'Campos dos Goytacazes',
            'state' => 'RJ',
            'payment_method' => 'pix',
            'subtotal_cents' => 8990,
            'shipping_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 8990,
            'privacy_accepted_at' => now(),
        ], $overrides));
    }

    private function createProduct(): Product
    {
        $category = Category::create([
            'name' => 'Creatina',
            'slug' => 'creatina',
            'icon' => 'CR',
            'is_active' => true,
            'is_featured' => true,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'TEST-ADMIN-001',
            'price_cents' => 8990,
            'stock_quantity' => 10,
            'rating' => 4.9,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => true,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);
    }
}
