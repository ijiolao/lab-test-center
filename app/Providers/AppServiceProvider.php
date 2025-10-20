<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register LabPartnerManager as singleton
        $this->app->singleton(
            \App\Services\LabPartner\LabPartnerManager::class
        );

        // Register core services
        $this->app->bind(
            \App\Services\OrderService::class
        );

        $this->app->bind(
            \App\Services\ResultService::class
        );

        // Register services that should be singletons
        // Add more as needed
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fix for older MySQL versions
        Schema::defaultStringLength(191);

        // Register model observers if needed
        // \App\Models\Order::observe(\App\Observers\OrderObserver::class);
    }
}