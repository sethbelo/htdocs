<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:10,1')->name('auth.token');

    Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function (): void {
        Route::get('/me', [AuthController::class, 'me'])->name('me');
        Route::delete('/auth/token', [AuthController::class, 'revoke'])->name('auth.revoke');

        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/orders/{number}', [OrderController::class, 'show'])->name('orders.show');

        Route::get('/products', [CatalogController::class, 'products'])->name('products.index');
        Route::get('/clients', [CatalogController::class, 'clients'])->name('clients.index');
        Route::post('/clients', [CatalogController::class, 'storeClient'])->name('clients.store');
    });
});

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.payments');
