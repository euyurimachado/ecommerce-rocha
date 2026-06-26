<?php

namespace App\Support\Cart;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CartManager
{
    private const SESSION_KEY = 'cart.items';

    private const COUPON_SESSION_KEY = 'cart.coupon_code';

    public function add(int $productId, int $quantity = 1, array $variantSelections = []): void
    {
        $product = $this->findPurchasableProduct($productId);
        $items = $this->rawItems();
        $variantSelections = $this->normalizeVariantSelections($product, $variantSelections);
        $itemKey = $this->itemKey($product->id, $variantSelections);
        $currentQuantity = $items[$itemKey]['quantity'] ?? 0;
        $availableQuantity = $product->availableQuantityForSelections($variantSelections);

        $items[$itemKey] = [
            'product_id' => $product->id,
            'quantity' => min($availableQuantity, $currentQuantity + max(1, $quantity)),
            'variant_selections' => $variantSelections,
        ];

        $this->store($items);
    }

    public function update(string|int $itemKey, int $quantity): void
    {
        $items = $this->rawItems();

        if ($quantity <= 0) {
            unset($items[$itemKey]);
            $this->store($items);

            return;
        }

        $item = $items[$itemKey] ?? null;

        if (! $item) {
            return;
        }

        $product = $this->findPurchasableProduct((int) $item['product_id']);
        $variantSelections = $item['variant_selections'] ?? [];

        $items[$itemKey] = [
            'product_id' => $product->id,
            'quantity' => min($product->availableQuantityForSelections($variantSelections), $quantity),
            'variant_selections' => $variantSelections,
        ];

        $this->store($items);
    }

    public function remove(string|int $itemKey): void
    {
        $items = $this->rawItems();
        unset($items[$itemKey]);

        $this->store($items);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
        $this->removeCoupon();
    }

    public function items(): Collection
    {
        $items = collect($this->rawItems());

        if ($items->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with(['brand', 'category'])
            ->whereIn('id', $items->pluck('product_id')->unique())
            ->get()
            ->keyBy('id');

        return $items
            ->map(function (array $item, string|int $itemKey) use ($products) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    return null;
                }

                $variantSelections = $item['variant_selections'] ?? [];
                $quantity = min((int) $item['quantity'], $product->availableQuantityForSelections($variantSelections));
                $unitPriceCents = $product->priceCentsForSelections($variantSelections);

                return [
                    'key' => (string) $itemKey,
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price_cents' => $unitPriceCents,
                    'product_sku' => $product->skuForSelections($variantSelections),
                    'variant_selections' => $variantSelections,
                    'variant_summary' => $this->variantSummary($variantSelections),
                    'line_total_cents' => $unitPriceCents * $quantity,
                ];
            })
            ->filter()
            ->values();
    }

    public function count(): int
    {
        return $this->items()->sum('quantity');
    }

    public function subtotalCents(): int
    {
        return $this->items()->sum('line_total_cents');
    }

    public function applyCoupon(string $code): Coupon
    {
        $coupon = Coupon::query()
            ->where('code', mb_strtoupper(trim($code)))
            ->first();

        if (! $coupon || ! $coupon->isAvailableForSubtotal($this->subtotalCents())) {
            throw new InvalidArgumentException('Cupom inválido ou indisponível para este carrinho.');
        }

        session()->put(self::COUPON_SESSION_KEY, $coupon->code);

        return $coupon;
    }

    public function removeCoupon(): void
    {
        session()->forget(self::COUPON_SESSION_KEY);
    }

    public function coupon(): ?Coupon
    {
        $code = session()->get(self::COUPON_SESSION_KEY);

        if (! $code) {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', $code)
            ->first();

        if (! $coupon || ! $coupon->isAvailableForSubtotal($this->subtotalCents())) {
            $this->removeCoupon();

            return null;
        }

        return $coupon;
    }

    public function discountCents(): int
    {
        return $this->coupon()?->discountFor($this->subtotalCents()) ?? 0;
    }

    public function totalCents(): int
    {
        return max(0, $this->subtotalCents() - $this->discountCents());
    }

    public function formattedSubtotal(): string
    {
        return $this->formatCurrency($this->subtotalCents());
    }

    public function formattedDiscount(): string
    {
        return $this->formatCurrency($this->discountCents());
    }

    public function formattedTotal(): string
    {
        return $this->formatCurrency($this->totalCents());
    }

    public function formatCurrency(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    private function rawItems(): array
    {
        return collect(session()->get(self::SESSION_KEY, []))
            ->mapWithKeys(function (array $item, string|int $key): array {
                $variantSelections = $item['variant_selections'] ?? [];
                $itemKey = is_numeric($key) ? $this->itemKey((int) $item['product_id'], $variantSelections) : (string) $key;

                return [$itemKey => [
                    'product_id' => (int) $item['product_id'],
                    'quantity' => (int) $item['quantity'],
                    'variant_selections' => $variantSelections,
                ]];
            })
            ->all();
    }

    private function store(array $items): void
    {
        session()->put(self::SESSION_KEY, array_filter($items, fn (array $item) => $item['quantity'] > 0));
    }

    private function findPurchasableProduct(int $productId): Product
    {
        return Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function itemKey(int $productId, array $variantSelections = []): string
    {
        if ($variantSelections === []) {
            return (string) $productId;
        }

        return $productId.':'.md5(json_encode($variantSelections));
    }

    private function normalizeVariantSelections(Product $product, array $variantSelections): array
    {
        return collect($product->variationOptions())
            ->mapWithKeys(function (array $variation) use ($variantSelections): array {
                $name = $variation['name'];
                $selectedValue = trim((string) ($variantSelections[$name] ?? ''));

                if ($selectedValue === '' || ! in_array($selectedValue, $variation['values'], true)) {
                    $selectedValue = $variation['values'][0] ?? '';
                }

                return $selectedValue === '' ? [] : [$name => $selectedValue];
            })
            ->filter()
            ->all();
    }

    private function variantSummary(array $variantSelections): ?string
    {
        if ($variantSelections === []) {
            return null;
        }

        return collect($variantSelections)
            ->map(fn (string $value, string $name): string => "{$name}: {$value}")
            ->implode(' / ');
    }
}
