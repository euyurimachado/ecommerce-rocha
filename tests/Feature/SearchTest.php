<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_finds_product_by_name_brand_and_category(): void
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

        $this->get(route('search', ['q' => 'Integralmedica']))
            ->assertOk()
            ->assertSee($creatine->name)
            ->assertDontSee('Whey Protein 900g');

        $this->get(route('search', ['q' => 'Creatina']))
            ->assertOk()
            ->assertSee($creatine->name);
    }

    public function test_search_can_filter_by_category(): void
    {
        $this->createProduct(
            name: 'Creatina Monohidratada 300g',
            categoryName: 'Creatina',
            brandName: 'Integralmedica',
        );

        $this->createProduct(
            name: 'Whey Protein 900g',
            categoryName: 'Whey Protein',
            brandName: 'Max Titanium',
        );

        $this->get(route('search', ['categoria' => 'whey-protein']))
            ->assertOk()
            ->assertSee('Whey Protein 900g')
            ->assertDontSee('Creatina Monohidratada 300g');
    }

    public function test_search_hides_inactive_products(): void
    {
        $this->createProduct(
            name: 'Produto Inativo',
            categoryName: 'Creatina',
            brandName: 'Integralmedica',
            active: false,
        );

        $this->get(route('search', ['q' => 'Produto Inativo']))
            ->assertOk()
            ->assertSee('Nenhum produto encontrado')
            ->assertSee('0 produtos encontrados');
    }

    private function createProduct(string $name, string $categoryName, string $brandName, bool $active = true): Product
    {
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
            'sku' => 'TEST-'.str()->random(8),
            'short_description' => 'Produto para busca.',
            'stock_quantity' => 10,
            'price_cents' => 8990,
            'rating' => 4.8,
            'sales_count' => 10,
            'is_active' => $active,
            'is_featured' => true,
            'is_offer' => false,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);
    }
}
