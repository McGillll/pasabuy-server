<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('admin/products')->group(base_path('routes/api/products.php'));
Route::prefix('admin/pasabuy-schedules')->group(base_path('routes/api/pasabuy-schedules.php'));
Route::prefix('admin/orders')->group(base_path('routes/api/orders.php'));
Route::prefix('public')->group(base_path('routes/api/public.php'));
