<?php

use App\Http\Controllers\Api\ProductRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/product-requests')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [ProductRequestController::class, 'index']);
    Route::patch('/{uuid}/reviewed', [ProductRequestController::class, 'markReviewed']);
});

Route::prefix('customer/product-requests')->middleware(['auth:sanctum', 'abilities:customer', 'customer.approved'])->group(function () {
    Route::get('/', [ProductRequestController::class, 'myRequests']);
    Route::post('/', [ProductRequestController::class, 'store']);
});
