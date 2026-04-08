<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $xenditClient;

    public function __construct()
    {
        // Initialize Xendit
        if (env('XENDIT_SECRET_KEY') && str_starts_with(env('XENDIT_SECRET_KEY'), 'xnd_')) {
            \Xendit\Xendit::setApiKey(env('XENDIT_SECRET_KEY'));
        }
    }

    public function createInvoice(Request $request, Order $order)
    {
        // Check authorization
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Order is not pending'], 400);
        }

        // Check if already paid
        $existingPayment = Payment::where('order_id', $order->id)->first();
        if ($existingPayment && $existingPayment->status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        $externalId = 'ORDER-' . $order->id . '-' . time();

        try {
            // Check if Xendit is configured
            if (!env('XENDIT_SECRET_KEY') || !str_starts_with(env('XENDIT_SECRET_KEY'), 'xnd_')) {
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

            // Create Xendit Invoice
            $params = [
                'external_id' => $externalId,
                'amount' => (float) $order->total_amount,
                'description' => 'Payment for Order #' . $order->id,
                'invoice_duration' => 86400, // 24 hours
                'customer' => [
                    'given_names' => $order->user->name,
                    'email' => $order->user->email,
                    'mobile_number' => $order->user->phone ?? '',
                ],
                'customer_notification_preference' => [
                    'invoice_created' => ['email'],
                    'invoice_reminder' => ['email'],
                    'invoice_paid' => ['email'],
                ],
                'success_redirect_url' => env('FRONTEND_URL') . '/orders/' . $order->id . '?payment=success',
                'failure_redirect_url' => env('FRONTEND_URL') . '/orders/' . $order->id . '?payment=failed',
                'currency' => 'IDR',
                'items' => $order->items->map(function($item) {
                    return [
                        'name' => $item->product->name,
                        'quantity' => $item->quantity,
                        'price' => (float) $item->price,
                    ];
                })->toArray(),
            ];

            Log::info('Creating Xendit Invoice', ['params' => $params]);

            $invoice = \Xendit\Invoice::create($params);

            Log::info('Xendit Invoice Created', ['invoice_id' => $invoice['id']]);

            // Save payment
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
                'invoice_id' => $invoice['id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Xendit Invoice Creation Failed', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
            ]);

            return response()->json([
                'message' => 'Failed to create payment invoice',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('Xendit Webhook Received', $request->all());

        // Verify webhook token (optional but recommended)
        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        $expectedToken = env('XENDIT_CALLBACK_TOKEN');
        
        if ($expectedToken && $callbackToken !== $expectedToken) {
            Log::warning('Invalid webhook token');
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $externalId = $request->external_id;
        $status = $request->status;

        $payment = Payment::where('xendit_external_id', $externalId)->first();

        if (!$payment) {
            Log::warning('Payment not found for webhook', ['external_id' => $externalId]);
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

        // Update payment and order
        $payment->update([
            'status' => $paymentStatus,
            'payment_method' => $request->payment_method ?? null,
            'paid_at' => $status === 'PAID' ? now() : null,
            'xendit_invoice_id' => $request->id ?? $payment->xendit_invoice_id,
        ]);

        $payment->order->update(['status' => $orderStatus]);

        Log::info('Payment Updated via Webhook', [
            'payment_id' => $payment->id,
            'status' => $paymentStatus,
            'order_status' => $orderStatus,
        ]);

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

        return response()->json([
            'message' => 'Payment marked as paid (mock)',
            'redirect_url' => env('FRONTEND_URL') . '/orders/' . $payment->order_id . '?payment=success'
        ]);
    }
}