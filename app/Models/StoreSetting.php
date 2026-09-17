<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class StoreSetting extends Model
{
    public const FONTS = [
        'Ubuntu', 'Inter', 'Montserrat', 'Poppins', 'Roboto', 'Lato', 'Nunito',
        'Open Sans', 'Raleway', 'Source Sans 3',
    ];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['installed_at' => 'datetime'];
    }

    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'name' => config('app.name', 'Minha loja'),
            'short_name' => (string) str(config('app.name', 'Loja'))->before(' '),
            'slogan' => 'Produtos selecionados para você.',
            'primary_color' => '#0098d7',
            'primary_dark_color' => '#005d8f',
            'secondary_color' => '#a7a9ac',
            'accent_color' => '#f59e0b',
            'background_color' => '#f8fafc',
            'font_family' => 'Ubuntu',
            'country' => 'BR',
        ]);
    }

    public function isInstalled(): bool
    {
        return $this->exists && $this->installed_at !== null;
    }

    public static function installationDetected(): bool
    {
        try {
            if (! Schema::hasTable('store_settings')) {
                return false;
            }

            if (static::query()->whereNotNull('installed_at')->exists()) {
                return true;
            }

            return Schema::hasTable('users')
                && User::query()
                    ->where('role', User::ROLE_ADMIN)
                    ->where('is_active', true)
                    ->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    public function assetUrl(string $attribute, string $fallback): string
    {
        $path = trim((string) $this->getAttribute($attribute));

        return $path !== '' ? asset('storage/'.$path) : asset($fallback);
    }

    public function assetMime(string $attribute, string $fallback = 'image/svg+xml'): string
    {
        return match (strtolower(pathinfo((string) $this->getAttribute($attribute), PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => $fallback,
        };
    }
}
