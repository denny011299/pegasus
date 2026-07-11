<?php

namespace App\Providers;

use App\Models\ProductVariant;
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

        // $hasStockAlert hanya dipakai di header, jadi composer cukup di view itu
        // (bukan '*') dan cukup cek keberadaan baris tanpa loop detail per-variant.
        View::composer('layout.partials.header', function ($view) {
            static $hasStockAlert = null;
            if ($hasStockAlert === null) {
                $hasStockAlert = ProductVariant::where('product_variants.status', '=', 1)
                    ->join('products as p', 'p.product_id', '=', 'product_variants.product_id')
                    ->where('p.status', '=', 1)
                    ->exists();
            }
            if ($hasStockAlert) {
                $view->with('hasStockAlert', true);
            }
        });
    }
}