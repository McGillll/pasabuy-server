<?php

use App\Http\Controllers\Api\CustomerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [CustomerController::class, 'index']);
    Route::post('/{uuid}/approve', [CustomerController::class, 'approve']);
    Route::post('/{uuid}/reject', [CustomerController::class, 'reject']);
});
