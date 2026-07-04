<?php

use App\Http\Controllers\Api\PublicCustomerController;
use App\Http\Controllers\Api\PublicPasabuyController;
use Illuminate\Support\Facades\Route;

Route::prefix('pasabuy-schedules')->group(function () {
    Route::get('/{uuid}', [PublicPasabuyController::class, 'show']);
});

Route::post('/customers/register', [PublicCustomerController::class, 'register'])->middleware('throttle:register');
