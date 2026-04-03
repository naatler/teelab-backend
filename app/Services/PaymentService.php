<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Order;
use App\Models\User;
use Xendit\Configuration;
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class PaymentService
{
    protected InvoiceApi $invoiceApi;

    public function __construct()
    {
        Configuration::setXenditKey(config('services.xendit.secret_key'));
        $this->invoiceApi = new InvoiceApi();
    }

    public function createInvoice(Order $order, User $user): Payment
    {
        $params = new CreateInvoiceRequest([
            'external_id'  => (string) $order->id,
            'amount'       => $order->total_amount,
            'payer_email'  => $user->email,
            'description'  => 'Order Payment #' . $order->id,
        ]);

        $invoice = $this->invoiceApi->createInvoice($params);

        return Payment::create([
            'order_id'    => $order->id,
            'user_id'     => $user->id,
            'invoice_id'  => $invoice->getId(),
            'amount'      => $order->total_amount,
            'status'      => $invoice->getStatus(),
            'invoice_url' => $invoice->getInvoiceUrl(),
        ]);
    }
}