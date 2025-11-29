<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PythonSearchService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PythonSearchService::class, function ($app) {
            return new PythonSearchService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
