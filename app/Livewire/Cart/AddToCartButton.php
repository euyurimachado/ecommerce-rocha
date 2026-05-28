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

    public function add(CartManager $cart): void
    {
        $cart->add($this->product->id);

        $this->dispatch('cart-updated');
    }

    public function render()
    {
        return view('livewire.cart.add-to-cart-button');
    }
}
