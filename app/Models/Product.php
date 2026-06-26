<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'sku',
        'image_path',
        'gallery_images',
        'weight',
        'variations',
        'short_description',
        'description',
        'benefits',
        'usage_instructions',
        'ingredients',
        'nutrition_facts',
        'serving_size',
        'allergen_info',
        'manufacturer_url',
        'image_source_url',
        'stock_quantity',
        'reserved_quantity',
        'price_cents',
        'compare_at_price_cents',
        'rating',
        'reviews_count',
        'sales_count',
        'is_active',
        'is_featured',
        'is_offer',
        'allows_pickup',
        'allows_local_delivery',
        'meta_title',
        'meta_description',
    ];

    protected function casts(): array
    {
        return [
            'benefits' => 'array',
            'gallery_images' => 'array',
            'nutrition_facts' => 'array',
            'variations' => 'array',
            'rating' => 'decimal:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_offer' => 'boolean',
            'allows_pickup' => 'boolean',
            'allows_local_delivery' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Product $product): void {
            $product->syncReusableVariations();
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'R$ '.number_format($this->price_cents / 100, 2, ',', '.');
    }

    public function getFormattedCompareAtPriceAttribute(): ?string
    {
        if ($this->compare_at_price_cents === null) {
            return null;
        }

        return 'R$ '.number_format($this->compare_at_price_cents / 100, 2, ',', '.');
    }

    public function getAvailableQuantityAttribute(): int
    {
        return max(0, $this->stock_quantity - $this->reserved_quantity);
    }

    public function formattedPriceForSelections(array $variantSelections = []): string
    {
        return 'R$ '.number_format($this->priceCentsForSelections($variantSelections) / 100, 2, ',', '.');
    }

    public function formattedCompareAtPriceForSelections(array $variantSelections = []): ?string
    {
        $compareAtPriceCents = $this->compareAtPriceCentsForSelections($variantSelections);

        if ($compareAtPriceCents === null) {
            return null;
        }

        return 'R$ '.number_format($compareAtPriceCents / 100, 2, ',', '.');
    }

    public function priceCentsForSelections(array $variantSelections = []): int
    {
        return $this->selectedVariationOptions($variantSelections)
            ->pluck('price_cents')
            ->filter(fn ($price): bool => $price !== null)
            ->map(fn ($price): int => (int) $price)
            ->first() ?? $this->price_cents;
    }

    public function compareAtPriceCentsForSelections(array $variantSelections = []): ?int
    {
        return $this->selectedVariationOptions($variantSelections)
            ->pluck('compare_at_price_cents')
            ->filter(fn ($price): bool => $price !== null)
            ->map(fn ($price): int => (int) $price)
            ->first() ?? $this->compare_at_price_cents;
    }

    public function skuForSelections(array $variantSelections = []): string
    {
        return $this->selectedVariationOptions($variantSelections)
            ->pluck('sku')
            ->map(fn ($sku): string => trim((string) $sku))
            ->first(fn (string $sku): bool => $sku !== '') ?? $this->sku;
    }

    public function availableQuantityForSelections(array $variantSelections = []): int
    {
        $variantQuantities = $this->selectedVariationOptions($variantSelections)
            ->filter(fn (array $option): bool => $option['stock_quantity'] !== null)
            ->map(fn (array $option): int => max(0, (int) $option['stock_quantity'] - (int) ($option['reserved_quantity'] ?? 0)));

        if ($variantQuantities->isEmpty()) {
            return $this->available_quantity;
        }

        return min($variantQuantities->all());
    }

    public function decrementStockForSelections(array $variantSelections, int $quantity): void
    {
        $matchedStockOptions = 0;
        $variations = collect($this->variations ?? [])
            ->map(function (array $variation) use ($variantSelections, $quantity, &$matchedStockOptions): array {
                $name = trim((string) ($variation['name'] ?? ''));
                $selectedValue = trim((string) ($variantSelections[$name] ?? ''));

                if ($name === '' || $selectedValue === '') {
                    return $variation;
                }

                $variation['options'] = collect($variation['options'] ?? $variation['values'] ?? [])
                    ->map(function ($option) use ($selectedValue, $quantity, &$matchedStockOptions) {
                        if (! is_array($option)) {
                            $option = ['value' => $option];
                        }

                        $value = trim((string) ($option['value'] ?? $option['name'] ?? ''));

                        if ($value !== $selectedValue || ! array_key_exists('stock_quantity', $option) || $option['stock_quantity'] === null) {
                            return $option;
                        }

                        $option['stock_quantity'] = max(0, (int) $option['stock_quantity'] - $quantity);
                        $matchedStockOptions++;

                        return $option;
                    })
                    ->values()
                    ->all();

                return $variation;
            })
            ->values()
            ->all();

        if ($matchedStockOptions > 0) {
            $this->forceFill(['variations' => $variations])->save();

            return;
        }

        $this->decrement('stock_quantity', $quantity);
    }

    public function galleryImageUrls(): array
    {
        return collect([$this->image_path])
            ->merge($this->gallery_images ?? [])
            ->merge($this->variationImagePaths())
            ->filter()
            ->unique()
            ->map(fn (string $path): string => asset('storage/'.$path))
            ->whenEmpty(fn ($images) => $images->push(asset('images/products/placeholder.svg')))
            ->values()
            ->all();
    }

    public function variationOptions(): array
    {
        return collect($this->variations ?? [])
            ->map(function (array $variation): ?array {
                $name = trim((string) ($variation['name'] ?? ''));
                $options = collect($variation['options'] ?? $variation['values'] ?? [])
                    ->map(function ($option): ?array {
                        if (is_array($option)) {
                            $optionData = $option;
                            $value = trim((string) ($option['value'] ?? $option['name'] ?? ''));
                            $imagePath = $this->variationOptionImagePath($option);
                        } else {
                            $optionData = ['value' => $option];
                            $value = trim((string) $option);
                            $imagePath = null;
                        }

                        if ($value === '') {
                            return null;
                        }

                        return [
                            'value' => $value,
                            'image_path' => $imagePath,
                            'image_url' => $imagePath ? asset('storage/'.$imagePath) : null,
                            'sku' => trim((string) ($optionData['sku'] ?? '')) ?: null,
                            'price_cents' => $this->nullableInteger($optionData['price_cents'] ?? null),
                            'compare_at_price_cents' => $this->nullableInteger($optionData['compare_at_price_cents'] ?? null),
                            'stock_quantity' => $this->nullableInteger($optionData['stock_quantity'] ?? null),
                            'reserved_quantity' => $this->nullableInteger($optionData['reserved_quantity'] ?? 0) ?? 0,
                        ];
                    })
                    ->filter()
                    ->unique('value')
                    ->values()
                    ->all();
                $values = collect($options)
                    ->pluck('value')
                    ->all();

                if ($name === '' || $options === []) {
                    return null;
                }

                return [
                    'name' => $name,
                    'options' => $options,
                    'values' => $values,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function variationImagePaths(): array
    {
        return collect($this->variations ?? [])
            ->flatMap(fn (array $variation): array => $variation['options'] ?? [])
            ->map(fn (array $option): ?string => $this->variationOptionImagePath($option))
            ->filter()
            ->values()
            ->all();
    }

    private function selectedVariationOptions(array $variantSelections): Collection
    {
        return collect($this->variationOptions())
            ->flatMap(function (array $variation) use ($variantSelections): array {
                $name = $variation['name'];
                $selectedValue = trim((string) ($variantSelections[$name] ?? ''));

                if ($selectedValue === '') {
                    $selectedValue = $variation['values'][0] ?? '';
                }

                if ($selectedValue === '') {
                    return [];
                }

                $option = collect($variation['options'])
                    ->first(fn (array $option): bool => $option['value'] === $selectedValue);

                return $option ? [$option] : [];
            });
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function variationOptionImagePath(array $option): ?string
    {
        $imagePath = trim((string) ($option['image_path'] ?? ''));

        if ($imagePath !== '' && ! str_starts_with($imagePath, 'livewire-tmp/')) {
            return $imagePath;
        }

        $uploadedImagePath = trim((string) ($option['uploaded_image_path'] ?? ''));

        if ($uploadedImagePath !== '' && ! str_starts_with($uploadedImagePath, 'livewire-tmp/')) {
            return $uploadedImagePath;
        }

        return null;
    }

    private function syncReusableVariations(): void
    {
        if (! Schema::hasTable('product_variations') || ! Schema::hasTable('product_variation_options')) {
            return;
        }

        foreach ($this->variationOptions() as $variationData) {
            $variation = ProductVariation::findOrCreateByName($variationData['name']);

            foreach ($variationData['values'] as $value) {
                ProductVariationOption::findOrCreateForVariation($variation, $value);
            }
        }
    }
}
