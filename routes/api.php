<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\{
    AuthController,
    CategoryController,
    ProductController,
    AddressController,
    CartController,
    OrderController,
    PaymentController,
    UploadController,
    DiscountController, 
    ReviewController,
};
/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Webhook (WAJIB di luar auth)
Route::post('/xendit/webhook', [PaymentController::class, 'webhook']);


// Public Products & Categories
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Reviews
Route::get('/reviews', [ReviewController::class, 'index']);
Route::get('/reviews/featured', [ReviewController::class, 'getFeaturedReviews']);
Route::get('/products/{product}/reviews', [ReviewController::class, 'getProductReviews']);

// Cart (public - returns empty cart for guest users)
Route::get('/cart/guest', [CartController::class, 'guestIndex']);

// Discounts (public - for applying discounts without full auth)
Route::prefix('discounts')->group(function () {
    Route::get('/', [DiscountController::class, 'getAvailableDiscounts']);
    Route::post('/apply', [DiscountController::class, 'apply']);
});

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (AUTH)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // Cart (authenticated actions)
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'add']);
        Route::patch('/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    // Addresses
    Route::apiResource('addresses', AddressController::class);
    Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);

    // Orders
    Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // 🔥 PAYMENTS (FIXED & CLEAN)
    Route::prefix('payments')->group(function () {
        Route::post('/{order}/invoice', [PaymentController::class, 'createInvoice']); // ✅ clean
        Route::get('/{order}/status', [PaymentController::class, 'getPaymentStatus']);
    });

    // Upload
    Route::prefix('upload')->group(function () {
        Route::post('/image', [UploadController::class, 'uploadImage']);
        Route::delete('/image', [UploadController::class, 'deleteImage']);
    });

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
    Route::put('/reviews/{review}', [ReviewController::class, 'update']);
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);
    Route::get('/reviews/purchased', [ReviewController::class, 'getPurchasedProducts']);

    /*
    |--------------------------------------------------------------------------
    | ADMIN ROUTES
    |--------------------------------------------------------------------------
    */
    Route::middleware(\App\Http\Middleware\IsAdmin::class)->prefix('admin')->group(function () {

        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Products
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    // Orders
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{order}', [OrderController::class, 'adminShow']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'adminUpdateStatus']);
    });

});