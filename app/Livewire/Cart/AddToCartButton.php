<?php

namespace App\Livewire\Cart;

use App\Models\Product;
use App\Support\Cart\CartManager;
use InvalidArgumentException;
use Livewire\Component;

class AddToCartButton extends Component
{
    public Product $product;

    public string $label = '+';

    public bool $fullWidth = false;

    public bool $redirectToCheckout = false;

    public ?string $stockError = null;

    public function add(CartManager $cart, array $variantSelections = [])
    {
        $this->stockError = null;
        $subtotalBeforeCents = $cart->subtotalCents();

        try {
            $cart->add($this->product->id, variantSelections: $variantSelections);
        } catch (InvalidArgumentException $exception) {
            $this->stockError = $exception->getMessage();

            return null;
        }

        $this->dispatch('rocha-meta-add-to-cart',
            contentId: (string) $this->product->id,
            value: max(0, $cart->subtotalCents() - $subtotalBeforeCents) / 100,
            quantity: 1,
        );
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
