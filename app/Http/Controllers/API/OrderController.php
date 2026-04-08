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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            Log::info('Orders Index Request', [
                'user_id' => $user->id,
                'user_email' => $user->email,
            ]);

            $orders = $user->orders()
                          ->with(['items.product', 'address', 'payment'])
                          ->latest()
                          ->get();

            return response()->json($orders);
        } catch (\Exception $e) {
            Log::error('Error fetching orders: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to fetch orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'notes' => 'nullable|string|max:1000',
            'discount_code' => 'nullable|string',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
                    ->with('items.product')
                    ->firstOrFail();

        if ($cart->items->count() === 0) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $totalAmount = $cart->items->reduce(function ($sum, $item) {
            return $sum + ((float) $item->product->price * $item->quantity);
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

        $order->load(['items.product', 'address', 'discount', 'payment']);

        return response()->json([
            'id' => $order->id,
            'order' => $order,
            'message' => 'Order created successfully. Create invoice to proceed with payment.'
        ], 201);
    }

    public function show(Request $request, Order $order)
    {
        // Debug logging
        Log::info('Order Show Request', [
            'order_id' => $order->id,
            'order_user_id' => $order->user_id,
            'request_user_id' => $request->user()?->id,
        ]);

        if ($order->user_id !== $request->user()->id) {
            Log::warning('Unauthorized order access attempt', [
                'order_id' => $order->id,
                'order_user_id' => $order->user_id,
                'request_user_id' => $request->user()?->id,
            ]);
            
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->load(['items.product', 'address', 'payment', 'user']);

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

    // Admin endpoints
    public function adminIndex(Request $request)
    {
        $query = Order::with(['user', 'items.product', 'address', 'payment'])
                      ->latest();

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $orders = $query->paginate(20);

        return response()->json($orders);
    }

    public function adminShow(Order $order)
    {
        $order->load(['user', 'items.product', 'address', 'payment', 'discount']);

        return response()->json($order);
    }

    public function adminUpdateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $order->update(['status' => $request->status]);

        return response()->json($order);
    }
}
