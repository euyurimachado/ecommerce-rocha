<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

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
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->with('items')->first();

        $this->assertNotNull($order);
        $this->assertStringContainsString($order->code, route('orders.status', ['order' => $order->code]));
        $this->assertSame('received', $order->status);
        $this->assertSame(17980, $order->subtotal_cents);
        $this->assertSame(990, $order->shipping_cents);
        $this->assertSame(18970, $order->total_cents);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(8, $product->refresh()->stock_quantity);
        $this->assertSame(0, app(CartManager::class)->count());
    }

    public function test_checkout_redirects_to_mercado_pago_when_selected(): void
    {
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref-test-123',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-test-123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-test-123',
            ], 201),
        ]);

        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('payment_method', 'mercado_pago')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertRedirect('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref-test-123');

        $order = Order::query()->first();

        $this->assertSame('payment_pending', $order->status);
        $this->assertSame('mercado_pago', $order->payment_method);
        $this->assertSame('pref-test-123', $order->mercado_pago_preference_id);
        $this->assertSame(0, app(CartManager::class)->count());
        $this->assertSame(10, $product->refresh()->stock_quantity);

        Http::assertSent(fn ($request) => $request->url() === 'https://api.mercadopago.com/checkout/preferences'
            && $request['external_reference'] === $order->code
            && $request['items'][0]['unit_price'] === 89.9
            && ! isset($request['notification_url'])
            && ! isset($request['back_urls'])
            && ! isset($request['auto_return']));
    }

    public function test_checkout_creates_payment_on_delivery_order(): void
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
            ->assertHasNoErrors();

        $order = Order::query()->first();

        $this->assertSame('payment_on_delivery_card', $order->payment_method);
        $this->assertSame('Cartão na entrega', $order->payment_method_label);
        $this->assertSame(9, $product->refresh()->stock_quantity);
        $this->assertSame(0, app(CartManager::class)->count());
    }

    public function test_checkout_keeps_cart_when_mercado_pago_preference_fails(): void
    {
        config(['services.mercado_pago.access_token' => 'TEST-ACCESS-TOKEN']);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'message' => 'auto_return invalid. back_url.success must be defined',
            ], 400),
        ]);

        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CheckoutPage::class)
            ->set('customer_name', 'Yuri Machado')
            ->set('customer_email', 'yuri@example.com')
            ->set('customer_phone', '22999990000')
            ->set('fulfillment_method', 'pickup')
            ->set('payment_method', 'mercado_pago')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertSet('checkoutError', 'Não foi possível finalizar o pedido. Revise os dados e tente novamente.');

        $this->assertSame(1, app(CartManager::class)->count());
        $this->assertSame(10, $product->refresh()->stock_quantity);
        $this->assertDatabaseCount('orders', 0);
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
            ->set('payment_method', 'pix')
            ->set('privacy_accepted', true)
            ->call('placeOrder')
            ->assertHasNoErrors();

        $order = Order::query()->first();

        $this->assertSame(0, $order->shipping_cents);
        $this->assertSame(26000, $order->total_cents);
    }

    private function createProduct(array $overrides = []): Product
    {
        $category = Category::create([
            'name' => 'Creatina',
            'slug' => 'creatina',
            'icon' => 'CR',
            'is_active' => true,
            'is_featured' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'TEST-CHECKOUT-001',
            'stock_quantity' => 10,
            'price_cents' => 8990,
            'rating' => 4.9,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => true,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ], $overrides));
    }
}
