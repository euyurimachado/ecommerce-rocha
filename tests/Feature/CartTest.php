<?php

namespace Tests\Feature;

use App\Livewire\Cart\AddToCartButton;
use App\Livewire\Cart\CartPage;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Support\Cart\CartManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_added_to_session_cart(): void
    {
        $product = $this->createProduct();

        Livewire::test(AddToCartButton::class, ['product' => $product])
            ->call('add')
            ->assertDispatched('cart-updated');

        $this->assertSame(1, app(CartManager::class)->count());
        $this->assertSame(8990, app(CartManager::class)->subtotalCents());
    }

    public function test_buy_now_adds_product_and_redirects_to_checkout(): void
    {
        $product = $this->createProduct();

        Livewire::test(AddToCartButton::class, [
            'product' => $product,
            'label' => 'Comprar agora',
            'fullWidth' => true,
            'redirectToCheckout' => true,
        ])
            ->call('add')
            ->assertDispatched('cart-updated')
            ->assertRedirect(route('checkout'));

        $this->assertSame(1, app(CartManager::class)->count());
    }

    public function test_cart_page_renders_items_and_summary(): void
    {
        $product = $this->createProduct();

        app(CartManager::class)->add($product->id, 2);

        $this->get(route('cart'))
            ->assertOk()
            ->assertSee('Creatina Monohidratada 300g')
            ->assertSee('R$ 179,80');
    }

    public function test_coupon_can_be_applied_to_cart(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id, 2);

        Coupon::create([
            'code' => 'ROCHA10',
            'name' => 'Primeira compra',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        Livewire::test(CartPage::class)
            ->set('couponCode', 'rocha10')
            ->call('applyCoupon')
            ->assertSet('couponCode', 'ROCHA10')
            ->assertSee('Cupom ROCHA10')
            ->assertSee('- R$ 17,98')
            ->assertSee('R$ 161,82');

        $this->assertSame(1798, app(CartManager::class)->discountCents());
        $this->assertSame(16182, app(CartManager::class)->totalCents());
    }

    public function test_invalid_coupon_shows_clear_error(): void
    {
        $product = $this->createProduct();
        app(CartManager::class)->add($product->id);

        Livewire::test(CartPage::class)
            ->set('couponCode', 'INVALIDO')
            ->call('applyCoupon')
            ->assertSee('Cupom inválido ou indisponível para este carrinho.');
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
            'sku' => 'TEST-001',
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
