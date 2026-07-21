<?php

namespace Tests\Feature;

use App\Livewire\Favorites\FavoriteToggle;
use App\Models\Category;
use App\Models\Product;
use App\Support\Favorites\FavoriteManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FavoritesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_open_empty_favorites_page(): void
    {
        $this->get('/favoritos')
            ->assertOk()
            ->assertSee('Sua lista está vazia')
            ->assertSee('Explorar produtos');
    }

    public function test_customer_can_toggle_product_as_favorite(): void
    {
        $product = $this->createProduct();

        Livewire::test(FavoriteToggle::class, ['product' => $product])
            ->assertSet('isFavorited', false)
            ->call('toggle')
            ->assertSet('isFavorited', true)
            ->call('toggle')
            ->assertSet('isFavorited', false);

        $this->assertFalse(app(FavoriteManager::class)->has($product->id));
    }

    public function test_favorites_page_shows_session_products(): void
    {
        $product = $this->createProduct([
            'name' => 'Whey Protein Concentrado 900g',
            'slug' => 'whey-protein-concentrado-900g',
        ]);

        app(FavoriteManager::class)->add($product->id);

        $this->get('/favoritos')
            ->assertOk()
            ->assertSee('Produtos salvos')
            ->assertSee('Whey Protein Concentrado 900g');
    }

    public function test_inactive_products_are_hidden_from_favorites(): void
    {
        $product = $this->createProduct([
            'name' => 'Produto Inativo',
            'slug' => 'produto-inativo',
            'is_active' => false,
        ]);

        session()->put('favorites.product_ids', [$product->id]);

        $this->get('/favoritos')
            ->assertOk()
            ->assertSee('Sua lista está vazia')
            ->assertDontSee('Produto Inativo');
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

        $attributes = array_merge([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'FAV-001',
            'price_cents' => 8990,
            'rating' => 4.9,
            'is_active' => true,
            'is_featured' => true,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ], $overrides);

        if (array_key_exists('slug', $overrides) && ! array_key_exists('sku', $overrides)) {
            $attributes['sku'] = 'FAV-'.substr(md5($attributes['slug']), 0, 8);
        }

        return Product::create($attributes);
    }
}
