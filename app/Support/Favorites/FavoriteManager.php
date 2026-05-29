<?php

namespace App\Support\Favorites;

use App\Models\Product;
use Illuminate\Support\Collection;

class FavoriteManager
{
    private const SESSION_KEY = 'favorites.product_ids';

    public function toggle(int $productId): bool
    {
        if ($this->has($productId)) {
            $this->remove($productId);

            return false;
        }

        $this->add($productId);

        return true;
    }

    public function add(int $productId): void
    {
        $product = $this->findActiveProduct($productId);
        $ids = $this->ids();

        if (! in_array($product->id, $ids, true)) {
            $ids[] = $product->id;
        }

        $this->store($ids);
    }

    public function remove(int $productId): void
    {
        $this->store(array_values(array_filter(
            $this->ids(),
            fn (int $id) => $id !== $productId,
        )));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return $this->products()->count();
    }

    public function products(): Collection
    {
        $ids = $this->ids();

        if ($ids === []) {
            return collect();
        }

        return Product::query()
            ->with(['brand', 'category'])
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->sortBy(fn (Product $product) => array_search($product->id, $ids, true))
            ->values();
    }

    private function ids(): array
    {
        return collect(session()->get(self::SESSION_KEY, []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function store(array $ids): void
    {
        session()->put(self::SESSION_KEY, collect($ids)->unique()->values()->all());
    }

    private function findActiveProduct(int $productId): Product
    {
        return Product::query()
            ->whereKey($productId)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
