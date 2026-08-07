<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-06): same bug shape as
 * tests/Regression/ProductionReportDateRangeFormatCrashTest.php, found while fixing that one and
 * flagged in KNOWN_ISSUES.md as out of scope there — now fixed here too.
 *
 * `LogStock::getRawMaterialUsageReport()` and `ProductIssues::getProductIssues()` both had a
 * date-RANGE branch that unconditionally called `Carbon::createFromFormat('d-m-Y', ...)` on both
 * ends, while their own single-date branch a few lines below already had a `Y-m-d` fallback via
 * `Carbon::hasFormat()`. A `Y-m-d` date range crashed with `InvalidFormatException` instead of
 * parsing cleanly. Confirmed both crashes were real (not just read from code) by reverting the fix
 * and reproducing the actual exception via `php artisan tinker` before restoring it.
 *
 * `ProductIssues.php` already has the correct pattern in a sibling method in the same file
 * (`getArmadaReturnReport()`) — this was purely a "didn't match its own neighbor" gap.
 */
class LogStockAndProductIssuesDateRangeFormatCrashTest extends TestCase
{
    use ActingAsStaff;

    public function test_raw_material_usage_report_accepts_a_y_m_d_date_range_without_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportPemakaianBahan?'.http_build_query([
            'date' => ['2026-01-01', '2026-01-31'],
        ]));

        $response->assertStatus(200);
    }

    public function test_raw_material_usage_report_still_accepts_a_d_m_y_date_range(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportPemakaianBahan?'.http_build_query([
            'date' => ['01-01-2026', '31-01-2026'],
        ]));

        $response->assertStatus(200);
    }

    public function test_product_issues_list_accepts_a_y_m_d_date_range_without_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getProductIssue?'.http_build_query([
            'date' => ['2026-01-01', '2026-01-31'],
        ]));

        $response->assertStatus(200);
    }

    public function test_product_issues_list_still_accepts_a_d_m_y_date_range(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getProductIssue?'.http_build_query([
            'date' => ['01-01-2026', '31-01-2026'],
        ]));

        $response->assertStatus(200);
    }
}
