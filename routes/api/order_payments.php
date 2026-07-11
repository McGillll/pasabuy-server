<?php

use App\Http\Controllers\Api\OrderPaymentController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders/{orderUuid}/payments')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [OrderPaymentController::class, 'index']);
    Route::post('/', [OrderPaymentController::class, 'store']);
    Route::delete('/{uuid}', [OrderPaymentController::class, 'destroy']);
});
