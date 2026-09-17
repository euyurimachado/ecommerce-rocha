<?php

namespace App\Providers;

use App\Models\Order;
use App\Models\StoreSetting;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);

        View::composer('*', function ($view): void {
            try {
                $settings = Schema::hasTable('store_settings') ? StoreSetting::current() : new StoreSetting;
            } catch (\Throwable) {
                $settings = new StoreSetting;
            }

            $view->with('storeSettings', $settings);
        });
    }
}
