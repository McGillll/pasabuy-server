<?php

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(base_path('routes/api/auth.php'));
Route::prefix('admin/products')->group(base_path('routes/api/products.php'));
Route::prefix('admin/product-categories')->group(base_path('routes/api/product-categories.php'));
Route::prefix('admin/brands')->group(base_path('routes/api/brands.php'));
Route::prefix('admin/pasabuy-schedules')->group(base_path('routes/api/pasabuy-schedules.php'));
Route::group([], base_path('routes/api/orders.php'));
Route::group([], base_path('routes/api/order_payments.php'));
Route::prefix('admin/customers')->group(base_path('routes/api/customers.php'));
Route::prefix('public')->group(base_path('routes/api/public.php'));
Route::group([], base_path('routes/api/settings.php'));
Route::group([], base_path('routes/api/invoice_settings.php'));
Route::group([], base_path('routes/api/invoices.php'));
