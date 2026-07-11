<?php

use App\Http\Controllers\Api\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/public/settings', [SettingsController::class, 'show']);

Route::middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
    Route::patch('/admin/settings', [SettingsController::class, 'update']);
});
