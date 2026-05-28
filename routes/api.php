<?php

use App\Http\Controllers\Api\V1\CatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/home', [CatalogController::class, 'home'])->name('home');
    Route::get('/categories', [CatalogController::class, 'categories'])->name('categories.index');
    Route::get('/brands', [CatalogController::class, 'brands'])->name('brands.index');
    Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
    Route::get('/products/{product:slug}', [CatalogController::class, 'product'])->name('products.show');
});
