<?php

namespace App\Providers;

use App\Models\StockAlert;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        Blade::if('roleCan', function (string $module, string $ability) {
            return RoleAccess::can(Session::get('user'), $module, strtolower($ability));
        });

        Blade::if('roleCanAny', function (array $modules, string $ability) {
            return RoleAccess::canAny(Session::get('user'), $modules, strtolower($ability));
        });

        View::composer('*', function ($view) {
            $s = (new StockAlert())->getStockAlert();
            if (count($s) > 0) {
                $view->with(
                    'hasStockAlert',
                    true
                );
            }
        });
    }
}