<?php

use App\Http\Controllers\Api\InvoiceSettingController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/invoice-settings')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [InvoiceSettingController::class, 'show']);
    Route::patch('/', [InvoiceSettingController::class, 'update']);
});
