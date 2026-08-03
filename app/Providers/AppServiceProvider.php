<?php

namespace App\Providers;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Docs\ApiDocRegistry;
use App\ExternalApi\Logging\RequestLogger;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\Warehouse;
use App\Support\RoleAccess;
use App\Synchronization\Pmo\PmoClient;
use App\Synchronization\SyncFlowRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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

    public function boot()
    {
        Blade::if('roleCan', function (string $module, string $ability) {
            return RoleAccess::can(Session::get('user'), $module, strtolower($ability));
        });

        Blade::if('roleCanAny', function (array $modules, string $ability) {
            return RoleAccess::canAny(Session::get('user'), $modules, strtolower($ability));
        });

        // Sinkronkan role_access session dari tabel roles
        if (Session::has('user')) {
            $sessionUser = Session::get('user');
            $freshAccess = RoleAccess::resolveRoleAccessJson($sessionUser);
            if ((string) ($sessionUser->role_access ?? '') !== (string) $freshAccess) {
                $sessionUser->role_access = $freshAccess;
                Session::put('user', $sessionUser);
            }
        }

        // Global: $warehouses + $warehousesGrouped + $activeWarehouse untuk layout/header/sidebar
        View::composer(['layout.partials.header', 'layout.partials.sidebar', 'layout.mainlayout'], function ($view) {
            static $payload = null;

            if ($payload === null) {
                $payload = $this->buildWarehouseNavbarPayload();
            }

            $view->with($payload);
        });

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

    /**
     * Siapkan data gudang navbar + pastikan session active_warehouse_id valid.
     * Setelah pilih gudang → page reload → ProductStock / getStock ikut filter warehouse ini.
     */
    private function buildWarehouseNavbarPayload(): array
    {
        $empty = [
            'warehouses' => collect(),
            'warehousesGrouped' => collect(),
            'activeWarehouse' => null,
        ];

        try {
            if (! Schema::hasTable('warehouses')) {
                return $empty;
            }

            $user = Session::get('user');
            $warehouses = $user ? Warehouse::availableForUser($user) : collect();
            $warehousesGrouped = Warehouse::groupByType($warehouses);

            $activeId = Session::get('active_warehouse_id');
            $activeWarehouse = null;

            if ($activeId) {
                $activeWarehouse = $warehouses->first(
                    fn($wh) => (int) $wh->id === (int) $activeId
                );
                if (! $activeWarehouse) {
                    // Session mengarah ke gudang non-aktif / tidak di-assign → bersihkan
                    Session::forget('active_warehouse_id');
                    $activeId = null;
                }
            }

            if (! $activeWarehouse && $warehouses->isNotEmpty()) {
                $activeWarehouse = Warehouse::pickDefaultWarehouse($warehouses, $user);
                if ($activeWarehouse) {
                    $activeId = (int) $activeWarehouse->id;
                    Session::put('active_warehouse_id', $activeId);

                    // Persist preferensi jika belum / beda
                    if ($user && ! empty($user->staff_id)) {
                        $last = (int) ($user->last_active_warehouse_id ?? 0);
                        if ($last !== $activeId) {
                            Staff::where('staff_id', (int) $user->staff_id)
                                ->update(['last_active_warehouse_id' => $activeId]);
                            $user->last_active_warehouse_id = $activeId;
                            Session::put('user', $user);
                        }
                    }
                }
            }

            return [
                'warehouses' => $warehouses,
                'warehousesGrouped' => $warehousesGrouped,
                'activeWarehouse' => $activeWarehouse,
            ];
        } catch (\Throwable $e) {
            return $empty;
        }
    }
}
