<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $productId = $request->product_id;
        
        if ($productId) {
            $reviews = Review::with('user')
                ->where('product_id', $productId)
                ->latest()
                ->get();
        } else {
            $reviews = Review::with('user', 'product')
                ->latest()
                ->take(10)
                ->get();
        }

        return response()->json($reviews);
    }

    public function getPurchasedProducts(Request $request)
    {
        $userId = $request->user() ? (string) $request->user()->id : null;
        
        if (!$userId) {
            return response()->json(['message' => 'Please login'], 401);
        }

        // Get products from paid orders that haven't been reviewed yet
        $purchasedProductIds = OrderItem::whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->where('status', 'paid');
            })
            ->pluck('product_id')
            ->unique();

        // Get all purchased products with their review status
        $products = Product::whereIn('id', $purchasedProductIds)->get()->map(function ($product) use ($userId) {
            $hasReview = Review::where('product_id', $product->id)
                ->where('user_id', $userId)
                ->exists();
            
            return [
                'id' => $product->id,
                'name' => $product->name,
                'image_url' => $product->image_url,
                'price' => $product->price,
                'has_reviewed' => $hasReview,
            ];
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $userId = $request->user() ? (string) $request->user()->id : null;
        
        if (!$userId) {
            return response()->json(['message' => 'Please login to write a review'], 401);
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        // Check if user has purchased this product
        $hasPurchased = OrderItem::whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->where('status', 'paid');
            })
            ->where('product_id', $request->product_id)
            ->exists();

        if (!$hasPurchased) {
            return response()->json(['message' => 'You can only review products you have purchased'], 403);
        }

        $existingReview = Review::where('product_id', $request->product_id)
            ->where('user_id', $userId)
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'You have already reviewed this product'], 400);
        }

        $product = Product::findOrFail($request->product_id);
        
        $review = Review::create([
            'product_id' => $request->product_id,
            'user_id' => $userId,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user');

        return response()->json($review, 201);
    }

    public function update(Request $request, Review $review)
    {
        $userId = $request->user() ? (string) $request->user()->id : null;
        
        if (!$userId) {
            return response()->json(['message' => 'Please login to update review'], 401);
        }

        if ($review->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'nullable|string',
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $review->load('user');

        return response()->json($review);
    }

    public function destroy(Request $request, Review $review)
    {
        $userId = $request->user() ? (string) $request->user()->id : null;
        
        if (!$userId) {
            return response()->json(['message' => 'Please login to delete review'], 401);
        }

        if ($review->user_id !== $userId) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted']);
    }

    public function getProductReviews(Request $request, string $productId)
    {
        $reviews = Review::with('user')
            ->where('product_id', $productId)
            ->latest()
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;
        $totalReviews = $reviews->count();

        return response()->json([
            'reviews' => $reviews,
            'average_rating' => round($avgRating, 1),
            'total_reviews' => $totalReviews,
        ]);
    }

    public function getFeaturedReviews()
    {
        $reviews = Review::with('user', 'product')
            ->latest()
            ->take(6)
            ->get();

        return response()->json($reviews);
    }
}