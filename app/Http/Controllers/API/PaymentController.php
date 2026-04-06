<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Xendit\Xendit;

class PaymentController extends Controller
{
    public function __construct()
    {
        Xendit::setApiKey(env('XENDIT_SECRET_KEY'));
    }

    public function createInvoice(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is not pending'], 400);
        }

        $existingPayment = Payment::where('order_id', $order->id)->first();

        if ($existingPayment && $existingPayment->status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        $externalId = 'ORDER-' . $order->id . '-' . time();

        try {
            // Check if Xendit is configured
            if (!env('XENDIT_SECRET_KEY') || env('XENDIT_SECRET_KEY') === 'your_xendit_secret_key') {
                // Mock payment for development
                $payment = Payment::updateOrCreate(
                    ['order_id' => $order->id],
                    [
                        'xendit_external_id' => $externalId,
                        'amount' => $order->total_amount,
                        'status' => 'pending',
                    ]
                );

                return response()->json([
                    'payment' => $payment,
                    'invoice_url' => url('/api/payments/mock/' . $payment->id),
                    'message' => 'Mock payment created (Xendit not configured)',
                ]);
            }

            $params = [
                'external_id' => $externalId,
                'amount' => (float) $order->total_amount,
                'payer_email' => $order->user->email,
                'description' => 'Payment for Order #' . $order->id,
                'success_redirect_url' => env('FRONTEND_URL') . '/orders/' . $order->id . '?payment=success',
                'failure_redirect_url' => env('FRONTEND_URL') . '/orders/' . $order->id . '?payment=failed',
            ];

                $invoice = \Xendit\Invoice::create($params);

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'xendit_invoice_id' => $invoice['id'],
                    'xendit_external_id' => $externalId,
                    'amount' => $order->total_amount,
                    'status' => 'pending',
                ]
            );

            return response()->json([
                'payment' => $payment,
                'invoice_url' => $invoice['invoice_url'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        $externalId = $request->external_id;
        $status = $request->status;

        $payment = Payment::where('xendit_external_id', $externalId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $paymentStatus = 'pending';
        $orderStatus = 'pending';

        if ($status === 'PAID') {
            $paymentStatus = 'paid';
            $orderStatus = 'processing';
        } elseif ($status === 'EXPIRED') {
            $paymentStatus = 'expired';
            $orderStatus = 'cancelled';
        }

        $payment->update([
            'status' => $paymentStatus,
            'payment_method' => $request->payment_method ?? null,
            'paid_at' => $status === 'PAID' ? now() : null,
        ]);

        $payment->order->update(['status' => $orderStatus]);

        return response()->json(['message' => 'Webhook processed']);
    }

    public function getPaymentStatus(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = Payment::where('order_id', $order->id)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        return response()->json($payment);
    }

    public function mockPaymentSuccess(Payment $payment)
    {
        $payment->update([
            'status' => 'paid',
            'payment_method' => 'MOCK_PAYMENT',
            'paid_at' => now(),
        ]);

        $payment->order->update(['status' => 'processing']);

        return response()->json(['message' => 'Payment marked as paid (mock)']);
    }
}