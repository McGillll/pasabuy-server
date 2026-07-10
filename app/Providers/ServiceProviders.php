<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthServiceInterface;
use App\Interfaces\Customer\CustomerServiceInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Interfaces\Product\ProductServiceInterface;
use App\Interfaces\ProductCategory\ProductCategoryServiceInterface;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\OrderService;
use App\Services\PasabuyScheduleService;
use App\Services\ProductService;
use App\Services\ProductCategoryService;
use Illuminate\Support\ServiceProvider;

class ServiceProviders extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(ProductServiceInterface::class, ProductService::class);
        $this->app->bind(ProductCategoryServiceInterface::class, ProductCategoryService::class);
        $this->app->bind(PasabuyScheduleServiceInterface::class, PasabuyScheduleService::class);
        $this->app->bind(OrderServiceInterface::class, OrderService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
    }
}
