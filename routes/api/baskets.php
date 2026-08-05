<?php

use App\Http\Controllers\Api\BasketController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:customer', 'customer.approved'])->prefix('customer/basket')->group(function () {
    Route::get('/', [BasketController::class, 'show']);
    Route::post('items', [BasketController::class, 'storeItem']);
    Route::patch('items/{uuid}', [BasketController::class, 'updateItem']);
    Route::delete('items/{uuid}', [BasketController::class, 'destroyItem']);
    Route::delete('/', [BasketController::class, 'clear']);
});
