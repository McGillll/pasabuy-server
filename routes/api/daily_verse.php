<?php

use App\Http\Controllers\Api\DailyVerseController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/daily-verse', [DailyVerseController::class, 'show']);
});
