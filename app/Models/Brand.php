<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    private const PACKAGED_LOGOS = [
        'body-action' => 'body-action.png',
        'central-nutrition' => 'central-nutrition.svg',
        'darkness' => 'darkness.svg',
        'dux' => 'dux.svg',
        'integral-medica' => 'integral-medica.svg',
        'integralmedica' => 'integral-medica.svg',
        'max-titanio' => 'max-titanio.svg',
        'max-titanium' => 'max-titanio.svg',
        'new-millen' => 'new-millen.png',
        'probiotica' => 'probiotica.svg',
        'sudract' => 'sudract.png',
        'super-coffe' => 'super-coffe.webp',
        'super-coffee' => 'super-coffe.webp',
        'caffeine-army' => 'super-coffe.webp',
        'z2-performance' => 'z2-performance.png',
    ];

    protected $fillable = [
        'name',
        'slug',
        'logo_path',
        'description',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if ($this->hasCustomLogo()) {
            return $this->storageLogoUrl();
        }

        $key = Str::slug($this->slug ?: $this->name);
        $packagedLogo = self::PACKAGED_LOGOS[$key]
            ?? self::PACKAGED_LOGOS[Str::slug($this->name)]
            ?? null;

        if ($packagedLogo !== null) {
            return asset('images/brands/'.$packagedLogo);
        }

        if (! $this->logo_path) {
            return null;
        }

        if (Str::startsWith($this->logo_path, ['http://', 'https://'])) {
            return $this->logo_path;
        }

        return $this->storageLogoUrl();
    }

    private function hasCustomLogo(): bool
    {
        return filled($this->logo_path)
            && Str::startsWith(
                ltrim(Str::after($this->logo_path, 'storage/'), '/'),
                'brands/custom/',
            );
    }

    private function storageLogoUrl(): string
    {
        return asset('storage/'.ltrim(Str::after($this->logo_path, 'storage/'), '/'));
    }
}
