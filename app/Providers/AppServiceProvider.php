<?php

namespace App\Providers;

use App\Services\RemoteServerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('remote.ssh', function () {
            return new RemoteServerService;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'local-dev') {
            $user = \App\Models\User::newModelInstance([
                'name' => 'Faisal Khan',
                'email' => 'faisal@example.com',
                'password' => Hash::make('secretpass'),
            ]);
            Auth::login($user);
        }
    }
}
