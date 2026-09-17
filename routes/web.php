<?php

use App\Http\Controllers\Admin\PrintOrderController;
use App\Http\Controllers\InstallerController;
use App\Http\Controllers\MelhorEnvioOAuthController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\ShippingWebhookController;
use App\Http\Controllers\StorefrontController;
use App\Http\Middleware\InstallerAccess;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/site.webmanifest', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::view('/offline', 'storefront.offline')->name('pwa.offline');
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/buscar', [StorefrontController::class, 'search'])->name('search');
Route::get('/categorias/{category:slug}', [StorefrontController::class, 'category'])->name('categories.show');
Route::get('/produto/{product:slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::view('/favoritos', 'storefront.favorites')->name('favorites.index');
Route::view('/carrinho', 'storefront.cart')->name('cart');
Route::view('/checkout', 'storefront.checkout')->name('checkout');
Route::get('/pagamentos/mercado-pago/retorno/{order:code}', [MercadoPagoController::class, 'return'])->name('payments.mercado-pago.return');
Route::get('/pedidos', [StorefrontController::class, 'orders'])->name('orders.index');
Route::get('/pedido/{order:code}/status', [StorefrontController::class, 'orderStatus'])->name('orders.status');
Route::view('/politica-de-privacidade', 'legal.privacy')->name('legal.privacy');
Route::view('/politica-de-cookies', 'legal.cookies')->name('legal.cookies');

Route::middleware(InstallerAccess::class)->group(function (): void {
    Route::get('/install/{step?}', [InstallerController::class, 'show'])
        ->whereNumber('step')->name('installer.show');
    Route::post('/install/{step}', [InstallerController::class, 'save'])
        ->whereNumber('step')->name('installer.save');
});

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->whereIn('provider', ['mercado-pago', 'asaas'])
    ->name('webhooks.payments');
Route::post('/webhooks/shipping/melhor-envio', ShippingWebhookController::class)
    ->name('webhooks.shipping.melhor-envio');

Route::get('/integrations/melhor-envio/redirect', [MelhorEnvioOAuthController::class, 'redirect'])
    ->name('integrations.melhor-envio.redirect');
Route::get('/integrations/melhor-envio/callback', [MelhorEnvioOAuthController::class, 'callback'])
    ->name('integrations.melhor-envio.callback');

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/print/orders/{order}', PrintOrderController::class)
        ->name('admin.orders.print');
});
