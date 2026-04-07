<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CategoryController;
use App\Http\Controllers\API\ProductController;
use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\CartController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\UploadController;
use App\Http\Controllers\API\DiscountController;

// Public routes
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Payment webhook (no auth required)
Route::post('/payments/webhook', [PaymentController::class, 'webhook']);
Route::post('/payments/mock/{payment}/success', [PaymentController::class, 'mockPaymentSuccess']);

// Public product & category routes
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{category}', [CategoryController::class, 'show']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // Addresses
    Route::get('/addresses', [AddressController::class, 'index']);
    Route::post('/addresses', [AddressController::class, 'store']);
    Route::get('/addresses/{address}', [AddressController::class, 'show']);
    Route::patch('/addresses/{address}', [AddressController::class, 'update']);
    Route::delete('/addresses/{address}', [AddressController::class, 'destroy']);
    Route::patch('/addresses/{address}/set-default', [AddressController::class, 'setDefault']);

    // Cart
    Route::get('/cart', [CartController::class, 'index']);
    Route::post('/cart/items', [CartController::class, 'addItem']);
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem']);
    Route::delete('/cart', [CartController::class, 'clear']);

    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

    // Payments
    Route::post('/payments/orders/{order}/create', [PaymentController::class, 'createInvoice']);
    Route::get('/payments/orders/{order}/status', [PaymentController::class, 'getPaymentStatus']);

    // Upload (authenticated users)
    Route::post('/upload/image', [UploadController::class, 'uploadImage']);
    Route::delete('/upload/image', [UploadController::class, 'deleteImage']);

    // Discounts
    Route::get('/discounts', [DiscountController::class, 'getAvailableDiscounts']);
    Route::post('/discounts/validate', [DiscountController::class, 'validate']);
    Route::post('/discounts/apply', [DiscountController::class, 'apply']);
    Route::post('/discounts/record', [DiscountController::class, 'recordUsage']);

    // Admin only routes
    Route::middleware(\App\Http\Middleware\IsAdmin::class)->group(function () {
        // Categories
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::patch('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        // Products
        Route::post('/products', [ProductController::class, 'store']);
        Route::patch('/products/{product}', [ProductController::class, 'update']);
        Route::delete('/products/{product}', [ProductController::class, 'destroy']);

        // Orders (admin)
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
        Route::middleware(['auth:sanctum', 'admin'])->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
        });
    });
});