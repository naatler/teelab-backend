<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\DiscountUsage;
use Illuminate\Http\Request;

class DiscountController extends Controller
{
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $code = strtoupper($request->code);
        $discount = Discount::where('code', $code)->first();

        if (!$discount) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid discount code',
            ], 404);
        }

        if (!$discount->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'Discount code is expired or inactive',
            ]);
        }

        $user = $request->user();
        
        if ($discount->is_new_user_only) {
            $hasPreviousOrder = $user->orders()->exists();
            if ($hasPreviousOrder) {
                return response()->json([
                    'valid' => false,
                    'message' => 'This discount is only for new users',
                ]);
            }

            if ($discount->hasBeenUsedByUser($user->id)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'You have already used this discount',
                ]);
            }
        }

        return response()->json([
            'valid' => true,
            'discount' => [
                'id' => $discount->id,
                'code' => $discount->code,
                'type' => $discount->type,
                'value' => $discount->value,
                'description' => $discount->description,
            ],
        ]);
    }

    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $code = strtoupper($request->code);
        $orderAmount = (float) $request->order_amount;
        
        // Case-insensitive discount lookup
        $discount = Discount::whereRaw('UPPER(code) = ?', [$code])->first();

        if (!$discount) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid discount code: ' . $code,
            ], 400);
        }

        if (!$discount->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Discount code is expired or inactive',
                'debug' => [
                    'is_active' => $discount->is_active,
                    'starts_at' => $discount->starts_at,
                    'expires_at' => $discount->expires_at,
                    'used_count' => $discount->used_count,
                    'usage_limit' => $discount->usage_limit,
                ],
            ], 400);
        }

        // Skip user validation for non-new-user discounts
        if ($discount->is_new_user_only) {
            $user = $request->user();
            if ($user && $discount->hasBeenUsedByUser($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already used this discount',
                ], 400);
            }
        }

        $discountAmount = $discount->calculateDiscount($orderAmount);
        
        if ($discountAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum order amount of Rp ' . number_format($discount->min_order_amount) . ' not met',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'discount_amount' => $discountAmount,
            'final_amount' => $orderAmount - $discountAmount,
            'discount' => [
                'id' => $discount->id,
                'code' => $discount->code,
                'type' => $discount->type,
                'value' => $discount->value,
            ],
        ]);
    }

    public function recordUsage(Request $request)
    {
        $request->validate([
            'discount_id' => 'required|exists:discounts,id',
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = $request->user();
        
        $usage = DiscountUsage::create([
            'discount_id' => $request->discount_id,
            'user_id' => $user->id,
            'order_id' => $request->order_id,
        ]);

        $discount = Discount::find($request->discount_id);
        $discount->increment('used_count');

        return response()->json([
            'success' => true,
            'usage' => $usage,
        ]);
    }

    public function getAvailableDiscounts(Request $request)
    {
        $user = $request->user();
        $hasPreviousOrder = $user->orders()->exists();

        $discounts = Discount::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->where(function ($query) use ($hasPreviousOrder) {
                $query->where('is_new_user_only', false)
                    ->orWhere(function ($q) use ($hasPreviousOrder) {
                        if (!$hasPreviousOrder) {
                            $q->whereNull('used_count')
                                ->orWhereRaw('used_count < usage_limit');
                        }
                    });
            })
            ->get();

        return response()->json($discounts);
    }
}