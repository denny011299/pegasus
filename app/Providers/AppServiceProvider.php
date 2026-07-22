<?php

namespace App\Providers;

use App\Models\ProductVariant;
use App\Models\Warehouse;
use App\Support\RoleAccess;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
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

        // Navbar: HANYA gudang yang di-assign ke staf login (pivot staff_warehouses)
        View::composer(['layout.partials.header', 'layout.mainlayout'], function ($view) {
            static $warehouses = null;
            if ($warehouses === null) {
                try {
                    $user = Session::get('user');
                    $warehouses = (Schema::hasTable('warehouses') && $user)
                        ? Warehouse::availableForUser($user)
                        : collect();
                } catch (\Throwable $e) {
                    $warehouses = collect();
                }

                $activeId = Session::get('active_warehouse_id');
                if ($activeId) {
                    $stillAllowed = $warehouses->contains(fn ($wh) => (int) $wh->id === (int) $activeId);
                    if (!$stillAllowed) {
                        Session::forget('active_warehouse_id');
                    }
                } elseif ($warehouses->count() > 0) {
                    // Auto-pilih jika belum ada yang terpilih
                    Session::put('active_warehouse_id', (int) $warehouses->first()->id);
                }
            }
            $view->with('warehouses', $warehouses);
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
}
