<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Notifications\OrderStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_syncs_approved_payment_status(): void
    {
        Notification::fake();
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);

        $product = $this->createProduct();

        $order = Order::create([
            'code' => 'RS260615MP',
            'status' => 'payment_pending',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000',
            'fulfillment_method' => 'pickup',
            'payment_method' => 'pix',
            'payment_provider' => 'mercado_pago',
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

        $this->assertSame('preparing', $order->status);
        $this->assertSame('approved', $order->payment_status);
        $this->assertSame('123456', $order->mercado_pago_payment_id);
        $this->assertSame('approved', $order->mercado_pago_status);
        $this->assertSame('accredited', $order->mercado_pago_status_detail);
        $this->assertNotNull($order->payment_approved_at);
        $product->refresh();
        $this->assertSame(1, $product->sales_count);
        $this->assertSame(9, $product->stock_quantity);
        Notification::assertSentTo($order, OrderStatusNotification::class, fn ($notification) => $notification->event === 'preparing');

        $this->postJson('/api/pagamentos/mercado-pago/webhook?type=payment&data.id=123456', [
            'type' => 'payment',
            'data' => ['id' => '123456'],
        ])->assertOk();

        $this->assertSame(1, $product->refresh()->sales_count);
        $this->assertSame(9, $product->stock_quantity);
        Notification::assertSentToTimes($order, OrderStatusNotification::class, 2);
    }

    public function test_invalid_mercado_pago_signature_is_rejected(): void
    {
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'sandbox', 'credentials' => [
                'access_token' => 'TEST-ACCESS-TOKEN', 'webhook_secret' => 'TEST-WEBHOOK-SECRET',
            ], 'settings' => [],
        ]);

        $this->withHeaders(['x-signature' => 'ts=1700000000,v1=invalid', 'x-request-id' => 'request-1'])
            ->postJson('/webhooks/payments/mercado-pago?type=payment&data.id=123456', [
                'type' => 'payment', 'data' => ['id' => '123456'],
            ])->assertUnauthorized();
    }

    public function test_legacy_order_webhook_resolves_its_payment_id(): void
    {
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);
        $order = Order::create([
            'code' => 'RS260615LEGACY', 'status' => 'payment_pending',
            'customer_name' => 'Cliente Teste', 'customer_email' => 'cliente@example.com',
            'customer_phone' => '22999990000', 'fulfillment_method' => 'pickup',
            'payment_method' => 'pix', 'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending', 'subtotal_cents' => 8990, 'shipping_cents' => 0,
            'discount_cents' => 0, 'total_cents' => 8990,
        ]);
        Payment::create([
            'order_id' => $order->id, 'provider' => 'mercado_pago', 'method' => 'pix',
            'provider_payment_id' => '654321', 'amount_cents' => 8990,
            'status' => 'pending', 'external_status' => 'pending',
            'idempotency_key' => 'legacy-payment-attempt',
        ]);
        Http::fake([
            'api.mercadopago.com/v1/orders/ORD-LEGACY' => Http::response([
                'id' => 'ORD-LEGACY', 'transactions' => ['payments' => [['id' => 654321]]],
            ]),
            'api.mercadopago.com/v1/payments/654321' => Http::response([
                'id' => 654321, 'external_reference' => $order->code,
                'status' => 'approved', 'status_detail' => 'accredited',
                'payment_type_id' => 'bank_transfer', 'transaction_amount' => 89.90,
            ]),
        ]);

        $this->postJson('/api/pagamentos/mercado-pago/webhook?type=order&data.id=ORD-LEGACY', [
            'type' => 'order', 'data' => ['id' => 'ORD-LEGACY'],
        ])->assertOk();

        $this->assertSame('approved', $order->refresh()->payment_status);
        $this->assertSame(PaymentStatus::Paid, Payment::query()->firstOrFail()->status);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/orders/ORD-LEGACY'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/v1/payments/654321'));
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
