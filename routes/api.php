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

    // Addresses
    Route::apiResource('addresses', AddressController::class);
    Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/items', [CartController::class, 'addItem']);
        Route::patch('/items/{cartItem}', [CartController::class, 'updateItem']);
        Route::delete('/items/{cartItem}', [CartController::class, 'removeItem']);
        Route::delete('/', [CartController::class, 'clear']);
    });

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

    // Discounts
    Route::prefix('discounts')->group(function () {
        Route::get('/', [DiscountController::class, 'getAvailableDiscounts']);
        Route::post('/validate', [DiscountController::class, 'validate']);
        Route::post('/apply', [DiscountController::class, 'apply']);
        Route::post('/record', [DiscountController::class, 'recordUsage']);
    });

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
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Orders
        Route::get('/orders', [OrderController::class, 'adminIndex']);
        Route::get('/orders/{order}', [OrderController::class, 'adminShow']);
        Route::patch('/orders/{order}/status', [OrderController::class, 'adminUpdateStatus']);
    });

});