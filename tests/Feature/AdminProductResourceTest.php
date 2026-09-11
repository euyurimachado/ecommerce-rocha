<?php

namespace Tests\Feature;

use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminProductResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_product_table_column_can_be_shown_or_hidden(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $columns = Livewire::test(ListProducts::class)
            ->instance()
            ->getTable()
            ->getColumns();

        foreach ($columns as $column) {
            $this->assertTrue($column->isToggleable(), "A coluna {$column->getName()} deveria ser configurable.");
        }
    }

    public function test_product_table_filters_records_by_status_category_brand_and_offer(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $creatine = Category::create(['name' => 'Creatina', 'slug' => 'creatina', 'is_active' => true]);
        $whey = Category::create(['name' => 'Whey', 'slug' => 'whey', 'is_active' => true]);
        $brandA = Brand::create(['name' => 'Marca A', 'slug' => 'marca-a', 'is_active' => true]);
        $brandB = Brand::create(['name' => 'Marca B', 'slug' => 'marca-b', 'is_active' => true]);

        $matching = $this->createProduct($creatine, $brandA, 'Creatina em oferta', true, true);
        $inactive = $this->createProduct($creatine, $brandA, 'Creatina inativa', false, true);
        $otherCategory = $this->createProduct($whey, $brandA, 'Whey em oferta', true, true);
        $otherBrand = $this->createProduct($creatine, $brandB, 'Creatina outra marca', true, true);
        $notOffer = $this->createProduct($creatine, $brandA, 'Creatina sem oferta', true, false);

        Livewire::test(ListProducts::class)
            ->filterTable('is_active', true)
            ->filterTable('category_id', $creatine)
            ->filterTable('brand_id', $brandA)
            ->filterTable('is_offer', true)
            ->assertCanSeeTableRecords([$matching])
            ->assertCanNotSeeTableRecords([$inactive, $otherCategory, $otherBrand, $notOffer])
            ->removeTableFilter('brand_id')
            ->assertCanSeeTableRecords([$matching, $otherBrand])
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$matching, $inactive, $otherCategory, $otherBrand, $notOffer]);
    }

    private function createProduct(Category $category, Brand $brand, string $name, bool $active, bool $offer): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => $name,
            'slug' => str($name)->slug(),
            'sku' => 'SKU-'.str()->random(8),
            'price_cents' => 10000,
            'stock_quantity' => 5,
            'is_active' => $active,
            'is_offer' => $offer,
        ]);
    }
}
