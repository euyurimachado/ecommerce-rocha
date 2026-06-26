<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProductVariationOption extends Model
{
    protected $fillable = [
        'product_variation_id',
        'value',
        'normalized_value',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ProductVariationOption $option): void {
            $option->value = Str::of($option->value)->squish()->toString();
            $option->normalized_value = self::normalize($option->value);
        });
    }

    public static function findOrCreateForVariation(ProductVariation $variation, string $value): self
    {
        $value = Str::of($value)->squish()->toString();

        return self::firstOrCreate(
            [
                'product_variation_id' => $variation->id,
                'normalized_value' => self::normalize($value),
            ],
            ['value' => $value, 'is_active' => true],
        );
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->squish()->lower()->ascii()->toString();
    }

    public function variation(): BelongsTo
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
