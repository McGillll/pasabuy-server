<?php

use App\Http\Controllers\Api\PublicChecklistController;
use App\Http\Controllers\Api\PublicCustomerController;
use App\Http\Controllers\Api\PublicPasabuyController;
use App\Http\Controllers\Api\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('pasabuy-schedules')->group(function () {
    Route::get('/{uuid}', [PublicPasabuyController::class, 'show']);
});

Route::get('/products', [PublicProductController::class, 'index']);

Route::post('/customers/register', [PublicCustomerController::class, 'register'])->middleware('throttle:register');

Route::prefix('checklist')->middleware('throttle:checklist-toggle')->group(function () {
    Route::get('/{token}', [PublicChecklistController::class, 'show']);
    Route::post('/{token}/{productUuid}', [PublicChecklistController::class, 'toggle']);
});
