<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $user = auth()->user();

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        $item = CartItem::where([
            'cart_id' => $cart->id,
            'product_id' => $request->product_id
        ])->first();

        if ($item) {
            $item->increment('quantity', $request->qty);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->qty
            ]);
        }


        return response()->json(['message' => 'added']);

    }
    public function index()
    {
        return auth()->user()
            ->carts()
            ->with('items.product')
            ->latest()
            ->first();
    }
}
