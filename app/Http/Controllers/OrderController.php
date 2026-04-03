<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\PaymentService;

class OrderController extends Controller
{
    public function checkout(Request $request, PaymentService $paymentService)
{
    $user = auth()->user();

    $order = Order::create([
        'user_id' => $user->id,
        'address_id' => $request->address_id,
        'status' => 'pending',
        'total_amount' => 10000 // nanti dari cart
    ]);

    $invoice = $paymentService->createInvoice($order, $user);

    return response()->json([
        'invoice_url' => $invoice['invoice_url']
    ]);
}
}
