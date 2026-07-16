<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthServiceInterface;
use App\Interfaces\Customer\CustomerServiceInterface;
use App\Interfaces\Invoice\InvoiceMailerServiceInterface;
use App\Interfaces\Invoice\InvoiceServiceInterface;
use App\Interfaces\InvoiceSetting\InvoiceSettingServiceInterface;
use App\Interfaces\Order\OrderServiceInterface;
use App\Interfaces\OrderPayment\OrderPaymentServiceInterface;
use App\Interfaces\PasabuySchedule\PasabuyScheduleServiceInterface;
use App\Interfaces\Product\ProductServiceInterface;
use App\Interfaces\ProductCategory\ProductCategoryServiceInterface;
use App\Interfaces\Settings\SettingsServiceInterface;
use App\Services\AuthService;
use App\Services\CustomerService;
use App\Services\InvoiceMailerService;
use App\Services\InvoiceService;
use App\Services\InvoiceSettingService;
use App\Services\OrderPaymentService;
use App\Services\OrderService;
use App\Services\PasabuyScheduleService;
use App\Services\ProductService;
use App\Services\ProductCategoryService;
use App\Services\SettingsService;
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
        $this->app->bind(OrderPaymentServiceInterface::class, OrderPaymentService::class);
        $this->app->bind(CustomerServiceInterface::class, CustomerService::class);
        $this->app->bind(SettingsServiceInterface::class, SettingsService::class);
        $this->app->bind(InvoiceSettingServiceInterface::class, InvoiceSettingService::class);
        $this->app->bind(InvoiceServiceInterface::class, InvoiceService::class);
        $this->app->bind(InvoiceMailerServiceInterface::class, InvoiceMailerService::class);
    }
}
