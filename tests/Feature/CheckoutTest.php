<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\IntegrationSetting;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);
        IntegrationSetting::create([
            'type' => 'payment', 'provider' => 'mercado_pago', 'enabled' => true,
            'environment' => 'sandbox',
            'credentials' => ['access_token' => 'TEST-ACCESS-TOKEN', 'public_key' => 'TEST-public-key'],
            'settings' => [],
        ]);
        Http::fake([
            'api.mercadopago.com/v1/payments' => function (Request $request) {
                if (config('testing.fail_payment')) {
                    return Http::response(['message' => 'payment unavailable'], 400);
                }
                if ($response = config('testing.payment_response')) {
                    return Http::response($response, 201);
                }

                return Http::response([
                    'id' => 'pay-test-123',
                    'status' => 'pending',
                    'status_detail' => 'pending_waiting_payment',
                    'point_of_interaction' => ['transaction_data' => [
                        'qr_code' => 'pix-test-code',
                        'qr_code_base64' => 'cGl4LWltYWdl',
                        'ticket_url' => 'https://www.mercadopago.com.br/ticket/pay-test-123',
                    ]],
                ], 201);
            },
        ]);
    }

    public function test_checkout_renders_card_brick_callbacks_and_friendly_copy(): void
    {
        config(['services.mercado_pago.public_key' => 'TEST-public-key']);
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('payment_method', 'credit_card')
            ->assertSee('paymentBrick_container', false)
            ->assertSee('Carregando pagamento seguro...')
            ->assertSee('Pagamento seguro com cartão.')
            ->assertDontSee('Dados tokenizados com MercadoPago.js.');

        $blade = file_get_contents(resource_path('views/livewire/checkout/checkout-page.blade.php'));
        $this->assertStringContainsString('onReady:', $blade);
        $this->assertStringContainsString('onError:', $blade);
        $this->assertStringContainsString('onSubmit:', $blade);
    }

    public function test_checkout_uses_persisted_gateway_without_legacy_hosted_checkout(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->assertSee('PIX')
            ->assertSee('Cartão de crédito')
            ->assertDontSee('Você será direcionado ao ambiente seguro do Mercado Pago.')
            ->assertDontSee('value="mercado_pago"', false);
    }

    public function test_checkout_handles_missing_public_key_without_exposing_card_form(): void
    {
        config(['services.mercado_pago.public_key' => null]);
        IntegrationSetting::query()->where('provider', 'mercado_pago')->firstOrFail()->update([
            'credentials' => ['access_token' => 'TEST-ACCESS-TOKEN'],
        ]);
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('payment_method', 'credit_card')
            ->assertSee('O pagamento com cartão está temporariamente indisponível.')
            ->assertDontSee('paymentBrick_container', false);
    }

    public function test_checkout_rejects_credit_card_without_brick_token(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('payment_method', 'credit_card')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasErrors(['card_token', 'card_payment_method_id']);

        Http::assertNotSent(fn (Request $request) => str_ends_with($request->url(), '/v1/payments'));
    }

    public function test_checkout_without_active_persisted_payment_gateway_shows_message_and_does_not_create_order(): void
    {
        config([
            'services.mercado_pago.access_token' => 'legacy-access-token',
            'services.mercado_pago.public_key' => 'legacy-public-key',
        ]);
        IntegrationSetting::query()->where('provider', 'mercado_pago')->firstOrFail()->update([
            'enabled' => false,
            'credentials' => [],
        ]);
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->assertSee('Nenhum meio de pagamento online está configurado. Entre em contato com a loja.')
            ->assertDontSee('paymentBrick_container', false)
            ->call('placeOrder')
            ->assertSet('checkoutError', 'Nenhum meio de pagamento online está configurado. Entre em contato com a loja.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_creates_order_from_cart(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id, 2);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'delivery')
            ->set('postal_code', '28000-000')
            ->set('street', 'Rua Teste')
            ->set('number', '123')
            ->set('neighborhood', 'Centro')
            ->set('city', 'Campos dos Goytacazes')
            ->set('state', 'RJ')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::query()->with('items')->first();

        $this->assertNotNull($order);
        $this->assertStringContainsString($order->code, route('orders.status', ['order' => $order->code]));
        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertNull($order->mercado_pago_preference_id);
        $this->assertSame('pay-test-123', $order->mercado_pago_payment_id);
        $this->assertSame('pix-test-code', $order->pix_qr_code);
        $this->assertSame('https://www.mercadopago.com.br/ticket/pay-test-123', $order->pix_ticket_url);
        $this->assertSame(17980, $order->subtotal_cents);
        $this->assertSame(990, $order->shipping_cents);
        $this->assertSame(18970, $order->total_cents);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(0, $product->refresh()->sales_count);
        $this->assertSame(10, $product->stock_quantity);
        $this->assertSame(0, app(CartManager::class)->count());

        $this->get(route('orders.status', ['order' => $order->code]))
            ->assertOk()
            ->assertSee('Pedido realizado')
            ->assertSee('PIX Copia e Cola')
            ->assertSee('Abrir instruções do PIX');
    }

    public function test_approved_card_payment_advances_order_and_records_sale(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);
        config(['testing.payment_response' => [
            'id' => 'pay-card-approved', 'status' => 'approved', 'status_detail' => 'accredited',
        ]]);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('payment_method', 'credit_card')
            ->set('card_token', 'secure-token')
            ->set('card_payment_method_id', 'visa')
            ->set('card_installments', 1)
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->firstOrFail();
        $this->assertSame('preparing', $order->status);
        $this->assertSame('approved', $order->payment_status);
        $this->assertSame('pay-card-approved', $order->mercado_pago_payment_id);
        $this->assertSame(1, $product->refresh()->sales_count);
        $this->assertSame(9, $product->stock_quantity);
    }

    public function test_checkout_uses_variant_price_and_sku_without_inventory_control(): void
    {
        $product = $this->createProduct([
            'price_cents' => 8990,
            'variations' => [
                ['name' => 'Sabor', 'options' => [
                    [
                        'value' => 'Chocolate',
                        'sku' => 'WHEY-CHOC',
                        'price_cents' => 12990,
                        'stock_quantity' => 3,
                    ],
                    [
                        'value' => 'Baunilha',
                        'sku' => 'WHEY-BAU',
                        'price_cents' => 11990,
                    ],
                ]],
            ],
        ]);

        app(CartManager::class)->add($product->id, 2, ['Sabor' => 'Chocolate']);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->with('items')->firstOrFail();
        $product->refresh();

        $this->assertSame(25980, $order->subtotal_cents);
        $this->assertSame('WHEY-CHOC', $order->items->first()->product_sku);
        $this->assertSame(12990, $order->items->first()->unit_price_cents);
        $this->assertSame(0, $product->sales_count);
        $this->assertSame(3, data_get($product->variations, '0.options.0.stock_quantity'));
        $this->assertSame(10, $product->stock_quantity);
    }

    public function test_checkout_uses_payments_api_without_hosted_checkout_redirect(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::query()->firstOrFail();
        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('pix', $order->payment_method);
        $this->assertSame('pay-test-123', $order->mercado_pago_payment_id);
        $this->assertSame(0, app(CartManager::class)->count());
        $this->assertSame(0, $product->refresh()->sales_count);
        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/v1/payments'
            && $request['payment_method_id'] === 'pix'
            && filled($request->header('X-Idempotency-Key')[0] ?? null));
        Http::assertNotSent(fn ($request) => str_ends_with($request->url(), '/checkout/preferences'));
    }

    public function test_checkout_rejects_payment_on_delivery(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'delivery')
            ->set('postal_code', '28000-000')
            ->set('street', 'Rua Teste')
            ->set('number', '123')
            ->set('neighborhood', 'Centro')
            ->set('city', 'Campos dos Goytacazes')
            ->set('state', 'RJ')
            ->set('payment_method', 'payment_on_delivery_card')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasErrors(['payment_method']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_keeps_cart_and_order_key_when_mercado_pago_payment_fails(): void
    {
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);

        config(['testing.fail_payment' => true]);

        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertSet('checkoutError', 'Não foi possível finalizar o pedido. Revise os dados e tente novamente.');

        $this->assertSame(1, app(CartManager::class)->count());
        $this->assertSame(0, $product->refresh()->sales_count);
        $this->assertDatabaseCount('orders', 1);
        $this->assertNotNull(Order::first()->payment_idempotency_key);
    }

    public function test_checkout_requires_address_for_delivery(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'delivery')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasErrors(['postal_code', 'street', 'number', 'neighborhood']);
    }

    public function test_checkout_autofills_address_from_postal_code(): void
    {
        Http::fake([
            'viacep.com.br/ws/28000000/json/' => Http::response([
                'cep' => '28000-000',
                'logradouro' => 'Rua do Comércio',
                'bairro' => 'Centro',
                'localidade' => 'Campos dos Goytacazes',
                'uf' => 'RJ',
            ]),
        ]);

        Livewire::test(CheckoutPage::class)
            ->set('postal_code', '28000000')
            ->call('lookupPostalCode')
            ->assertSet('postal_code', '28000-000')
            ->assertSet('street', 'Rua do Comércio')
            ->assertSet('neighborhood', 'Centro')
            ->assertSet('city', 'Campos dos Goytacazes')
            ->assertSet('state', 'RJ')
            ->assertSet('addressLookupError', null);
    }

    public function test_checkout_validates_email_and_phone(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'email-invalido')
            ->set('customer_phone', '(22) 999')
            ->set('fulfillment_method', 'pickup')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasErrors(['customer_email', 'customer_phone']);
    }

    public function test_checkout_persists_coupon_discount_on_order(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id, 2);

        $coupon = Coupon::create([
            'code' => 'ROCHA20',
            'name' => 'Campanha Rocha',
            'type' => 'fixed',
            'value' => 2000,
            'is_active' => true,
        ]);

        app(CartManager::class)->applyCoupon('ROCHA20');

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'delivery')
            ->set('postal_code', '28000-000')
            ->set('street', 'Rua Teste')
            ->set('number', '123')
            ->set('neighborhood', 'Centro')
            ->set('city', 'Campos dos Goytacazes')
            ->set('state', 'RJ')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->first();

        $this->assertSame('ROCHA20', $order->coupon_code);
        $this->assertSame(17980, $order->subtotal_cents);
        $this->assertSame(990, $order->shipping_cents);
        $this->assertSame(2000, $order->discount_cents);
        $this->assertSame(16970, $order->total_cents);
        $this->assertSame(1, $coupon->refresh()->used_count);
    }

    public function test_pickup_order_has_free_shipping(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->first();

        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(8990, $order->total_cents);
    }

    public function test_delivery_order_gets_free_shipping_above_threshold(): void
    {
        $product = $this->createProduct([
            'price_cents' => 26000,
        ]);

        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'delivery')
            ->set('postal_code', '28000-000')
            ->set('street', 'Rua Teste')
            ->set('number', '123')
            ->set('neighborhood', 'Centro')
            ->set('city', 'Campos dos Goytacazes')
            ->set('state', 'RJ')
            ->set('customer_tax_id', '12345678909')
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->first();

        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(26000, $order->total_cents);
    }

    public function test_checkout_summary_uses_selected_variation_image(): void
    {
        $product = $this->createProduct([
            'image_path' => 'products/creatina.webp',
            'variations' => [[
                'name' => 'Sabor',
                'options' => [[
                    'value' => 'Frutas Vermelhas',
                    'image_path' => 'products/gallery/frutas-vermelhas.webp',
                ]],
            ]],
        ]);
        app(CartManager::class)->add($product->id, variantSelections: ['Sabor' => 'Frutas Vermelhas']);

        Livewire::test(CheckoutPage::class)
            ->assertSee(asset('storage/products/gallery/frutas-vermelhas.webp'), false);
    }

    public function test_checkout_summary_uses_product_image_then_fallback(): void
    {
        $product = $this->createProduct(['image_path' => 'products/creatina.webp']);
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->assertSee(asset('storage/products/creatina.webp'), false);

        app(CartManager::class)->clear();
        $fallbackProduct = $this->createProduct([
            'slug' => 'produto-sem-foto',
            'sku' => 'TEST-CHECKOUT-SEM-FOTO',
            'name' => 'Produto sem foto',
        ]);
        app(CartManager::class)->add($fallbackProduct->id);

        Livewire::test(CheckoutPage::class)
            ->assertSee(asset('images/products/placeholder.svg'), false);
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'creatina'],
            [
                'name' => 'Creatina',
                'icon' => 'CR',
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'TEST-CHECKOUT-001',
            'price_cents' => 8990,
            'stock_quantity' => 10,
            'rating' => 4.9,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => true,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ], $overrides));
    }
}
