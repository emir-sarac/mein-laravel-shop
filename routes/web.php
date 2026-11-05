<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    Volt::route('settings/addresses', 'settings.addresses')->name('settings.addresses');
});

// Admin-Bereich: Nur für eingeloggte Admins
Route::middleware(['auth', 'can:admin'])->group(function () {
    Volt::route('/products', 'products.products')->name('products.products');
    Volt::route('/create', 'products.create')->name('products.create');
    Volt::route('/edit', 'products.edit')->name('products.edit');
    Volt::route('/delete', 'products.delete')->name('products.delete');
    Volt::route('/admin/orders', 'admin.orders')->name('admin.orders');
});

Route::middleware(['auth'])->group(function () {
    Volt::route('/cart', 'cart.cart')->name('cart.cart');
    Volt::route('/shop', 'cart.shop')->name('cart.shop');
    Volt::route('/checkout', 'cart.checkout')->name('cart.checkout');
    Volt::route('/orders', 'orders.orders')->name('account.orders');
});
require __DIR__.'/auth.php';
