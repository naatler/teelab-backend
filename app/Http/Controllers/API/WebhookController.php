<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Payment;

class WebhookController extends Controller
{
    public function handle(Request $request)
{
    $payment = Payment::where('xendit_invoice_id', $request->id)->first();

    if ($request->status === 'PAID') {
        $payment->update(['status' => 'paid']);
        $payment->order->update(['status' => 'paid']);
    }
}
}
