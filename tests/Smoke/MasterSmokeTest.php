<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Master" sidebar group (cdocs/docs/modules.md §2): Kategori/Satuan/Variasi,
 * Produk, Bahan Mentah, Armada & Pemasok, Inventaris, plus the fase-2 "Gudang"
 * submenu (Daftar Gudang / Tipe Gudang, modules.md §2.2). Broad, shallow
 * checks — business correctness is covered by Health/Workflow tests, not here.
 *
 * Note on the Gudang routes: checkAccess also consults
 * WarehouseMenuAccess::allows(), a per-warehouse sidebar_menus whitelist that
 * super admin does NOT bypass. It only bites once a warehouse is active in the
 * session, and these tests never set active_warehouse_id, so allows() short-
 * circuits to true here. Pin one with withActiveWarehouse() and that stops
 * being true — that path belongs in a Workflow test, not this file.
 *
 * Guest-redirect behaviour (checkLogin middleware) is app-wide and already
 * proven once in tests/Feature/ExampleTest.php — not repeated per route here.
 */
class MasterSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'category' => ['/category', 'Kategori'],
            'unit' => ['/unit', 'Satuan'],
            'variant' => ['/variant', 'Variasi'],
            'warehouse (Daftar Gudang)' => ['/warehouse', 'Gudang'],
            'warehouse-type (Tipe Gudang)' => ['/warehouse-type', 'Tipe Gudang'],
            'product' => ['/product', 'Daftar Produk'],
            'stockProduct' => ['/stockProduct', 'Stok Produk'],
            'barcodePrint' => ['/barcodePrint', 'Daftar Produk'],
            'supplies' => ['/supplies', 'Daftar Bahan Mentah'],
            'stockSupplies' => ['/stockSupplies', 'Stok Bahan Mentah'],
            'customer (Armada)' => ['/customer', 'Armada'],
            'supplier (Pemasok)' => ['/supplier', 'Pemasok'],
            'productIssue' => ['/productIssue', 'Produk Bermasalah'],
            'stockAlert' => ['/stockAlert', 'Peringatan Stok Produk'],
            'stockAlertSupplies' => ['/stockAlertSupplies', 'Peringatan Stok Bahan Mentah'],
            'stockOpname' => ['/stockOpname', 'Stok Opname Produk'],
            'stockOpnameBahan' => ['/stockOpnameBahan', 'Stok Opname Bahan Mentah'],
        ];
    }

    #[DataProvider('pageProvider')]
    public function test_page_loads_for_super_admin(string $uri, string $module): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get($uri)->assertStatus(200);
    }

    #[DataProvider('pageProvider')]
    public function test_page_blocks_staff_without_permission(string $uri, string $module): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get($uri)->assertStatus(403);
    }

    #[DataProvider('pageProvider')]
    public function test_page_loads_for_staff_with_only_that_permission(string $uri, string $module): void
    {
        $this->actingAsStaffWithOnlyPermission($module);

        $this->get($uri)->assertStatus(200);
    }
}
