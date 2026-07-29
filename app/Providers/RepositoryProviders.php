<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthRepositoryInterface;
use App\Interfaces\Brand\BrandRepositoryInterface;
use App\Interfaces\Customer\CustomerRepositoryInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingRepositoryInterface;
use App\Interfaces\Order\OrderRepositoryInterface;
use App\Interfaces\OrderPayment\OrderPaymentRepositoryInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleRepositoryInterface;
use App\Interfaces\Product\ProductRepositoryInterface;
use App\Interfaces\ProductCategory\ProductCategoryRepositoryInterface;
use App\Interfaces\ScheduleInvoiceSetting\ScheduleInvoiceSettingRepositoryInterface;
use App\Interfaces\Settings\SettingsRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\InvoiceSettingRepository;
use App\Repositories\OrderPaymentRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PasabuyScheduleRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\ScheduleInvoiceSettingRepository;
use App\Repositories\SettingsRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryProviders extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(ProductCategoryRepositoryInterface::class, ProductCategoryRepository::class);
        $this->app->bind(BrandRepositoryInterface::class, BrandRepository::class);
        $this->app->bind(PasabuyScheduleRepositoryInterface::class, PasabuyScheduleRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(OrderPaymentRepositoryInterface::class, OrderPaymentRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(SettingsRepositoryInterface::class, SettingsRepository::class);
        $this->app->bind(InvoiceSettingRepositoryInterface::class, InvoiceSettingRepository::class);
        $this->app->bind(ScheduleInvoiceSettingRepositoryInterface::class, ScheduleInvoiceSettingRepository::class);
    }
}
