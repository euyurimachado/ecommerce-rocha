<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
            'variations' => 'array',
            'rating' => 'decimal:1',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_offer' => 'boolean',
            'allows_pickup' => 'boolean',
            'allows_local_delivery' => 'boolean',
        ];
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
                            $value = trim((string) ($option['value'] ?? $option['name'] ?? ''));
                            $imagePath = $this->variationOptionImagePath($option);
                        } else {
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
}
