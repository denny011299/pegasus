<?php

namespace Tests\DatabaseTransaction;

use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCT_ISSUES_FLOW.md and
 * cdocs/docs/flows/produk-bermasalah/FLOW.md for the fully-traced flow this documents.
 *
 * History: `StockController::accProductIssues()` used to have no `DB::transaction()`, and flipped
 * `product_issues.status` to 2 (Approved) INSIDE the per-item loop rather than once at the end —
 * the most severe atomicity bug found across this whole testing program, worse than the
 * (already-fixed) `accProduction` gap because the status flip happened on the FIRST successful
 * item, not only after everything succeeded. A second, independent bug lived in the same method:
 * the log-writing step did `Supplier::find($sup->supplier_id)->supplier_name` with no null-guard,
 * 500ing if a supplies_variant's supplier_id was null or pointed at a deleted supplier.
 *
 * FIXED 2026-08-03: both items' stock sufficiency AND supplier validity are now pre-checked
 * (no mutation) for every line item before any mutation happens; the actual mutation loop is
 * wrapped in `DB::transaction()`; and the status flip happens exactly once, after the loop
 * completes, right before commit. This test now proves the two failure shapes end cleanly instead
 * of characterizing the old crash/stuck-state behavior.
 *
 * Uses a fresh, isolated fixture (own Supplies + single-unit SuppliesStock, no SuppliesRelation)
 * to avoid `stockCheck()`'s insert-time unit-conversion side effects entirely — item B's stock is
 * deliberately drained AFTER a clean insert to simulate it being consumed elsewhere between insert
 * and approval, the same pattern already used for Sales Order/Production's own shortfall tests.
 */
class ProductIssuesApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece

    /** @return array{variant: SuppliesVariant, stock: SuppliesStock} */
    private function createSuppliesFixture(string $label, int $startingStock, ?int $supplierId = null): array
    {
        $supplies = new Supplies();
        $supplies->supplies_name = "Atomicity Test Supplies $label";
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $supplierId = $supplierId ?? (int) DB::table('suppliers')->where('status', 1)->value('supplier_id');

        $variant = new SuppliesVariant();
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplier_id = $supplierId ?: null;
        $variant->supplies_variant_name = "Atomicity Test Variant $label";
        $variant->supplies_variant_sku = 'WF-PI-ATOMIC-'.$label.'-'.uniqid();
        $variant->supplies_variant_barcode = 'WF-PI-BARCODE-'.$label.'-'.uniqid();
        $variant->supplies_variant_price = 0;
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        $stock = new SuppliesStock();
        $stock->supplies_id = $supplies->supplies_id;
        $stock->unit_id = self::UNIT_ID;
        $stock->warehouse_id = 1;
        $stock->ss_stock = $startingStock;
        $stock->status = 1;
        $stock->save();

        return compact('variant', 'stock');
    }

    public function test_item_two_failing_leaves_the_whole_document_unmutated_and_still_pending(): void
    {
        $this->actingAsSuperAdminStaff();

        $qtyA = 3;
        $qtyB = 5;

        // Both fixtures start with ample stock so insertProductIssues's own stockCheck() passes
        // cleanly for both items — the shortfall for item B is introduced AFTER insert.
        $itemA = $this->createSuppliesFixture('A', 100);
        $itemB = $this->createSuppliesFixture('B', 100);

        $insertResponse = $this->post('/insertProductIssues', [
            'tipe_return' => 1,
            'pi_type' => 2,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'DB transaction atomicity test',
            'items' => json_encode([
                [
                    'supplies_variant_id' => $itemA['variant']->supplies_variant_id,
                    'supplies_name' => 'Item A (sufficient stock)',
                    'unit_id' => self::UNIT_ID,
                    'pid_qty' => $qtyA,
                ],
                [
                    'supplies_variant_id' => $itemB['variant']->supplies_variant_id,
                    'supplies_name' => 'Item B (insufficient stock)',
                    'unit_id' => self::UNIT_ID,
                    'pid_qty' => $qtyB,
                ],
            ]),
        ]);
        $insertResponse->assertStatus(200);
        $this->assertNotSame('-1', $insertResponse->getContent(), 'the insert itself must succeed cleanly with ample stock for both items');
        $piId = (int) ProductIssues::orderByDesc('pi_id')->value('pi_id');
        $this->assertDatabaseHas('product_issues', ['pi_id' => $piId, 'status' => 1]);

        // Simulate item B's stock being consumed elsewhere between insert and approval.
        $itemB['stock']->ss_stock = 2; // less than qtyB (5)
        $itemB['stock']->save();

        $logCountBefore = DB::table('log_stocks')->count();

        $accResponse = $this->post('/accProductIssues', ['pi_id' => $piId]);

        // FIXED: a clean, structured rejection — not a bare -1 — naming the actual shortfall.
        $accResponse->assertStatus(200);
        $accResponse->assertJson(['status' => -1]);
        $this->assertStringContainsString('Atomicity Test Variant B', $accResponse->json('message'));

        // FIXED: item A is no longer mutated either — the pre-check runs for ALL items before ANY
        // mutation, so item B's shortfall blocks the whole approval, not just its own iteration.
        $itemA['stock']->refresh();
        $this->assertSame(100, $itemA['stock']->ss_stock, "item A's stock must NOT be touched when a later item fails pre-check");

        $itemB['stock']->refresh();
        $this->assertSame(2, $itemB['stock']->ss_stock, "item B's stock is untouched beyond the manual drain above");

        // FIXED: the document stays genuinely pending — not falsely flipped to Approved.
        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(1, (int) $pi->status, 'the document must remain pending, not flipped to Approved on a rejected approval');

        // FIXED: since nothing was mutated and status is still pending, a repeat approve attempt
        // hits the SAME clean shortage rejection again — not "already processed" — meaning the
        // document is genuinely retryable once the real stock issue is resolved.
        $repeatAccResponse = $this->post('/accProductIssues', ['pi_id' => $piId]);
        $repeatAccResponse->assertJson(['status' => -1]);

        // No log_stocks rows at all — neither item was ever mutated.
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count());
        $itemBDetail = ProductIssuesDetail::where('pi_id', $piId)->where('item_id', $itemB['variant']->supplies_variant_id)->firstOrFail();
        $this->assertSame(1, (int) $itemBDetail->status, 'item B\'s detail row is untouched, still marked active');
    }

    public function test_a_missing_or_invalid_supplier_is_rejected_cleanly_instead_of_crashing(): void
    {
        $this->actingAsSuperAdminStaff();

        // supplier_id pointing at a row that doesn't exist — reproduces the null-guard crash
        // (Supplier::find($sup->supplier_id)->supplier_name on a null find()) without needing to
        // actually delete a real supplier.
        $bogusSupplierId = ((int) DB::table('suppliers')->max('supplier_id')) + 1000;
        $qty = 3;
        $item = $this->createSuppliesFixture('BadSupplier', 100, $bogusSupplierId);

        $insertResponse = $this->post('/insertProductIssues', [
            'tipe_return' => 1,
            'pi_type' => 2,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'DB transaction supplier null-guard test',
            'items' => json_encode([[
                'supplies_variant_id' => $item['variant']->supplies_variant_id,
                'supplies_name' => 'Item with invalid supplier',
                'unit_id' => self::UNIT_ID,
                'pid_qty' => $qty,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $piId = (int) ProductIssues::orderByDesc('pi_id')->value('pi_id');

        $logCountBefore = DB::table('log_stocks')->count();

        // FIXED: no 500 — a clean, structured rejection naming the item with the bad supplier.
        $accResponse = $this->post('/accProductIssues', ['pi_id' => $piId]);
        $accResponse->assertStatus(200);
        $accResponse->assertJson(['status' => 0]);
        $this->assertStringContainsString('supplier', strtolower($accResponse->json('message')));
        $this->assertStringContainsString('Atomicity Test Variant BadSupplier', $accResponse->json('message'));

        $item['stock']->refresh();
        $this->assertSame(100, $item['stock']->ss_stock, 'no stock mutation should happen when the pre-check rejects a bad supplier');

        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(1, (int) $pi->status, 'the document must remain pending, not flipped to Approved');

        $this->assertSame($logCountBefore, DB::table('log_stocks')->count());
    }
}
