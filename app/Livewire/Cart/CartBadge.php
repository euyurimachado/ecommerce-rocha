<?php

namespace App\Livewire\Cart;

use App\Support\Cart\CartManager;
use Livewire\Attributes\On;
use Livewire\Component;

class CartBadge extends Component
{
    public string $variant = 'default';

    #[On('cart-updated')]
    public function refresh(): void
    {
        //
    }

    public function render(CartManager $cart)
    {
        return view('livewire.cart.cart-badge', [
            'count' => $cart->count(),
        ]);
    }
}
