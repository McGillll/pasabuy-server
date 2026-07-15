<?php

use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [OrderController::class, 'index']);
    Route::patch('/{uuid}/status', [OrderController::class, 'updateStatus']);
    Route::patch('/{uuid}/delivery-status', [OrderController::class, 'updateDeliveryStatus']);
});

Route::middleware(['auth:sanctum', 'abilities:customer', 'customer.approved'])->group(function () {
    Route::post('/customer/pasabuy-schedules/{scheduleUuid}/orders', [OrderController::class, 'storeForCustomer']);
    Route::get('/customer/orders', [OrderController::class, 'myOrders']);
    Route::get('/customer/orders/{uuid}', [OrderController::class, 'myOrderShow']);
    Route::get('/customer/orders/{uuid}/invoice', [InvoiceController::class, 'myInvoice']);
    Route::put('/customer/orders/{uuid}', [OrderController::class, 'updateMyOrder']);
    Route::post('/customer/orders/{uuid}/cancel', [OrderController::class, 'cancelMyOrder']);
});
