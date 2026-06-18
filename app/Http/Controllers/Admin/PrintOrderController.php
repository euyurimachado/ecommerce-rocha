<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\View\View;

class PrintOrderController extends Controller
{
    public function __invoke(Order $order): View
    {
        $user = auth()->user();

        abort_unless(
            $user instanceof FilamentUser && $user->canAccessPanel(Filament::getPanel('admin')),
            403,
        );

        $order->loadMissing('items');

        return view('admin.orders.print', [
            'order' => $order,
        ]);
    }
}
