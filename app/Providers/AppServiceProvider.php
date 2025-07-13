<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RemoteServerService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('remote.ssh', function () {
            return new RemoteServerService();
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
