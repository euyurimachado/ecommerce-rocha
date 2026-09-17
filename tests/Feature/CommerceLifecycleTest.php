<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Notifications\OrderStatusNotification;
use App\Support\Cart\CartManager;
use App\Support\Shipping\ShippingProviderManager;
use App\Support\Shipping\UpdateShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class CommerceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_quote_pix_payment_shipment_and_tracking_lifecycle(): void
    {
        Notification::fake();
        StoreSetting::create([
            'name' => 'Loja', 'short_name' => 'Loja', 'primary_color' => '#0098d7', 'primary_dark_color' => '#005d8f',
            'secondary_color' => '#a7a9ac', 'accent_color' => '#f59e0b', 'background_color' => '#f8fafc',
            'font_family' => 'Inter', 'tax_id' => '12345678000199', 'state_registration' => 'ISENTO', 'email' => 'loja@example.com', 'phone' => '22999990000',
            'postal_code' => '28000000', 'street' => 'Rua Origem', 'number' => '1', 'neighborhood' => 'Centro',
            'city' => 'Campos', 'state' => 'RJ', 'country' => 'BR', 'installed_at' => now(), 'installation_version' => '1.0.0',
        ]);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true, 'environment' => 'sandbox',
            'credentials' => ['access_token' => 'mp-token', 'public_key' => 'mp-public', 'webhook_secret' => 'mp-webhook-secret'],
            'settings' => [],
        ]);
        IntegrationSetting::create([
            'type' => 'shipping', 'provider' => 'melhor_envio', 'enabled' => true, 'environment' => 'sandbox',
            'credentials' => [
                'client_id' => 'me-client', 'client_secret' => 'me-secret', 'access_token' => 'me-token',
                'refresh_token' => 'me-refresh', 'expires_at' => now()->addHour()->toIso8601String(),
            ], 'settings' => ['auto_purchase_label' => false],
        ]);
        $category = Category::create(['name' => 'Produtos', 'slug' => 'produtos', 'is_active' => true, 'is_featured' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Produto físico', 'slug' => 'produto-fisico', 'sku' => 'FIS-1',
            'price_cents' => 10000, 'stock_quantity' => 5, 'rating' => 5, 'is_active' => true,
            'requires_shipping' => true, 'weight_kg' => .5, 'width_cm' => 12, 'height_cm' => 8, 'length_cm' => 20,
        ]);
        app(CartManager::class)->add($product->id);
        Http::fake(function (Request $request) {
            return match (true) {
                str_ends_with($request->url(), '/shipment/calculate') => Http::response([[
                    'id' => 1, 'name' => 'PAC', 'price' => '24.90', 'custom_price' => '24.90',
                    'delivery_time' => 6, 'custom_delivery_time' => 6, 'company' => ['name' => 'Correios'],
                ]]),
                str_ends_with($request->url(), '/v1/orders') => Http::response([
                    'id' => 'mp-order-1', 'status' => 'action_required', 'transactions' => ['payments' => [[
                        'id' => 'mp-payment-1', 'status' => 'pending',
                        'payment_method' => ['qr_code' => 'pix-code', 'qr_code_base64' => 'pix-image'],
                    ]]],
                ], 201),
                str_ends_with($request->url(), '/v1/payments/mp-payment-1') => Http::response([
                    'id' => 'mp-payment-1', 'external_reference' => Order::query()->value('code'),
                    'status' => 'approved', 'status_detail' => 'accredited', 'payment_type_id' => 'bank_transfer',
                    'transaction_amount' => 124.90, 'date_approved' => now()->toIso8601String(),
                ]),
                str_ends_with($request->url(), '/api/v2/me/cart') => Http::response(['id' => 'shipment-1'], 201),
                default => Http::response([], 404),
            };
        });

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Cliente Teste')->set('customer_email', 'cliente@example.com')->set('customer_phone', '22999990000')
            ->set('customer_tax_id', '12345678901')
            ->set('fulfillment_method', 'delivery')->set('postal_code', '22000000')->set('street', 'Rua Destino')
            ->set('number', '10')->set('neighborhood', 'Centro')->set('city', 'Rio de Janeiro')->set('state', 'RJ')
            ->call('loadShippingQuotes')->set('selected_shipping', '1')->set('payment_method', 'pix')
            ->set('privacy_accepted', true)->call('placeOrder')->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame(2490, $order->shipping_price_cents);
        $this->assertSame('PAC', $order->shipping_service_name);
        $this->assertSame('pending', $order->payments()->firstOrFail()->status->value);

        $requestId = 'request-1';
        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', "id:mp-payment-1;request-id:{$requestId};ts:{$timestamp};", 'mp-webhook-secret');
        $headers = ['x-request-id' => $requestId, 'x-signature' => "ts={$timestamp},v1={$signature}"];
        $payload = ['type' => 'payment', 'data' => ['id' => 'mp-payment-1']];
        $this->withHeaders($headers)->postJson('/webhooks/payments/mercado-pago', $payload)->assertOk();
        $this->withHeaders($headers)->postJson('/webhooks/payments/mercado-pago', $payload)->assertOk();
        $this->assertSame('preparing', $order->refresh()->status);
        $this->assertSame(4, $product->refresh()->stock_quantity);
        $this->assertDatabaseCount('webhook_events', 1);

        $provider = app(ShippingProviderManager::class)->active();
        app(UpdateShipment::class)->apply($order, $provider->createShipment($order));
        $this->assertSame('shipment-1', $order->refresh()->shipping_external_id);

        $this->sendShippingWebhook($order, 'event-posted', 'posted', 'BR123');
        $this->assertSame('out_for_delivery', $order->refresh()->status);
        $this->sendShippingWebhook($order, 'event-delivered', 'delivered', 'BR123');
        $this->assertSame('delivered', $order->refresh()->status);

        Notification::assertSentTo($order, OrderStatusNotification::class, fn ($notification): bool => $notification->event === 'out_for_delivery');
        Notification::assertSentTo($order, OrderStatusNotification::class, fn ($notification): bool => $notification->event === 'delivered');
    }

    private function sendShippingWebhook(Order $order, string $eventId, string $status, string $tracking): void
    {
        $payload = ['event_id' => $eventId, 'event' => 'shipment.updated', 'data' => [
            'id' => $order->shipping_external_id, 'status' => $status, 'tracking' => $tracking,
        ]];
        $signature = base64_encode(hash_hmac('sha256', json_encode($payload), 'me-secret', true));

        $this->withHeader('X-ME-Signature', $signature)
            ->postJson('/webhooks/shipping/melhor-envio', $payload)->assertOk();
    }
}
