<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'minimum_subtotal_cents',
        'maximum_discount_cents',
        'usage_limit',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function isAvailableForSubtotal(int $subtotalCents): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return $subtotalCents >= $this->minimum_subtotal_cents;
    }

    public function discountFor(int $subtotalCents): int
    {
        if (! $this->isAvailableForSubtotal($subtotalCents)) {
            return 0;
        }

        $discount = $this->type === 'percent'
            ? (int) floor($subtotalCents * min($this->value, 100) / 100)
            : $this->value;

        if ($this->maximum_discount_cents !== null) {
            $discount = min($discount, $this->maximum_discount_cents);
        }

        return min($subtotalCents, max(0, $discount));
    }

    public function getFormattedMinimumSubtotalAttribute(): string
    {
        return 'R$ '.number_format($this->minimum_subtotal_cents / 100, 2, ',', '.');
    }
}
