<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    private function getUserId(Request $request): string
    {
        return (string) $request->user()->id;
    }

    public function index(Request $request)
    {
        $userId = $this->getUserId($request);
        $cart = Cart::firstOrCreate(['user_id' => $userId]);
        $cart->load(['items.product.category']);

        return response()->json($cart);
    }

    public function addItem(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if (!$product->is_active) {
            return response()->json(['message' => 'Product is not available'], 400);
        }

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock'], 400);
        }

        $userId = $this->getUserId($request);
        $cart = Cart::firstOrCreate(['user_id' => $userId]);

        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $request->quantity;
            
            if ($product->stock < $newQuantity) {
                return response()->json(['message' => 'Insufficient stock'], 400);
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity,
            ]);
        }

        $cartItem->load('product');

        return response()->json($cartItem, 201);
    }

    public function updateItem(Request $request, CartItem $cartItem)
    {
        $userId = $this->getUserId($request);
        if ($cartItem->cart->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        if ($request->quantity == 0) {
            $cartItem->delete();
            return response()->json(['message' => 'Item removed from cart']);
        }

        if ($cartItem->product->stock < $request->quantity) {
            return response()->json(['message' => 'Insufficient stock'], 400);
        }

        $cartItem->update(['quantity' => $request->quantity]);
        $cartItem->load('product');

        return response()->json($cartItem);
    }

    public function removeItem(Request $request, CartItem $cartItem)
    {
        $userId = $this->getUserId($request);
        if ($cartItem->cart->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clear(Request $request)
    {
        $userId = $this->getUserId($request);
        $cart = Cart::where('user_id', $userId)->first();
        
        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json(['message' => 'Cart cleared']);
    }
}