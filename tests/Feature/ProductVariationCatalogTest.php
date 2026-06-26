<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariationCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_variations_are_saved_to_reusable_catalog(): void
    {
        $category = Category::create([
            'name' => 'Whey',
            'slug' => 'whey',
            'is_active' => true,
            'is_featured' => false,
        ]);
        $brand = Brand::create([
            'name' => 'Rocha',
            'slug' => 'rocha',
            'is_active' => true,
            'is_featured' => false,
        ]);

        Product::create($this->productData($category->id, $brand->id, 'whey-chocolate'));
        Product::create($this->productData($category->id, $brand->id, 'whey-chocolate-2'));

        $this->assertDatabaseHas('product_variations', [
            'name' => 'Sabor',
            'normalized_name' => 'sabor',
        ]);

        $variation = ProductVariation::where('normalized_name', 'sabor')->firstOrFail();

        $this->assertSame(1, ProductVariation::where('normalized_name', 'sabor')->count());
        $this->assertSame(1, $variation->options()->where('normalized_value', 'chocolate')->count());
    }

    private function productData(int $categoryId, int $brandId, string $slug): array
    {
        return [
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'name' => 'Whey Chocolate',
            'slug' => $slug,
            'sku' => strtoupper($slug),
            'weight' => '900g',
            'variations' => [[
                'name' => 'Sabor',
                'options' => [[
                    'value' => 'Chocolate',
                    'image_path' => null,
                ]],
            ]],
            'stock_quantity' => 10,
            'reserved_quantity' => 0,
            'price_cents' => 12990,
            'rating' => 0,
            'reviews_count' => 0,
            'sales_count' => 0,
            'is_active' => true,
            'is_featured' => false,
            'is_offer' => false,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ];
    }
}
