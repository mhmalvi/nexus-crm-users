<?php

namespace App\Providers;

use App\Interfaces\CreateInterface;
use Illuminate\Support\Facades\Http;
use App\Services\CreateCustomerService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CreateInterface::class, CreateCustomerService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Http::macro('crm_leads', function () {
            return Http::baseUrl('https://crmleads.queleadscrm.com/api');
        });
    }
}
