<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::patch('/{uuid}/status', [OrderController::class, 'updateStatus']);
});

Route::middleware(['auth:sanctum', 'abilities:customer', 'customer.approved'])->group(function () {
    Route::post('/customer/pasabuy-schedules/{scheduleUuid}/orders', [OrderController::class, 'storeForCustomer']);
    Route::get('/customer/orders', [OrderController::class, 'myOrders']);
    Route::get('/customer/orders/{uuid}', [OrderController::class, 'myOrderShow']);
    Route::put('/customer/orders/{uuid}', [OrderController::class, 'updateMyOrder']);
    Route::patch('/customer/orders/{uuid}/cancel', [OrderController::class, 'cancelMyOrder']);
    Route::patch('/customer/orders/{uuid}/items/{itemUuid}/resolve', [OrderController::class, 'resolveMyOrderItem']);
});
