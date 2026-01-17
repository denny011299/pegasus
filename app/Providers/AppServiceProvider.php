<?php

namespace App\Providers;

use App\Models\StockAlert;
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