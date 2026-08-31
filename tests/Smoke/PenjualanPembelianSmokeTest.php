<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Penjualan & Pembelian" sidebar group (cdocs/docs/modules.md §3): Sales
 * Order (Pengiriman), Purchase Order (Pembelian), Tanda Terima PO, plus two
 * fase-2 additions — Stock Transfer (modules.md §3) and the Pengembalian/QC
 * return endpoints that ride on the existing `Pengiriman` permission
 * (modules.md §9.1, which also documents why they have no sidebar entry).
 *
 * The customerReturns* routes are DataTables/JSON endpoints, not HTML pages —
 * their controllers return JsonResponse and nothing in resources/views links
 * to them. They are smoke-tested here anyway because the thing this file
 * checks (authorized 200 / unauthorized 403 / never a 500) is exactly as
 * meaningful for a JSON endpoint as for a page. Only the list and /context
 * routes are covered: the show routes need a real return document to exist,
 * which is Workflow-test territory, not smoke.
 */
class PenjualanPembelianSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'salesOrder (Pengiriman)' => ['/salesOrder', 'Pengiriman'],
            'purchaseOrder (Pembelian)' => ['/purchaseOrder', 'Pembelian'],
            'tt (Tanda Terima PO)' => ['/tt', 'Tanda Terima PO'],
            'stockTransfer' => ['/stockTransfer', 'Stock Transfer'],
            'customerReturns (list)' => ['/customerReturns', 'Pengiriman'],
            'customerReturns/context' => ['/customerReturns/context', 'Pengiriman'],
            'customerProductReturns (list)' => ['/customerProductReturns', 'Pengiriman'],
            'customerProductReturns/context' => ['/customerProductReturns/context', 'Pengiriman'],
            'customerSupplyReturns (list)' => ['/customerSupplyReturns', 'Pengiriman'],
            'customerSupplyReturns/context' => ['/customerSupplyReturns/context', 'Pengiriman'],
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
