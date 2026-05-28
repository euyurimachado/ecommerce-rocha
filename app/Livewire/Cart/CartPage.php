<?php

namespace App\Livewire\Cart;

use App\Support\Cart\CartManager;
use Livewire\Component;

class CartPage extends Component
{
    public function increment(CartManager $cart, int $productId): void
    {
        $current = $cart->items()->firstWhere('product.id', $productId);

        $cart->update($productId, ($current['quantity'] ?? 0) + 1);
        $this->dispatch('cart-updated');
    }

    public function decrement(CartManager $cart, int $productId): void
    {
        $current = $cart->items()->firstWhere('product.id', $productId);

        $cart->update($productId, ($current['quantity'] ?? 1) - 1);
        $this->dispatch('cart-updated');
    }

    public function remove(CartManager $cart, int $productId): void
    {
        $cart->remove($productId);
        $this->dispatch('cart-updated');
    }

    public function clear(CartManager $cart): void
    {
        $cart->clear();
        $this->dispatch('cart-updated');
    }

    public function render(CartManager $cart)
    {
        return view('livewire.cart.cart-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->formattedSubtotal(),
            'subtotalCents' => $cart->subtotalCents(),
        ]);
    }
}
