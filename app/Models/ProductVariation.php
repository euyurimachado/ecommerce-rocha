<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductVariation extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
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
        static::saving(function (ProductVariation $variation): void {
            $variation->name = Str::of($variation->name)->squish()->toString();
            $variation->normalized_name = self::normalize($variation->name);
        });
    }

    public static function findOrCreateByName(string $name): self
    {
        $name = Str::of($name)->squish()->toString();

        return self::firstOrCreate(
            ['normalized_name' => self::normalize($name)],
            ['name' => $name, 'is_active' => true],
        );
    }

    public static function normalize(string $value): string
    {
        return Str::of($value)->squish()->lower()->ascii()->toString();
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductVariationOption::class)->orderBy('sort_order')->orderBy('value');
    }
}
