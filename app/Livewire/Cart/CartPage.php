<?php

namespace App\Livewire\Cart;

use App\Support\Cart\CartManager;
use InvalidArgumentException;
use Livewire\Component;

class CartPage extends Component
{
    public string $couponCode = '';

    public ?string $couponError = null;

    public ?string $stockError = null;

    public function mount(CartManager $cart): void
    {
        $this->couponCode = $cart->coupon()?->code ?? '';
    }

    public function increment(CartManager $cart, string $itemKey): void
    {
        $this->stockError = null;
        $current = $cart->items()->firstWhere('key', $itemKey);

        try {
            $cart->update($itemKey, ($current['quantity'] ?? 0) + 1);
        } catch (InvalidArgumentException $exception) {
            $this->stockError = $exception->getMessage();

            return;
        }

        $this->dispatch('cart-updated');
    }

    public function decrement(CartManager $cart, string $itemKey): void
    {
        $current = $cart->items()->firstWhere('key', $itemKey);

        $cart->update($itemKey, ($current['quantity'] ?? 1) - 1);
        $this->dispatch('cart-updated');
    }

    public function remove(CartManager $cart, string $itemKey): void
    {
        $cart->remove($itemKey);
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
