<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Discount;
use Illuminate\Support\Str;

class DiscountSeeder extends Seeder
{
    public function run(): void
    {
        // New user welcome discount - 20% off
        Discount::create([
            'id' => Str::uuid(),
            'code' => 'WELCOME20',
            'description' => 'Welcome discount for new users - 20% off your first order!',
            'type' => 'percentage',
            'value' => 20,
            'min_order_amount' => 100000,
            'max_discount' => 50000,
            'usage_limit' => 1,
            'starts_at' => now(),
            'expires_at' => now()->addMonths(3),
            'is_active' => true,
            'is_new_user_only' => true,
        ]);

        // General discount - 10% off
        Discount::create([
            'id' => Str::uuid(),
            'code' => 'SAVE10',
            'description' => 'Save 10% on your order',
            'type' => 'percentage',
            'value' => 10,
            'min_order_amount' => 200000,
            'max_discount' => 25000,
            'usage_limit' => null,
            'starts_at' => now(),
            'expires_at' => null,
            'is_active' => true,
            'is_new_user_only' => false,
        ]);

        // Fixed discount - Rp 15,000 off
        Discount::create([
            'id' => Str::uuid(),
            'code' => 'RP15000',
            'description' => 'Rp 15,000 off for orders above Rp 300,000',
            'type' => 'fixed',
            'value' => 15000,
            'min_order_amount' => 300000,
            'usage_limit' => null,
            'starts_at' => now(),
            'expires_at' => null,
            'is_active' => true,
            'is_new_user_only' => false,
        ]);
    }
}