<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_syncs_approved_payment_status(): void
    {
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);

        $product = $this->createProduct();

        $order = Order::create([
            'code' => 'RS260615MP',
            'status' => 'payment_pending',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'mercado_pago',
            'subtotal_cents' => 8990,
            'shipping_cents' => 0,
            'discount_cents' => 0,
            'total_cents' => 8990,
            'privacy_accepted_at' => now(),
            'mercado_pago_preference_id' => 'pref-test-123',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'category_name' => $product->category->name,
            'quantity' => 1,
            'unit_price_cents' => $product->price_cents,
            'line_total_cents' => $product->price_cents,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/123456' => Http::response([
                'id' => 123456,
                'external_reference' => $order->code,
                'status' => 'approved',
                'status_detail' => 'accredited',
                'date_approved' => now()->toIso8601String(),
            ]),
        ]);

        $this->postJson('/api/pagamentos/mercado-pago/webhook?type=payment&data.id=123456', [
            'type' => 'payment',
            'data' => ['id' => '123456'],
        ])->assertOk();

        $order->refresh();

        $this->assertSame('payment_approved', $order->status);
        $this->assertSame('123456', $order->mercado_pago_payment_id);
        $this->assertSame('approved', $order->mercado_pago_status);
        $this->assertSame('accredited', $order->mercado_pago_status_detail);
        $this->assertNotNull($order->payment_approved_at);
        $product->refresh();
        $this->assertSame(1, $product->sales_count);
        $this->assertSame(9, $product->stock_quantity);
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
            'sku' => 'TEST-MP-001',
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
