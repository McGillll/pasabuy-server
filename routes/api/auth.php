<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:forgot-password');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:forgot-password');
Route::post('/email/verify', [AuthController::class, 'verifyEmail'])->middleware('throttle:verify-email')->name('api.verify-email');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::patch('/me', [AuthController::class, 'updateProfile']);
    Route::patch('/me/password', [AuthController::class, 'updatePassword']);
    Route::post('/email/verification-notification', [AuthController::class, 'sendVerificationEmail'])->middleware('throttle:resend-verification');
});
