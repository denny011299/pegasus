<?php

namespace App\Providers;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Logging\RequestLogger;
use App\Models\ProductVariant;
use App\Support\RoleAccess;
use App\Synchronization\Pmo\PmoClient;
use App\Synchronization\SyncFlowRegistry;
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
        $this->app->singleton(PmoClient::class, function () {
            return new PmoClient(config('synchronization.pmo', []));
        });

        $this->app->singleton(SyncFlowRegistry::class, function () {
            return new SyncFlowRegistry(config('synchronization.flows', []));
        });

        $this->app->singleton(ApiKeyManager::class, function () {
            return new ApiKeyManager(config('externalapi.key', []));
        });

        $this->app->singleton(ApiDocRegistry::class, function () {
            return new ApiDocRegistry(config('externalapi.docs', []));
        });

        // Singleton supaya pembacaan saklar pencatatan di tabel `settings`
        // cukup sekali per permintaan.
        $this->app->singleton(RequestLogger::class);
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