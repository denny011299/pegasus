<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Penjualan & Pembelian" sidebar group (cdocs/docs/modules.md §3): Sales
 * Order (Pengiriman), Purchase Order (Pembelian), Tanda Terima PO.
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
