<?php

namespace Tests\Feature;

use App\Enums\ShippingStatus;
use App\Models\Category;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\Shipping\FlatRateShipping;
use App\Support\Shipping\MelhorEnvio\MelhorEnvioClient;
use App\Support\Shipping\MelhorEnvio\MelhorEnvioShipping;
use App\Support\Shipping\MissingShippingDimensions;
use App\Support\Shipping\ShippingItem;
use App\Support\Shipping\ShippingQuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingProvidersTest extends TestCase
{
    use RefreshDatabase;

    public function test_flat_rate_normalizes_price_deadline_and_free_shipping(): void
    {
        $integration = IntegrationSetting::create([
            'type' => 'shipping', 'provider' => 'flat_rate', 'enabled' => true, 'environment' => 'production',
            'credentials' => [], 'settings' => ['name' => 'Entrega expressa', 'price_cents' => 1590, 'max_days' => 3, 'free_shipping_threshold_cents' => 20000],
        ]);
        $provider = new FlatRateShipping($integration);

        $paid = $provider->quote(new ShippingQuoteRequest('28000000', '22000000', [], 10000))[0];
        $free = $provider->quote(new ShippingQuoteRequest('28000000', '22000000', [], 25000))[0];

        $this->assertSame(1590, $paid->priceCents);
        $this->assertSame(3, $paid->deliveryDays);
        $this->assertSame('Entrega expressa', $paid->serviceName);
        $this->assertSame(0, $free->priceCents);
    }

    public function test_melhor_envio_rejects_physical_product_without_dimensions(): void
    {
        $provider = new MelhorEnvioShipping($this->melhorEnvio());

        $this->expectException(MissingShippingDimensions::class);
        $provider->quote(new ShippingQuoteRequest('28000000', '22000000', [
            new ShippingItem(1, 'Produto incompleto', 1, 10000, true, null, null, null, null),
        ], 10000));
    }

    public function test_melhor_envio_quotes_using_custom_values_and_short_cache(): void
    {
        Cache::clear();
        Http::fake(['sandbox.melhorenvio.com.br/api/v2/me/shipment/calculate' => Http::response([[
            'id' => 1, 'name' => 'PAC', 'price' => '30.00', 'custom_price' => '24.90',
            'delivery_time' => 8, 'custom_delivery_time' => 6, 'company' => ['name' => 'Correios'],
            'packages' => [[
                'dimensions' => ['height' => 9, 'width' => 13, 'length' => 21], 'weight' => '1.00',
            ]],
        ]])]);
        $provider = new MelhorEnvioShipping($this->melhorEnvio());
        $request = new ShippingQuoteRequest('28000000', '22000000', [
            new ShippingItem(1, 'Produto', 2, 10000, true, 0.5, 12, 8, 20),
        ], 20000);

        $quote = $provider->quote($request)[0];
        $provider->quote($request);

        $this->assertSame('melhor_envio', $quote->provider);
        $this->assertSame(2490, $quote->priceCents);
        $this->assertSame(3000, $quote->originalPriceCents);
        $this->assertSame(6, $quote->deliveryDays);
        $this->assertSame(9.0, $quote->packages[0]['height']);
        Http::assertSentCount(1);
    }

    public function test_melhor_envio_shipment_purchase_generation_and_tracking(): void
    {
        StoreSetting::create([
            'name' => 'Loja', 'short_name' => 'Loja', 'primary_color' => '#000000', 'primary_dark_color' => '#000000',
            'secondary_color' => '#ffffff', 'accent_color' => '#ff0000', 'background_color' => '#ffffff', 'font_family' => 'Inter',
            'tax_id' => '12345678000199', 'state_registration' => 'ISENTO', 'email' => 'loja@example.com', 'phone' => '22999990000',
            'postal_code' => '28000000', 'street' => 'Rua Origem', 'number' => '1', 'neighborhood' => 'Centro',
            'city' => 'Campos', 'state' => 'RJ', 'country' => 'BR',
        ]);
        $product = $this->product();
        $order = $this->order();
        $order->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name, 'product_sku' => $product->sku,
            'quantity' => 1, 'unit_price_cents' => 10000, 'line_total_cents' => 10000,
        ]);
        Http::fake(function (Request $request) {
            return match (true) {
                str_ends_with($request->url(), '/api/v2/me/cart') => Http::response(['id' => 'shipment-1'], 201),
                str_ends_with($request->url(), '/checkout') => Http::response(['purchase' => ['id' => 'shipment-1']]),
                str_ends_with($request->url(), '/generate') => Http::response([['id' => 'shipment-1', 'status' => true]]),
                str_ends_with($request->url(), '/print') => Http::response(['url' => 'https://labels.test/shipment-1.pdf']),
                str_ends_with($request->url(), '/tracking') => Http::response(['shipment-1' => ['status' => 'delivered', 'tracking' => 'BR123']]),
                default => Http::response([], 404),
            };
        });
        $provider = new MelhorEnvioShipping($this->melhorEnvio());

        $created = $provider->createShipment($order);
        $order->update(['shipping_external_id' => $created->externalId]);
        $purchased = $provider->purchaseLabel($order->refresh());
        $generated = $provider->generateLabel($order->refresh());
        $tracking = $provider->tracking($order->refresh());

        $this->assertSame(ShippingStatus::LabelCreated, $created->status);
        $this->assertSame(ShippingStatus::Paid, $purchased->status);
        $this->assertSame('https://labels.test/shipment-1.pdf', $generated->labelUrl);
        $this->assertSame(ShippingStatus::Delivered, $tracking->status);
        $this->assertSame('BR123', $tracking->trackingCode);
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v2/me/cart')
            && $request['from']['company_document'] === '12345678000199'
            && $request['to']['document'] === '12345678901');
    }

    public function test_melhor_envio_refreshes_expired_token_once_before_request(): void
    {
        Cache::clear();
        $integration = $this->melhorEnvio(['expires_at' => now()->subMinute()->toIso8601String()]);
        Http::fake(function (Request $request) {
            if (str_ends_with($request->url(), '/oauth/token')) {
                return Http::response(['access_token' => 'renewed-token', 'refresh_token' => 'renewed-refresh', 'expires_in' => 3600]);
            }
            if (str_ends_with($request->url(), '/api/v2/me')) {
                return Http::response(['id' => 123]);
            }

            return Http::response([], 404);
        });

        $result = (new MelhorEnvioShipping($integration))->testConnection();

        $this->assertTrue($result->successful);
        $this->assertSame('renewed-token', $integration->fresh()->credential('access_token'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/oauth/token'));
        Http::assertSent(fn (Request $request): bool => str_ends_with($request->url(), '/api/v2/me')
            && ($request->header('Authorization')[0] ?? null) === 'Bearer renewed-token');
    }

    public function test_melhor_envio_authorization_uses_required_shipping_scopes(): void
    {
        $url = (new MelhorEnvioClient($this->melhorEnvio()))->authorizeUrl('oauth-state');
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $scopes = explode(' ', $query['scope']);

        $this->assertContains('cart-write', $scopes);
        $this->assertContains('shipping-checkout', $scopes);
        $this->assertContains('shipping-print', $scopes);
        $this->assertNotContains('shipping-store', $scopes);
    }

    public function test_melhor_envio_webhook_validates_hmac_and_is_idempotent(): void
    {
        $this->melhorEnvio(['client_secret' => 'hmac-secret']);
        $order = $this->order(['shipping_external_id' => 'shipment-1']);
        $payload = ['event' => 'order.posted', 'data' => [
            'id' => 'shipment-1', 'status' => 'posted', 'tracking' => 'BR123',
        ]];
        $json = json_encode($payload);

        $this->withHeader('X-ME-Signature', 'invalid')->postJson('/webhooks/shipping/melhor-envio', $payload)->assertUnauthorized();
        $signature = base64_encode(hash_hmac('sha256', $json, 'hmac-secret', true));
        $this->withHeader('X-ME-Signature', $signature)->postJson('/webhooks/shipping/melhor-envio', $payload)->assertOk();
        $this->withHeader('X-ME-Signature', $signature)->postJson('/webhooks/shipping/melhor-envio', $payload)->assertOk();

        $this->assertSame('out_for_delivery', $order->refresh()->status);
        $this->assertSame('BR123', $order->tracking_code);
        $this->assertDatabaseCount('webhook_events', 1);
    }

    private function melhorEnvio(array $credentials = []): IntegrationSetting
    {
        return IntegrationSetting::query()->updateOrCreate(
            ['type' => 'shipping', 'provider' => 'melhor_envio'],
            [
                'enabled' => true, 'environment' => 'sandbox',
                'credentials' => array_merge([
                    'client_id' => 'client-id', 'client_secret' => 'client-secret', 'access_token' => 'access-token',
                    'refresh_token' => 'refresh-token', 'expires_at' => now()->addHour()->toIso8601String(),
                ], $credentials),
                'settings' => ['auto_purchase_label' => false],
            ],
        );
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Teste', 'slug' => 'teste', 'is_active' => true, 'is_featured' => true]);

        return Product::create([
            'category_id' => $category->id, 'name' => 'Produto', 'slug' => 'produto', 'sku' => 'PROD-1',
            'price_cents' => 10000, 'stock_quantity' => 10, 'rating' => 5, 'is_active' => true,
            'requires_shipping' => true, 'weight_kg' => .5, 'width_cm' => 12, 'height_cm' => 8, 'length_cm' => 20,
        ]);
    }

    private function order(array $overrides = []): Order
    {
        return Order::create(array_merge([
            'code' => 'SHIP'.str()->random(8), 'status' => 'preparing', 'customer_name' => 'Cliente',
            'customer_email' => 'cliente@example.com', 'customer_phone' => '22999990000', 'fulfillment_method' => 'delivery',
            'customer_tax_id' => '12345678901',
            'postal_code' => '22000000', 'street' => 'Rua Destino', 'number' => '2', 'neighborhood' => 'Centro',
            'city' => 'Rio de Janeiro', 'state' => 'RJ', 'payment_method' => 'pix', 'payment_provider' => 'mercado_pago',
            'payment_status' => 'approved', 'subtotal_cents' => 10000, 'shipping_cents' => 2490,
            'shipping_provider' => 'melhor_envio', 'shipping_service_id' => '1', 'shipping_service_name' => 'PAC',
            'shipping_carrier' => 'Correios', 'shipping_status' => 'pending', 'discount_cents' => 0, 'total_cents' => 12490,
        ], $overrides));
    }
}
