<?php

namespace App\Livewire\Cart;

use App\Support\Cart\CartManager;
use InvalidArgumentException;
use Livewire\Component;

class CartPage extends Component
{
    public string $couponCode = '';

    public ?string $couponError = null;

    public function mount(CartManager $cart): void
    {
        $this->couponCode = $cart->coupon()?->code ?? '';
    }

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

    public function applyCoupon(CartManager $cart): void
    {
        $this->couponError = null;

        try {
            $coupon = $cart->applyCoupon($this->couponCode);
            $this->couponCode = $coupon->code;
        } catch (InvalidArgumentException $exception) {
            $this->couponError = $exception->getMessage();
        }
    }

    public function removeCoupon(CartManager $cart): void
    {
        $cart->removeCoupon();
        $this->couponCode = '';
        $this->couponError = null;
    }

    public function render(CartManager $cart)
    {
        return view('livewire.cart.cart-page', [
            'items' => $cart->items(),
            'subtotal' => $cart->formattedSubtotal(),
            'subtotalCents' => $cart->subtotalCents(),
            'coupon' => $cart->coupon(),
            'discount' => $cart->formattedDiscount(),
            'total' => $cart->formattedTotal(),
        ]);
    }
}
