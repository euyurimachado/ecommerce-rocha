<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Support\Products\DuplicateProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFormAutomationTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_form_updates_automatic_slug_and_meta_but_preserves_manual_overrides(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        Livewire::test(CreateProduct::class)
            ->set('data.name', 'Creatina Monohidratada 300g')
            ->assertSet('data.slug', 'creatina-monohidratada-300g')
            ->set('data.short_description', '<p>Creatina pura para melhorar o desempenho.</p>')
            ->assertSet('data.meta_description', 'Creatina pura para melhorar o desempenho.')
            ->set('data.slug', 'creatina-premium')
            ->set('data.meta_description', 'Descrição SEO personalizada.')
            ->set('data.name', 'Creatina Monohidratada 500g')
            ->set('data.short_description', 'Nova descrição curta.')
            ->assertSet('data.slug', 'creatina-premium')
            ->assertSet('data.meta_description', 'Descrição SEO personalizada.');
    }

    public function test_duplicated_product_form_reenables_slug_and_meta_automation(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $category = Category::create(['name' => 'Creatina', 'slug' => 'creatina', 'is_active' => true]);
        $original = Product::create([
            'category_id' => $category->id,
            'name' => 'Creatina 300g',
            'slug' => 'creatina-300g',
            'sku' => 'CRE-300',
            'short_description' => 'Descrição da creatina original.',
            'meta_description' => 'SEO original.',
            'price_cents' => 8990,
        ]);
        $copy = app(DuplicateProduct::class)($original);

        Livewire::withQueryParams(['auto_seo' => 1])
            ->test(EditProduct::class, ['record' => $copy->getKey()])
            ->set('data.name', 'Creatina 500g')
            ->assertSet('data.slug', 'creatina-500g')
            ->set('data.short_description', 'Creatina de 500g para novos objetivos.')
            ->assertSet('data.meta_description', 'Creatina de 500g para novos objetivos.');
    }

    public function test_existing_product_keeps_its_published_slug_when_name_changes(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));
        $category = Category::create(['name' => 'Creatina', 'slug' => 'creatina', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Creatina antiga',
            'slug' => 'url-publicada',
            'sku' => 'CRE-OLD',
            'price_cents' => 8990,
        ]);

        Livewire::test(EditProduct::class, ['record' => $product->getKey()])
            ->set('data.name', 'Novo nome comercial')
            ->assertSet('data.slug', 'url-publicada');
    }
}
