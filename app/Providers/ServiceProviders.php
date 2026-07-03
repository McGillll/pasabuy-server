<?php

namespace App\Providers;

use App\Interfaces\Auth\AuthServiceInterface;
use App\Services\AuthService;
use Illuminate\Support\ServiceProvider;

class ServiceProviders extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
    }
}
