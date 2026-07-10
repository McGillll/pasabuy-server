<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Customer\CustomerRepositoryInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PasabuyScheduleRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductCategoryRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProviders extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductCategoryRepositoryInterface::class, ProductCategoryRepository::class);
        $this->app->bind(PasabuyScheduleRepositoryInterface::class, PasabuyScheduleRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
    }
}
