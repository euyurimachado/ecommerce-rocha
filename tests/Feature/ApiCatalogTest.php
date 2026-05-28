<?php

namespace Tests\Feature;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_home_returns_app_catalog_sections(): void
    {
        $product = $this->createProduct(
            name: 'Creatina Monohidratada 300g',
            categoryName: 'Creatina',
            brandName: 'Integralmedica',
            isOffer: true,
        );

        Banner::create([
            'title' => 'Suplementos originais com entrega rápida em Campos',
            'subtitle' => 'Compra rápida para atletas locais.',
            'cta_label' => 'Ver ofertas',
            'placement' => 'home_hero',
            'device' => 'all',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/home')
            ->assertOk()
            ->assertJsonPath('data.banner.title', 'Suplementos originais com entrega rápida em Campos')
            ->assertJsonPath('data.categories.0.name', 'Creatina')
            ->assertJsonPath('data.best_sellers.0.slug', $product->slug)
            ->assertJsonPath('data.offers.0.slug', $product->slug)
            ->assertJsonPath('data.brands.0.name', 'Integralmedica');
    }

    public function test_api_products_can_search_and_filter_catalog(): void
    {
        $creatine = $this->createProduct(
            name: 'Creatina Monohidratada 300g',
            categoryName: 'Creatina',
            brandName: 'Integralmedica',
        );

        $this->createProduct(
            name: 'Whey Protein 900g',
            categoryName: 'Whey Protein',
            brandName: 'Max Titanium',
        );

        $this->getJson('/api/v1/products?q=Integralmedica&category=creatina')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', $creatine->slug)
            ->assertJsonPath('data.0.price.formatted', 'R$ 89,90');
    }

    public function test_api_product_detail_hides_inactive_products(): void
    {
        $activeProduct = $this->createProduct(
            name: 'Whey Protein 900g',
            categoryName: 'Whey Protein',
            brandName: 'Max Titanium',
        );

        $inactiveProduct = $this->createProduct(
            name: 'Produto Inativo',
            categoryName: 'Whey Protein',
            brandName: 'Max Titanium',
            active: false,
        );

        $this->getJson('/api/v1/products/'.$activeProduct->slug)
            ->assertOk()
            ->assertJsonPath('data.name', 'Whey Protein 900g')
            ->assertJsonPath('data.category.name', 'Whey Protein')
            ->assertJsonPath('data.brand.name', 'Max Titanium');

        $this->getJson('/api/v1/products/'.$inactiveProduct->slug)
            ->assertNotFound();
    }

    private function createProduct(
        string $name,
        string $categoryName,
        string $brandName,
        bool $active = true,
        bool $isOffer = false,
    ): Product {
        $category = Category::firstOrCreate(
            ['slug' => str($categoryName)->slug()->toString()],
            [
                'name' => $categoryName,
                'icon' => strtoupper(substr($categoryName, 0, 2)),
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        $brand = Brand::firstOrCreate(
            ['slug' => str($brandName)->slug()->toString()],
            [
                'name' => $brandName,
                'is_active' => true,
                'is_featured' => true,
            ],
        );

        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug()->toString(),
            'sku' => 'API-'.str()->random(8),
            'short_description' => 'Produto para API.',
            'description' => 'Descrição completa do produto.',
            'benefits' => ['Produto original', 'Entrega rápida'],
            'usage_instructions' => 'Consulte o rótulo.',
            'ingredients' => 'Confira a tabela nutricional.',
            'stock_quantity' => 10,
            'price_cents' => 8990,
            'rating' => 4.8,
            'sales_count' => 10,
            'is_active' => $active,
            'is_featured' => true,
            'is_offer' => $isOffer,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);
    }
}
