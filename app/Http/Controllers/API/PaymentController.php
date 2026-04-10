<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private function xenditKey()
    {
        return env('XENDIT_SECRET_KEY');
    }

    public function createInvoice(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'paid') {
            return response()->json(['message' => 'Order is not paid'], 400);
        }

        $existingPayment = Payment::where('order_id', $order->id)->first();
        if ($existingPayment && $existingPayment->status === 'paid') {
            return response()->json(['message' => 'Order already paid'], 400);
        }

        $externalId = 'ORDER-' . $order->id . '-' . time();
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $amount = $order->total_amount - ($order->discount_amount ?? 0);

        try {
            $response = Http::withBasicAuth($this->xenditKey(), '')
                ->withOptions([
                    'verify' => false,
                ])
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => $externalId,
                    'amount' => (float) $amount,
                    'payer_email' => $order->user->email ?? 'test@email.com',
                    'description' => 'Order #' . $order->id,
                    'success_redirect_url' => $frontendUrl . '/orders/' . $order->id . '?payment=success',
                    'failure_redirect_url' => $frontendUrl . '/orders/' . $order->id . '?payment=failed',
                ]);

            if (!$response->successful()) {
                Log::error('Xendit Error', [
                    'body' => $response->body()
                ]);

                return response()->json([
                    'message' => 'Failed create invoice',
                    'error' => $response->json()
                ], 500);
            }

            $data = $response->json();

            $payment = Payment::updateOrCreate(
                ['order_id' => $order->id],
                [
                    'xendit_invoice_id' => $data['id'],
                    'xendit_external_id' => $externalId,
                    'amount' => $order->total_amount,
                    'status' => 'paid',
                    'invoice_url' => $data['invoice_url'] ?? null,
                ]
            );

            return response()->json([
                'payment' => $payment,
                'invoice_url' => $data['invoice_url'],
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice Error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // 🔔 WEBHOOK FIXED (IMPORTANT)
    public function webhook(Request $request)
    {
        Log::info('Webhook masuk', $request->all());

        // 🔐 VALIDASI TOKEN
        $token = $request->header('x-callback-token');
        if ($token !== env('XENDIT_CALLBACK_TOKEN')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $externalId = $request->external_id;
        $status = $request->status;

        $payment = Payment::where('xendit_external_id', $externalId)
            ->orWhere('xendit_external_id', 'like', '%' . $externalId . '%')
            ->first();

        if (!$payment) {
            Log::warning('Payment not found for external_id', ['external_id' => $externalId]);
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // 🔥 IDEMPOTENT (ANTI DOUBLE UPDATE)
        if ($payment->status === 'paid') {
            return response()->json(['message' => 'Already processed']);
        }

        if (in_array($status, ['PAID', 'SETTLED'])) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_method' => $request->payment_method ?? null,
            ]);

            $payment->load('order');

            $payment->order->update([
                'status' => 'paid'
            ]);

            Log::info('Payment berhasil', [
                'order_id' => $payment->order_id,
                'external_id' => $externalId,
            ]);
        }

        return response()->json(['message' => 'OK']);
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

        return response()->json([
            'status' => $payment->status,
            'amount' => $payment->amount,
            'paid_at' => $payment->paid_at,
        ]);
    }
}