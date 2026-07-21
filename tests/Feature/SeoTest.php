<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_public_storefront_urls(): void
    {
        $category = Category::create([
            'name' => 'Creatina',
            'slug' => 'creatina',
            'icon' => 'CR',
            'is_active' => true,
            'is_featured' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Creatina Monohidratada 300g',
            'slug' => 'creatina-monohidratada-300g',
            'sku' => 'SEO-001',
            'price_cents' => 8990,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Produto Inativo',
            'slug' => 'produto-inativo',
            'sku' => 'SEO-002',
            'price_cents' => 8990,
            'is_active' => false,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('content-type', 'application/xml')
            ->assertSee(route('home'), false)
            ->assertSee(route('categories.show', $category), false)
            ->assertSee(route('products.show', Product::where('slug', 'creatina-monohidratada-300g')->first()), false)
            ->assertDontSee('produto-inativo');
    }

    public function test_robots_points_to_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: https://rochasports.com.br/sitemap.xml');
    }
}
