<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function checkout(Request $request, PaymentService $paymentService)
    {
        $request->validate([
            'address_id' => 'required|exists:addresses,id',
        ]);

        $user = auth()->user();

        $cart = $user->carts()->with('items.product')->latest()->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart kosong'], 400);
        }

        $total = 0;

        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'error' => 'Stok produk "' . $item->product->name . '" tidak mencukupi',
                ], 400);
            }

            $total += $item->product->price * $item->quantity;
        }

        $order = Order::create([
            'user_id'      => $user->id,
            'address_id'   => $request->address_id,
            'status'       => 'pending',
            'total_amount' => $total,
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id'   => $order->id,
                'product_id' => $item->product_id,
                'quantity'   => $item->quantity,
                'price'      => $item->product->price,
            ]);

            $item->product->decrement('stock', $item->quantity);
        }

        $payment = $paymentService->createInvoice($order, $user);

        return response()->json([
            'invoice_url' => $payment->invoice_url,
        ], 201);
    }
}