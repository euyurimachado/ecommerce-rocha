<?php

use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/categorias/{category:slug}', [StorefrontController::class, 'category'])->name('categories.show');
Route::get('/produto/{product:slug}', [StorefrontController::class, 'product'])->name('products.show');
Route::view('/carrinho', 'storefront.cart')->name('cart');
Route::view('/checkout', 'storefront.checkout')->name('checkout');
Route::get('/pedido/{order:code}/status', [StorefrontController::class, 'orderStatus'])->name('orders.status');
Route::view('/politica-de-privacidade', 'legal.privacy')->name('legal.privacy');
Route::view('/politica-de-cookies', 'legal.cookies')->name('legal.cookies');
