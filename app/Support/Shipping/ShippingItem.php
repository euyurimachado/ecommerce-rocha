<?php

namespace App\Support\Shipping;

final readonly class ShippingItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public int $quantity,
        public int $valueCents,
        public bool $requiresShipping,
        public ?float $weightKg,
        public ?float $widthCm,
        public ?float $heightCm,
        public ?float $lengthCm,
    ) {}

    public function validateDimensions(): void
    {
        if (! $this->requiresShipping) {
            return;
        }

        if (min($this->weightKg ?? 0, $this->widthCm ?? 0, $this->heightCm ?? 0, $this->lengthCm ?? 0) <= 0) {
            throw new MissingShippingDimensions("O produto {$this->name} não possui peso e dimensões válidos para calcular o frete.");
        }
    }
}
