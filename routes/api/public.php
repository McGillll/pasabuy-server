<?php

use App\Http\Controllers\Api\PublicPasabuyController;
use Illuminate\Support\Facades\Route;

Route::prefix('pasabuy-schedules')->group(function () {
    Route::get('/{uuid}', [PublicPasabuyController::class, 'show']);
    Route::post('/{uuid}/orders', [PublicPasabuyController::class, 'storeOrder'])->middleware('throttle:public-order');
});
