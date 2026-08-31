<?php

namespace App\Support\Products;

use App\Models\Product;
use Illuminate\Support\Str;

class DuplicateProduct
{
    public function __invoke(Product $product): Product
    {
        $copy = $product->replicate();
        $copy->forceFill([
            'name' => 'Cópia de '.$product->name,
            'slug' => $this->uniqueValue('slug', Str::slug('copia-de-'.$product->name)),
            'sku' => $this->uniqueValue('sku', $product->sku.'-COPIA'),
            'variations' => $this->duplicateVariations($product->variations ?? []),
            'is_active' => false,
            'sales_count' => 0,
            'reviews_count' => 0,
            'rating' => 0,
        ]);
        $copy->save();

        return $copy;
    }

    private function uniqueValue(string $column, string $base): string
    {
        $candidate = $base;
        $suffix = 2;

        while (Product::query()->where($column, $candidate)->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }

    private function duplicateVariations(array $variations): array
    {
        return collect($variations)->map(function (array $variation): array {
            $variation['options'] = collect($variation['options'] ?? [])->map(function ($option) {
                if (! is_array($option) || blank($option['sku'] ?? null)) {
                    return $option;
                }

                $option['sku'] = $option['sku'].'-COPIA';

                return $option;
            })->all();

            return $variation;
        })->all();
    }
}
