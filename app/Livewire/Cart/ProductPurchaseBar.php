<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Support\Cart\CartManager;
use Livewire\Component;

class ProductPurchaseBar extends Component
{
    public Product $product;

    public int $quantity = 1;

    public bool $added = false;

    public int $maximumQuantity = 1;

    public array $variantSelections = [];

    public function mount(): void
    {
        $this->variantSelections = collect($this->product->variationOptions())
            ->mapWithKeys(fn (array $variation): array => [$variation['name'] => $variation['values'][0] ?? ''])
            ->filter()
            ->all();
        $this->maximumQuantity = $this->product->availableQuantityForSelections($this->variantSelections);
    }

    public function increment(): void
    {
        $this->added = false;
        $this->quantity = min(max(1, $this->maximumQuantity), $this->quantity + 1);
    }

    public function decrement(): void
    {
        $this->added = false;
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function selectVariants(array $variantSelections): void
    {
        $this->added = false;
        $this->variantSelections = $variantSelections;
        $this->maximumQuantity = $this->product->availableQuantityForSelections($variantSelections);
        $this->quantity = min(max(1, $this->maximumQuantity), max(1, $this->quantity));
    }

    public function add(CartManager $cart): void
    {
        $availableQuantity = $this->product->availableQuantityForSelections($this->variantSelections);

        if ($availableQuantity <= 0) {
            return;
        }

        $this->quantity = min($availableQuantity, max(1, $this->quantity));
        $cart->add($this->product->id, $this->quantity, $this->variantSelections);
        $this->added = true;
        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart.product-purchase-bar');
    }
}
