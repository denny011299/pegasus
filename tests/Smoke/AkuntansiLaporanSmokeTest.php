<?php

namespace Tests\Smoke;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * "Akuntansi & Laporan" sidebar group (cdocs/docs/modules.md §5): Bank
 * Account, Hutang, Kategori Kas, Kas, and the Laporan pages.
 *
 * Two routes are handled outside the generic provider:
 * - /reportSelisihOpname is gated by check.access.any, which has a confirmed
 *   bug (see cdocs/testing/KNOWN_ISSUES.md and memory
 *   pegasus-check-access-any-bug): Laravel splits a `middleware:a,b,c`
 *   parameter into separate positional args before calling handle(), but
 *   checkAccessAny::handle() only declares one `string $spec` parameter — so
 *   only the FIRST listed module (and a hardcoded 'view' ability) is ever
 *   actually checked; every module after the first, and any explicit
 *   ability/'any' suffix, is silently ignored. The tests below assert the
 *   CURRENT (buggy) behavior on purpose, so a real fix shows up here as an
 *   intentional, reviewed test change rather than a silent green->red flip.
 * - /operationalCash has no check.access middleware at all (routes/web.php),
 *   so any logged-in staff can load it regardless of permissions — that is
 *   the current, intentional-looking wiring, not a gap this test should
 *   paper over.
 */
class AkuntansiLaporanSmokeTest extends TestCase
{
    use ActingAsStaff;

    public static function pageProvider(): array
    {
        return [
            'bank' => ['/bank', 'Bank Account'],
            'payReceive (Hutang)' => ['/payReceive', 'Hutang'],
            'cashCategory' => ['/cashCategory', 'Kategori Kas'],
            'cash' => ['/cash', 'Kas'],
            'reportBahanBaku' => ['/reportBahanBaku', 'Pengelolaan Bahan Mentah'],
            'ProductReturn' => ['/ProductReturn', 'Retur Produk'],
            'reportReturProdukArmada' => ['/reportReturProdukArmada', 'Retur Produk'],
            'reportProduksi' => ['/reportProduksi', 'Laporan Produksi'],
            'reportStockAging' => ['/reportStockAging', 'Laporan Stock Aging'],
            'reportCashOut' => ['/reportCashOut', 'Kas'],
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

    public function test_report_selisih_opname_loads_for_super_admin(): void
    {
        $this->actingAsSuperAdminStaff();

        $this->get('/reportSelisihOpname')->assertStatus(200);
    }

    public function test_report_selisih_opname_blocks_staff_without_permission(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/reportSelisihOpname')->assertStatus(403);
    }

    public function test_report_selisih_opname_loads_with_stok_opname_produk_permission(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Produk');

        $this->get('/reportSelisihOpname')->assertStatus(200);
    }

    /**
     * Documents the checkAccessAny bug (see class docblock): granting ONLY
     * the second module in the any-list should also unlock this page per
     * routes/web.php's declared intent, but currently does not. This
     * assertion captures reality, not the intended behavior — flip it to
     * assertStatus(200) once the middleware is fixed.
     */
    public function test_report_selisih_opname_currently_ignores_stok_opname_bahan_mentah_permission(): void
    {
        $this->actingAsStaffWithOnlyPermission('Stok Opname Bahan Mentah');

        $this->get('/reportSelisihOpname')->assertStatus(403);
    }

    public function test_operational_cash_loads_for_any_logged_in_staff(): void
    {
        $this->actingAsStaffWithNoAccess();

        $this->get('/operationalCash')->assertStatus(200);
    }

    public function test_operational_cash_redirects_guest(): void
    {
        $this->get('/operationalCash')->assertRedirect('/login');
    }
}
