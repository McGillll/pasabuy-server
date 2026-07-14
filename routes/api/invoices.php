<?php

use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders/{orderUuid}/invoice')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::get('/', [InvoiceController::class, 'show']);
});
