<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\Products\DuplicateProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_is_duplicated_as_an_inactive_editable_copy(): void
    {
        $category = Category::create([
            'name' => 'Creatina', 'slug' => 'creatina', 'icon' => 'CR', 'is_active' => true, 'is_featured' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'CRE-300',
            'image_path' => 'products/creatina.webp',
            'gallery_images' => ['products/gallery/creatina-2.webp'],
            'variations' => [['name' => 'Sabor', 'options' => [['value' => 'Natural', 'sku' => 'CRE-300-NAT']]]],
            'description' => 'Descrição comercial',
            'price_cents' => 8990,
            'stock_quantity' => 8,
            'sales_count' => 15,
            'rating' => 4.9,
            'reviews_count' => 20,
            'is_active' => true,
        ]);

        $copy = app(DuplicateProduct::class)($product);

        $this->assertNotSame($product->id, $copy->id);
        $this->assertSame('Cópia de '.$product->name, $copy->name);
        $this->assertNotSame($product->slug, $copy->slug);
        $this->assertNotSame($product->sku, $copy->sku);
        $this->assertSame($product->description, $copy->description);
        $this->assertSame($product->gallery_images, $copy->gallery_images);
        $this->assertSame('CRE-300-NAT-COPIA', data_get($copy->variations, '0.options.0.sku'));
        $this->assertFalse($copy->is_active);
        $this->assertSame(0, $copy->sales_count);
    }

    public function test_multiple_copies_receive_unique_slug_and_sku(): void
    {
        $category = Category::create([
            'name' => 'Creatina', 'slug' => 'creatina', 'icon' => 'CR', 'is_active' => true, 'is_featured' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Creatina', 'slug' => 'creatina-produto', 'sku' => 'CRE', 'price_cents' => 5000,
        ]);

        $first = app(DuplicateProduct::class)($product);
        $second = app(DuplicateProduct::class)($product);

        $this->assertNotSame($first->slug, $second->slug);
        $this->assertNotSame($first->sku, $second->sku);
    }
}
