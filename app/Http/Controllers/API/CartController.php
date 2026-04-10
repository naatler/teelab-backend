<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;

class CartController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|uuid',
            'quantity' => 'nullable|integer|min:1'
        ]);

        // Use $request->user() for API authentication
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $cart = Cart::firstOrCreate([
            'user_id' => $user->id
        ]);

        $qty = $request->quantity ?? 1;
        
        // Check if product exists
        $product = \App\Models\Product::find($request->product_id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        $item = CartItem::where([
            'cart_id' => $cart->id,
            'product_id' => $request->product_id
        ])->first();

        if ($item) {
            $item->increment('quantity', $qty);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $qty
            ]);
        }

        return response()->json(['message' => 'added', 'cart' => $cart->fresh()->load('items.product')]);
    }
    public function index(Request $request)
    {
        // Use $request->user() for API authentication
        $user = $request->user();
        
        // Return empty cart structure for guest users
        if (!$user) {
            return response()->json([
                'id' => null,
                'items' => []
            ]);
        }
        
        $cart = $user->carts()->with('items.product')->latest()->first();
        
        // Always return proper structure even if cart is null
        if (!$cart) {
            return response()->json([
                'id' => null,
                'items' => []
            ]);
        }
        
        return response()->json($cart);
    }
    
    public function guestIndex()
    {
        // Public endpoint for guest users
        return response()->json([
            'id' => null,
            'items' => []
        ]);
    }
    
    public function updateItem(Request $request, CartItem $cartItem)
    {
        $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);
        
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        // Verify the item belongs to the user's cart
        if ($cartItem->cart->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($request->quantity === 0) {
            $cartItem->delete();
            return response()->json(['message' => 'Item removed']);
        }
        
        $cartItem->update([
            'quantity' => $request->quantity
        ]);
        
        return response()->json(['message' => 'Quantity updated', 'item' => $cartItem]);
    }
    
    public function removeItem(Request $request, CartItem $cartItem)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        // Verify the item belongs to the user's cart
        if ($cartItem->cart->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $cartItem->delete();
        
        return response()->json(['message' => 'Item removed']);
    }
    
    public function clear(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $cart = $user->carts()->latest()->first();
        
        if ($cart) {
            $cart->items()->delete();
        }
        
        return response()->json(['message' => 'Cart cleared']);
    }
}
