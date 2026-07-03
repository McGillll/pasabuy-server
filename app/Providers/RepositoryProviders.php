<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PasabuyScheduleRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProviders extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(PasabuyScheduleRepositoryInterface::class, PasabuyScheduleRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
    }
}
