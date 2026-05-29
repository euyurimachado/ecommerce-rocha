<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Support\Cart\CartManager;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;

    public string $label = '+';

    public bool $fullWidth = false;

    public bool $redirectToCheckout = false;

    public function add(CartManager $cart)
    {
        $cart->add($this->product->id);

        $this->dispatch('cart-updated');

        if ($this->redirectToCheckout) {
            return $this->redirectRoute('checkout', navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.cart.add-to-cart-button');
    }
}
