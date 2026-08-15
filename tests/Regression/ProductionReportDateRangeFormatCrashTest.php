<?php

namespace Tests\Regression;

use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-06): `Production::getProductionReport()`/`getProductionEfficiencyReport()`'s
 * date-RANGE branch (`is_array($data["date"]) && count(...) === 2`) unconditionally called
 * `Carbon::createFromFormat('d-m-Y', ...)` on both ends of the range — unlike their own
 * single-date branch a few lines below, which already falls back to `Y-m-d` via
 * `Carbon::hasFormat()`, and unlike the established pattern already used elsewhere in the same
 * controller (`ReportController.php:3353-3360`, `getReportBongkarPasang`'s date handling). A
 * `Y-m-d` date range (e.g. from an ISO-formatted date picker) threw
 * `InvalidArgumentException`/`Carbon\Exceptions\InvalidFormatException` and crashed the request
 * with a 500, instead of parsing cleanly like the single-date branch already did.
 *
 * Fix: mirrors the established `hasFormat('Y-m-d') ? $raw : createFromFormat('d-m-Y', $raw)`
 * fallback in both range-branch date parses, in both methods.
 */
class ProductionReportDateRangeFormatCrashTest extends TestCase
{
    use ActingAsStaff;

    public function test_production_report_accepts_a_y_m_d_date_range_without_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportProduksi?'.http_build_query([
            'date' => ['2026-01-01', '2026-01-31'],
        ]));

        $response->assertStatus(200);
    }

    public function test_production_report_still_accepts_a_d_m_y_date_range(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportProduksi?'.http_build_query([
            'date' => ['01-01-2026', '31-01-2026'],
        ]));

        $response->assertStatus(200);
    }

    public function test_efficiency_report_accepts_a_y_m_d_date_range_without_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportEfisiensiProduksi?'.http_build_query([
            'date' => ['2026-01-01', '2026-01-31'],
        ]));

        $response->assertStatus(200);
    }

    public function test_efficiency_report_still_accepts_a_d_m_y_date_range(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportEfisiensiProduksi?'.http_build_query([
            'date' => ['01-01-2026', '31-01-2026'],
        ]));

        $response->assertStatus(200);
    }

    public function test_production_report_still_accepts_a_single_y_m_d_date(): void
    {
        $this->actingAsSuperAdminStaff();

        $response = $this->get('/getReportProduksi?'.http_build_query([
            'date' => '2026-01-15',
        ]));

        $response->assertStatus(200);
    }
}
