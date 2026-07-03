<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{uuid}', [ProductController::class, 'show']);
    Route::put('/{uuid}', [ProductController::class, 'update']);
    Route::delete('/{uuid}', [ProductController::class, 'destroy']);
});
