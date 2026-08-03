<?php

use App\Http\Controllers\Api\DailyVerseController;
use Illuminate\Support\Facades\Route;

Route::get('/daily-verse', [DailyVerseController::class, 'show']);
