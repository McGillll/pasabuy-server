<?php

use App\Http\Controllers\Api\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [ProductCategoryController::class, 'index']);
    Route::post('/', [ProductCategoryController::class, 'store']);
    Route::get('/{uuid}', [ProductCategoryController::class, 'show']);
    Route::post('/{uuid}', [ProductCategoryController::class, 'update']);
    Route::delete('/{uuid}', [ProductCategoryController::class, 'destroy']);
});
