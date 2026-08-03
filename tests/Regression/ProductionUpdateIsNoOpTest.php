<?php

namespace Tests\Regression;

use App\Models\Production;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Found while tracing cdocs/testing/workflows/PRODUCTION_FLOW.md (Phase 3 pilot).
 * `ProductionController::updateProduction()`'s entire body is empty:
 *
 *   function updateProduction(Request $req) {}
 *
 * The `/updateProduction` route (permission Produksi|edit) therefore does
 * nothing when called, with no visible error — the same shape of finding as
 * `PurchaseOrder::pelunasanPurchaseOrder()` (see cdocs/testing/KNOWN_ISSUES.md).
 * Confirmed 2026-08-01, not fixed — deciding what editing a production
 * should actually do (and how it should interact with an already-approved
 * production's stock movements) is a business decision, not something to
 * guess at. This test characterizes the CURRENT (inert) behavior on
 * purpose — flip its assertions once the feature is implemented for real.
 */
class ProductionUpdateIsNoOpTest extends TestCase
{
    use ActingAsStaff;

    public function test_update_production_currently_does_not_change_anything(): void
    {
        $this->actingAsSuperAdminStaff();

        $production = Production::query()->where('status', '>=', 1)->firstOrFail();
        $before = $production->only(['production_date', 'production_desc', 'status', 'acc_by']);

        $this->post('/updateProduction', [
            'production_id' => $production->production_id,
            'production_date' => now()->toDateString(),
            'production_desc' => 'Should not actually be saved',
        ])->assertStatus(200);

        $production->refresh();
        $this->assertSame($before, $production->only(['production_date', 'production_desc', 'status', 'acc_by']));
    }
}
