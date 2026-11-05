<?php

namespace App\Livewire\Actions;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class ManageCart
{
    // Artikel hinzufügen
    public static function add(int $productId, int $quantity): void
    {
        if ($quantity <= 0) return;

        $product = Product::findOrFail($productId);
        $cart = Session::get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $quantity;
        } else {
            $cart[$productId] = [
                'name' => $product->name,
                'price' => $product->price,
                'img_path' => $product->img_path,
                'quantity' => $quantity
            ];
        }

        Session::put('cart', $cart);
    }

    // Menge im Korb ändern (+1 / -1)
    public static function updateQuantity(int $productId, int $amount): void
    {
        $cart = Session::get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $amount;

            // Wenn die Menge auf 0 sinkt, lösche den Artikel
            if ($cart[$productId]['quantity'] <= 0) {
                unset($cart[$productId]);
            }

            Session::put('cart', $cart);
        }
    }

    // Artikel komplett entfernen
    public static function remove(int $productId): void
    {
        $cart = Session::get('cart', []);

        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
        }
    }
}
