<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
});

// Product routes
Route::resource('products', ProductController::class)->only(['index', 'show']);

// Order routes
Route::get('/orders/{product}/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders/{product}', [OrderController::class, 'store'])->name('orders.store');
Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/orders/{order}/status', [OrderController::class, 'status'])
    ->middleware('throttle:30,1')
    ->name('orders.status');
Route::post('/orders/{order}/retry-payment', [OrderController::class, 'retryPayment'])->name('orders.retry-payment');
Route::post('/orders/{order}/check-status', [OrderController::class, 'checkStatus'])->name('orders.check-status');

// Admin routes
Route::prefix('admin')->middleware(['admin.basic', 'throttle:20,1'])->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/products/create', [AdminController::class, 'productsCreate'])->name('products.create');
    Route::post('/products', [AdminController::class, 'productsStore'])->name('products.store');
    Route::get('/products/{product}/edit', [AdminController::class, 'productsEdit'])->name('products.edit');
    Route::put('/products/{product}', [AdminController::class, 'productsUpdate'])->name('products.update');
    Route::delete('/products/{product}', [AdminController::class, 'productsDestroy'])->name('products.destroy');
    Route::post('/switch-environment', [AdminController::class, 'switchEnvironment'])->name('switch-environment');
    Route::post('/save-credentials', [AdminController::class, 'saveCredentials'])->name('save-credentials');
});

// Webhook routes
Route::get('/api/v1/payments/webhook', function () {
    return response()->json([
        'success' => true,
        'message' => 'Webhook endpoint is ready. Send a POST request to this URL.',
    ]);
})->name('webhook.affanpay.api.info');

Route::post('/api/v1/payments/webhook', [WebhookController::class, 'affanpay'])
    ->middleware('throttle:60,1')
    ->name('webhook.affanpay.api');
Route::post('/webhook/affanpay', [WebhookController::class, 'affanpay'])
    ->middleware('throttle:60,1')
    ->name('webhook.affanpay');
