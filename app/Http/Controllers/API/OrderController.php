<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Discount;
use App\Models\DiscountUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
                          ->with(['items.product', 'address', 'payment'])
                          ->latest()
                          ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string',
            'discount_code' => 'nullable|string',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
                    ->with('items.product')
                    ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validate stock
        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Insufficient stock for {$item->product->name}"
                ], 400);
            }
        }

        // Calculate total
        $totalAmount = $cart->items->reduce(function($carry, $item) {
            return $carry + ($item->product->price * $item->quantity);
        }, 0);

        // Validate and calculate discount
        $discountId = null;
        $discountAmount = 0;
        
        if ($request->discount_code) {
            $discount = Discount::where('code', strtoupper($request->discount_code))->first();
            
            if ($discount && $discount->isValid()) {
                $user = $request->user();
                
                if ($discount->is_new_user_only) {
                    if ($discount->hasBeenUsedByUser($user->id)) {
                        return response()->json(['message' => 'You have already used this discount'], 400);
                    }
                }

                $discountAmount = $discount->calculateDiscount($totalAmount);
                
                if ($discountAmount > 0) {
                    $discountId = $discount->id;
                }
            }
        }

        $finalAmount = $totalAmount - $discountAmount;

        // Create order with transaction
        $order = DB::transaction(function() use ($request, $cart, $totalAmount, $discountId, $discountAmount) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'address_id' => $request->address_id,
                'discount_id' => $discountId,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                ]);

                // Reduce stock
                $item->product->decrement('stock', $item->quantity);
            }

            // Clear cart
            $cart->items()->delete();

            return $order;
        });

        // Record discount usage if applied
        if ($discountId) {
            DiscountUsage::create([
                'discount_id' => $discountId,
                'user_id' => $request->user()->id,
                'order_id' => $order->id,
            ]);
            
            $discount = Discount::find($discountId);
            $discount->increment('used_count');
        }

        $order->load(['items.product', 'address', 'discount']);

        return response()->json($order, 201);
    }

    public function show(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->load(['items.product', 'address', 'payment']);

        return response()->json($order);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Only pending orders can be cancelled'
            ], 400);
        }

        DB::transaction(function() use ($order) {
            // Restore stock
            foreach ($order->items as $item) {
                $item->product->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);
        });

        return response()->json(['message' => 'Order cancelled successfully']);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json($order);
    }
}