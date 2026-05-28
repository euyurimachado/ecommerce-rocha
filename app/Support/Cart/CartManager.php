<?php

namespace App\Support\Cart;

use App\Models\Product;
use Illuminate\Support\Collection;

class CartManager
{
    private const SESSION_KEY = 'cart.items';

    public function add(int $productId, int $quantity = 1): void
    {
        $product = $this->findPurchasableProduct($productId);
        $items = $this->rawItems();
        $currentQuantity = $items[$product->id]['quantity'] ?? 0;

        $items[$product->id] = [
            'product_id' => $product->id,
            'quantity' => min($product->available_quantity, $currentQuantity + max(1, $quantity)),
        ];

        $this->store($items);
    }

    public function update(int $productId, int $quantity): void
    {
        $items = $this->rawItems();

        if ($quantity <= 0) {
            unset($items[$productId]);
            $this->store($items);

            return;
        }

        $product = $this->findPurchasableProduct($productId);

        $items[$product->id] = [
            'product_id' => $product->id,
            'quantity' => min($product->available_quantity, $quantity),
        ];

        $this->store($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->rawItems();
        unset($items[$productId]);

        $this->store($items);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function items(): Collection
    {
        $items = collect($this->rawItems());

        if ($items->isEmpty()) {
            return collect();
        }

        $products = Product::query()
            ->with(['brand', 'category'])
            ->whereIn('id', $items->keys())
            ->get()
            ->keyBy('id');

        return $items
            ->map(function (array $item) use ($products) {
                $product = $products->get($item['product_id']);

                if (! $product || ! $product->is_active) {
                    return null;
                }

                $quantity = min((int) $item['quantity'], $product->available_quantity);

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'line_total_cents' => $product->price_cents * $quantity,
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

    public function formattedSubtotal(): string
    {
        return $this->formatCurrency($this->subtotalCents());
    }

    public function formatCurrency(int $cents): string
    {
        return 'R$ '.number_format($cents / 100, 2, ',', '.');
    }

    private function rawItems(): array
    {
        return session()->get(self::SESSION_KEY, []);
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
            ->where('stock_quantity', '>', 0)
            ->firstOrFail();
    }
}
