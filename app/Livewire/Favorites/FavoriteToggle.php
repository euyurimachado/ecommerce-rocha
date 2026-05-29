<?php

namespace App\Livewire\Favorites;

use App\Models\Product;
use App\Support\Favorites\FavoriteManager;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class FavoriteToggle extends Component
{
    public Product $product;

    public bool $isFavorited = false;

    public bool $compact = false;

    public function mount(Product $product, FavoriteManager $favorites, bool $compact = false): void
    {
        $this->product = $product;
        $this->compact = $compact;
        $this->isFavorited = $favorites->has($product->id);
    }

    public function toggle(FavoriteManager $favorites): void
    {
        $this->isFavorited = $favorites->toggle($this->product->id);

        $this->dispatch('favorites-updated');
    }

    public function render(): View
    {
        return view('livewire.favorites.favorite-toggle');
    }
}
