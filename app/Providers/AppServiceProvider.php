<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\SiteSetting;
use App\Services\IconManager;
use App\Services\MaintenanceModeManager;
use App\Services\PaymentGatewayManager;
use App\Support\Dates\DateService;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View as ViewFacade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function (Application $app) {
            return new PaymentGatewayManager($app, config('payments.gateways', []));
        });

        $this->app->singleton(IconManager::class, function (Application $app) {
            return new IconManager(config('icons.slots', []), config('icons.variants', []));
        });

        $this->app->singleton(DateService::class, function (Application $app) {
            return new DateService(config('dates.business_timezone', 'Asia/Tehran'));
        });

        $this->app->singleton(MaintenanceModeManager::class, function () {
            return new MaintenanceModeManager;
        });
    }

    public function boot(): void
    {
        $this->app->terminating(function (): void {
            SiteSetting::clearRuntimeCache();
            Menu::clearRuntimeCache();
            if ($this->app->resolved(MaintenanceModeManager::class)) {
                $this->app->make(MaintenanceModeManager::class)->resetMemoizedStatus();
            }
        });

        Event::listen(KeyForgotten::class, function ($event): void {
            if ($event->key === 'settings.public' || $event->key === 'laravel-cache-settings.public') {
                SiteSetting::clearRuntimeCache();
            }
            if (str_contains($event->key, 'menus.location.')) {
                Menu::clearRuntimeCache();
            }
        });

        ViewFacade::composer('checkout.payment', function (View $view): void {
            if (! $view->offsetExists('gateways')) {
                $view->with('gateways', app(PaymentGatewayManager::class)->names());
            }
        });
    }
}
