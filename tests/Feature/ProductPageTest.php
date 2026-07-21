<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_variation_option_can_reference_gallery_image(): void
    {
        $category = Category::create([
            'name' => 'Whey Protein',
            'slug' => 'whey-protein',
            'icon' => 'WP',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Whey Protein 900g',
            'slug' => 'whey-protein-900g',
            'sku' => 'PAGE-001',
            'image_path' => 'products/whey.jpg',
            'gallery_images' => ['products/gallery/whey-baunilha.jpg'],
            'variations' => [[
                'name' => 'Sabor',
                'options' => [[
                    'value' => 'Baunilha',
                    'image_path' => 'products/gallery/whey-baunilha.jpg',
                ]],
            ]],
            'description' => '<p>Primeiro parágrafo.</p><p><strong>Segundo parágrafo em negrito.</strong></p>',
            'price_cents' => 12990,
            'rating' => 4.8,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => false,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-variation-value="Baunilha"', false)
            ->assertSee('data-variation-image="'.asset('storage/products/gallery/whey-baunilha.jpg').'"', false)
            ->assertSee('<strong>Segundo parágrafo em negrito.</strong>', false)
            ->assertDontSee('PAGE-001')
            ->assertDontSee('Estoque');
    }

    public function test_product_gallery_includes_image_uploaded_directly_to_variation_option(): void
    {
        $category = Category::create([
            'name' => 'Whey Protein',
            'slug' => 'whey-protein',
            'icon' => 'WP',
            'is_active' => true,
            'is_featured' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Whey Protein Isolado',
            'slug' => 'whey-protein-isolado',
            'sku' => 'PAGE-002',
            'image_path' => 'products/whey-isolado.jpg',
            'variations' => [[
                'name' => 'Sabor',
                'options' => [[
                    'value' => 'Morango',
                    'uploaded_image_path' => 'products/gallery/whey-morango.jpg',
                ]],
            ]],
            'price_cents' => 14990,
            'rating' => 4.8,
            'is_active' => true,
            'is_featured' => true,
            'is_offer' => false,
            'allows_pickup' => true,
            'allows_local_delivery' => true,
        ]);

        $variationImageUrl = asset('storage/products/gallery/whey-morango.jpg');

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-product-gallery-thumb="'.$variationImageUrl.'"', false)
            ->assertSee('data-variation-image="'.$variationImageUrl.'"', false);
    }
}
