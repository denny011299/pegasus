<?php

namespace Tests\Workflow;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\ProductionDetails;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Support\ProductionOverdueAutoResolver;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/docs/flows/production/FLOW.md and cdocs/testing/KNOWN_ISSUES.md's "GET /getProduction
 * can silently trigger real stock mutations" entry.
 *
 * REFACTORED (2026-08-05): the ~4-day auto-timeout for overdue pending productions/cancel-requests
 * used to run inline inside `Production::getProduction()`'s per-row display loop — only ever
 * processing whichever rows happened to pass the current GET request's own filters, and recursing
 * back into itself on every match. Kept as an explicit product decision (still triggers on every
 * page load, for now), but extracted into `App\Support\ProductionOverdueAutoResolver` — a single,
 * independently-testable, non-recursive pass over ALL overdue rows regardless of any list filter —
 * now shared between `getProduction()` and the new `php artisan production:resolve-overdue` command
 * (for eventually running this only via cron, without touching this logic again).
 */
class ProductionOverdueAutoResolverTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // "Piece"
    private const WAREHOUSE_ID = 1;
    private const BOM_QTY = 1;
    private const BOM_DETAIL_QTY = 2;

    /**
     * ProductionOverdueAutoResolver::resolveOverdue() scans EVERY pending (status=1) production
     * table-wide -- there's no way to scope it to just this test's own fixture. Real data (e.g.
     * the okeh8644 snapshot) can carry genuinely old pending productions of its own; against the
     * old near-empty default seed there happened to be none, so this never mattered. Neutralize
     * any that already exist before each test creates its own fixture, so "checked/approved/
     * declined" counts reflect only what the test itself set up. Tests\TestCase uses
     * DatabaseTransactions, so this never touches the real data permanently.
     */
    protected function setUp(): void
    {
        parent::setUp();

        \App\Models\Production::where('status', 1)
            ->where('production_date', '<', now()->toDateString())
            ->update(['production_date' => now()->toDateString()]);
    }

    /** @return array{variant: ProductVariant, productStock: ProductStock, suppliesStock: SuppliesStock, bom: Bom} */
    private function createFixture(int $startingSuppliesStock = 1000): array
    {
        $category = new Category();
        $category->category_name = 'Overdue Resolver Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Overdue Resolver Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Overdue Resolver Test Variant';
        $variant->product_variant_sku = 'WF-OVERDUE-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::UNIT_ID;
        $productStock->warehouse_id = self::WAREHOUSE_ID;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Overdue Resolver Test Supplies';
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = $startingSuppliesStock;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = self::BOM_QTY;
        $bom->unit_id = self::UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = self::BOM_DETAIL_QTY;
        $bomDetail->unit_id = self::UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        return compact('variant', 'productStock', 'suppliesStock', 'bom');
    }

    private function createPendingProduction(array $fx, string $productionDate, int $pdQty = 10): Production
    {
        $production = new Production();
        $production->production_date = $productionDate;
        $production->production_code = 'PRV'.substr(uniqid(), -6);
        $production->production_desc = 'Overdue resolver test production';
        $production->production_created_by = 1;
        $production->status = 1;
        $production->save();

        $detail = new ProductionDetails();
        $detail->production_id = $production->production_id;
        $detail->product_variant_id = $fx['variant']->product_variant_id;
        $detail->pd_qty = $pdQty;
        $detail->unit_id = self::UNIT_ID;
        $detail->bom_id = $fx['bom']->bom_id;
        $detail->status = 1;
        $detail->save();

        return $production;
    }

    public function test_an_overdue_pending_production_with_enough_stock_is_auto_approved(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());

        $summary = (new ProductionOverdueAutoResolver())->resolveOverdue();

        $this->assertSame(1, $summary['pending_approved']);
        $this->assertSame(0, $summary['pending_declined']);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'an overdue, resolvable production must be auto-approved');
        $this->assertTrue((bool) $production->resolved_by_system, 'must be flagged as system-resolved, not attributed to a real staff action');

        $fx['productStock']->refresh();
        $this->assertSame(10, $fx['productStock']->ps_stock, 'the real accProduction() logic must have run, crediting finished-goods stock');
    }

    public function test_a_pending_production_not_yet_overdue_is_left_untouched(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(2)->toDateString());

        $summary = (new ProductionOverdueAutoResolver())->resolveOverdue();

        $this->assertSame(0, $summary['pending_checked'], 'a production only 2 days old must not be considered overdue yet');

        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'must remain pending');
    }

    public function test_an_overdue_pending_production_with_insufficient_stock_is_auto_declined(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture(startingSuppliesStock: 1); // far short of the 20 needed for pdQty=10
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());

        $summary = (new ProductionOverdueAutoResolver())->resolveOverdue();

        $this->assertSame(0, $summary['pending_approved']);
        $this->assertSame(1, $summary['pending_declined']);

        $production->refresh();
        $this->assertSame(3, (int) $production->status, 'a resolvable-check failure must fall back to auto-decline, not stay stuck pending');
        $this->assertTrue((bool) $production->resolved_by_system, 'the auto-decline fallback must also be flagged as system-resolved');
    }

    public function test_an_overdue_cancel_request_is_auto_timed_out_back_to_approved(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());
        $production->status = 4; // pending cancel-request, as if already approved once
        $production->cancel_requested_by = 1;
        $production->save();

        $summary = (new ProductionOverdueAutoResolver())->resolveOverdue();

        $this->assertSame(1, $summary['cancel_timed_out']);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'an overdue cancel-request must time out back to approved, not stay pending forever');
        $this->assertTrue((bool) $production->resolved_by_system, 'a timed-out cancel-request must also be flagged as system-resolved');
    }

    /**
     * ✅ ADDED (2026-08-05): acc_by alone can't distinguish a real staff decision from an
     * auto-timeout — accProduction()/declineProduction() still fill it from Session::get('user')
     * if a session happens to be active (e.g. this auto-timeout fired while a staff member had the
     * Produksi list open), which used to misattribute the automatic action to whoever was merely
     * viewing the page. getProduction()'s acc_by_name now shows "Sistem (Auto-Timeout)" whenever
     * resolved_by_system is set, regardless of whatever acc_by ended up holding.
     */
    public function test_getProduction_shows_system_auto_timeout_label_instead_of_the_viewing_staffs_name(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());

        // Simulates the misattribution scenario directly: an active session exists (this staff),
        // yet the resolution is still a system auto-timeout.
        (new ProductionOverdueAutoResolver())->resolveOverdue();
        $production->refresh();
        $this->assertNotNull($production->acc_by, 'acc_by is still filled from the active session, same as before — this is the raw value the fix must not rely on for display');

        $rows = (new Production())->getProduction(['production_id' => $production->production_id]);
        $row = $rows->firstOrFail();
        $this->assertSame('Sistem (Auto-Timeout)', $row->acc_by_name, 'display must show the system label, not the session staff\'s real name, even though acc_by points at them');
    }

    public function test_getProduction_still_shows_the_real_staff_name_for_a_genuine_manual_decision(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->toDateString());

        $this->post('/accProduction', ['production_id' => $production->production_id])->assertOk();

        $production->refresh();
        $this->assertFalse((bool) $production->resolved_by_system, 'a real, on-time manual approval must not be flagged as system-resolved');

        $rows = (new Production())->getProduction(['production_id' => $production->production_id]);
        $row = $rows->firstOrFail();
        $this->assertNotSame('Sistem (Auto-Timeout)', $row->acc_by_name, 'a genuine manual approval must show the real staff name, not the system label');
    }

    public function test_getProduction_still_triggers_the_same_auto_timeout_as_a_side_effect(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());

        // Confirms the refactor preserved the (deliberately kept, for now) side effect: merely
        // loading/refreshing the Produksi list still resolves overdue productions.
        $this->get('/getProduction')->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'GET /getProduction must still auto-resolve overdue productions as a side effect');
    }

    /**
     * ✅ FIXED (2026-08-05): the console command's dry-run summary line used to unconditionally
     * print "(0 approved, 0 declined)" even when the table above correctly showed a "would
     * auto-acc" row — dry-run never actually calls accProduction(), so it has no way to know
     * whether an item would really succeed or get declined, and the 0/0 looked like a real (and
     * wrong) outcome instead of "not attempted." The summary line now only reports how many
     * overdue items were FOUND in dry-run mode, deferring outcome detail to the table's own
     * per-row "action" column.
     */
    public function test_dry_run_summary_does_not_claim_a_fake_approved_declined_outcome(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $production = $this->createPendingProduction($fx, now()->subDays(5)->toDateString());

        Artisan::call('production:resolve-overdue', ['--dry-run' => true]);
        $dryRunOutput = Artisan::output();

        $this->assertStringContainsString('would auto-acc', $dryRunOutput, 'the per-row table must still say what would happen');
        $this->assertStringNotContainsString('approved', $dryRunOutput, 'dry-run must not claim a fake approved/declined outcome it never actually attempted');
        $this->assertStringContainsString('1 ditemukan overdue', $dryRunOutput);

        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'dry-run must never mutate anything');

        Artisan::call('production:resolve-overdue');
        $realRunOutput = Artisan::output();

        $this->assertStringContainsString('auto-approved', $realRunOutput);
        $this->assertStringContainsString('1 approved, 0 declined', $realRunOutput, 'a real run must still report the actual outcome');

        $production->refresh();
        $this->assertSame(2, (int) $production->status);
    }
}
