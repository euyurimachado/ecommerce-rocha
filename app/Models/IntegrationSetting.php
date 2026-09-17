<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationSetting extends Model
{
    protected $fillable = [
        'type', 'provider', 'enabled', 'environment', 'credentials', 'settings',
        'connected_at', 'last_tested_at', 'last_test_status', 'last_error',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'connected_at' => 'datetime',
            'last_tested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $integration): void {
            if (! $integration->enabled) {
                return;
            }

            static::query()
                ->where('type', $integration->type)
                ->where($integration->getKeyName(), '!=', $integration->getKey())
                ->update(['enabled' => false]);
        });
    }

    public static function active(string $type): ?self
    {
        return static::query()->where('type', $type)->where('enabled', true)->first();
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials ?? [], $key, $default);
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings ?? [], $key, $default);
    }

    public function markTested(bool $success, ?string $error = null): void
    {
        $this->forceFill([
            'last_tested_at' => now(),
            'last_test_status' => $success ? 'connected' : 'failed',
            'last_error' => $success ? null : $error,
            'connected_at' => $success ? ($this->connected_at ?? now()) : $this->connected_at,
        ])->save();
    }
}
