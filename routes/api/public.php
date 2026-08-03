<?php

use App\Http\Controllers\Api\PublicChecklistController;
use App\Http\Controllers\Api\PublicCustomerController;
use App\Http\Controllers\Api\PublicPasabuyController;
use App\Http\Controllers\Api\PublicProductCategoryController;
use App\Http\Controllers\Api\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('pasabuy-schedules')->group(function () {
    Route::get('/{uuid}', [PublicPasabuyController::class, 'show']);
});

Route::get('/products', [PublicProductController::class, 'index']);
Route::get('/products/top-selling', [PublicProductController::class, 'topSelling']);
Route::get('/products/{uuid}', [PublicProductController::class, 'show']);

Route::get('/product-categories', [PublicProductCategoryController::class, 'children']);
Route::get('/product-categories/{uuid}', [PublicProductCategoryController::class, 'show']);

Route::post('/customers/register', [PublicCustomerController::class, 'register'])->middleware('throttle:register');

Route::prefix('checklist')->middleware('throttle:checklist-toggle')->group(function () {
    Route::get('/{token}', [PublicChecklistController::class, 'show']);
    Route::patch('/{token}/{productUuid}', [PublicChecklistController::class, 'toggle']);
    Route::patch('/{token}/{productUuid}/confirm-price', [PublicChecklistController::class, 'confirmPrice']);
});
