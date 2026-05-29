<?php

namespace Tests\Feature;

use App\Livewire\Checkout\CheckoutPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Support\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->assertSame(17980, $order->total_cents);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(8, $product->refresh()->stock_quantity);
        $this->assertSame(0, app(CartManager::class)->count());
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
            'sku' => 'TEST-CHECKOUT-001',
            'stock_quantity' => 10,
            'price_cents' => 8990,
            'rating' => 4.9,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => true,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);
    }
}
