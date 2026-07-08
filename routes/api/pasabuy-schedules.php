<?php

use App\Http\Controllers\Api\PasabuyScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [PasabuyScheduleController::class, 'index']);
    Route::post('/', [PasabuyScheduleController::class, 'store']);
    Route::get('/{uuid}', [PasabuyScheduleController::class, 'show']);
    Route::put('/{uuid}', [PasabuyScheduleController::class, 'update']);
    Route::delete('/{uuid}', [PasabuyScheduleController::class, 'destroy']);
    Route::post('/{uuid}/close', [PasabuyScheduleController::class, 'close']);
    Route::post('/{uuid}/reopen', [PasabuyScheduleController::class, 'reopen']);
    Route::put('/{uuid}/products', [PasabuyScheduleController::class, 'syncProducts']);
    Route::get('/{uuid}/orders', [PasabuyScheduleController::class, 'orders']);
    Route::patch('/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price', [PasabuyScheduleController::class, 'confirmProductPrice']);
    Route::get('/{uuid}/summary', [PasabuyScheduleController::class, 'summary']);
    Route::put('/{uuid}/orders/{orderUuid}/items', [PasabuyScheduleController::class, 'updateOrderItems']);
});
