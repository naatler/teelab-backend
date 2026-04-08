<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XenditService
{
    private $secretKey;

    public function __construct()
    {
        $this->secretKey = env('XENDIT_SECRET_KEY');
    }

    public function createInvoice(array $data)
    {
        try {
            $response = Http::withBasicAuth($this->secretKey, '')
                ->post('https://api.xendit.co/v2/invoices', [
                    'external_id' => $data['external_id'],
                    'amount' => $data['amount'],
                    'payer_email' => $data['email'],
                    'description' => $data['description'],
                    'success_redirect_url' => $data['success_url'],
                    'failure_redirect_url' => $data['failure_url'],
                ]);

            if (!$response->successful()) {
                Log::error('Xendit Error', [
                    'body' => $response->body()
                ]);
                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Xendit Exception', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}