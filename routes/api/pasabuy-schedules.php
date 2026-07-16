<?php

use App\Http\Controllers\Api\PasabuyScheduleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [PasabuyScheduleController::class, 'index']);
    Route::get('/{uuid}', [PasabuyScheduleController::class, 'show']);
    Route::get('/{uuid}/orders', [PasabuyScheduleController::class, 'orders']);
    Route::get('/{uuid}/summary', [PasabuyScheduleController::class, 'summary']);
    Route::get('/{uuid}/products/{productUuid}/buyers', [PasabuyScheduleController::class, 'productBuyers']);
    Route::get('/{uuid}/checklist', [PasabuyScheduleController::class, 'checklist']);
    Route::get('/{uuid}/checklist/share-link', [PasabuyScheduleController::class, 'checklistShareLink']);

    Route::middleware('email.verified')->group(function () {
        Route::post('/', [PasabuyScheduleController::class, 'store']);
        Route::put('/{uuid}', [PasabuyScheduleController::class, 'update']);
        Route::delete('/{uuid}', [PasabuyScheduleController::class, 'destroy']);
        Route::post('/{uuid}/close', [PasabuyScheduleController::class, 'close']);
        Route::post('/{uuid}/reopen', [PasabuyScheduleController::class, 'reopen']);
        Route::post('/{uuid}/finish', [PasabuyScheduleController::class, 'finish']);
        Route::patch('/{uuid}/schedule-products/{scheduleProductUuid}/confirm-price', [PasabuyScheduleController::class, 'confirmProductPrice']);
        Route::patch('/{uuid}/checklist/{productUuid}', [PasabuyScheduleController::class, 'toggleChecklistItem']);
        Route::post('/{uuid}/checklist/regenerate', [PasabuyScheduleController::class, 'regenerateChecklist']);
        Route::post('/{uuid}/checklist/share-link/regenerate', [PasabuyScheduleController::class, 'regenerateChecklistShareLink']);
        Route::put('/{uuid}/orders/{orderUuid}/items', [PasabuyScheduleController::class, 'updateOrderItems']);
    });
});
