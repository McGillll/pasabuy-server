<?php

use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::patch('/{uuid}/status', [OrderController::class, 'updateStatus']);
});
