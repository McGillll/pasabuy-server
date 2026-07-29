<?php

use App\Http\Controllers\Api\BrandController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [BrandController::class, 'index']);
    Route::post('/', [BrandController::class, 'store']);
    Route::get('/{uuid}', [BrandController::class, 'show']);
    Route::put('/{uuid}', [BrandController::class, 'update']);
    Route::delete('/{uuid}', [BrandController::class, 'destroy']);
});
