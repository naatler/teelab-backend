<?php

use Illuminate\Support\Facades\Route;

Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
    Route::post('/cart', [CartController::class, 'add']);
    Route::get('/cart', [CartController::class, 'index']);

    Route::get('/products', [ProductController::class, 'index']);

    Route::post('/checkout', [OrderController::class, 'checkout']);
});
