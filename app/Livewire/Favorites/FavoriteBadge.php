<?php

namespace App\Livewire\Favorites;

use App\Support\Favorites\FavoriteManager;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class FavoriteBadge extends Component
{
    #[On('favorites-updated')]
    public function render(FavoriteManager $favorites): View
    {
        return view('livewire.favorites.favorite-badge', [
            'count' => $favorites->count(),
        ]);
    }
}
