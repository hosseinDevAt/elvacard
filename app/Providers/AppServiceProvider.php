<?php

namespace App\Providers;

use App\Services\IconManager;
use App\Services\PaymentGatewayManager;
use App\Services\SmsManager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function (Application $app) {
            return new PaymentGatewayManager($app, config('payments.gateways', []));
        });

        $this->app->singleton(SmsManager::class, function (Application $app) {
            return new SmsManager($app, config('sms.providers', []));
        });

        $this->app->singleton(IconManager::class, function (Application $app) {
            return new IconManager(config('icons.slots', []), config('icons.variants', []));
        });
    }

    public function boot(): void
    {
        //
    }
}
