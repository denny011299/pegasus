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
 * cdocs/docs/flows/produk-bermasalah/FLOW.md for the fully-traced flow this documents. This is the
 * most severe atomicity bug found across this whole testing program — worse than the (now-fixed)
 * `accProduction` gap, because the status flip happens mid-loop, on the FIRST successful item, not
 * only at the very end.
 *
 * `StockController::accProductIssues()` has no `DB::transaction()`. For a multi-item
 * `tipe_return = 1` approval, EVERY item's iteration ends by calling
 * `(new ProductIssues())->accProductIssues($data)` — which sets `product_issues.status = 2` —
 * unconditionally, inside the loop. If item 1 succeeds (stock deducted, log written, status
 * flipped to 2) and item 2 then fails its own stock-sufficiency guard, the method does
 * `return -1;` immediately. The client receives `-1` (read by the frontend as "nothing happened,
 * stock insufficient") but the document is left `status = 2` (accepted) with only item 1's stock
 * actually mutated — and because `status` is no longer `1`, the document can never be cleanly
 * retried through approve/decline again (the guard now reports "already processed").
 *
 * Uses a fresh, isolated fixture (own Supplies + single-unit SuppliesStock, no SuppliesRelation)
 * to avoid `stockCheck()`'s insert-time unit-conversion side effects entirely — item B's stock is
 * deliberately drained AFTER a clean insert to simulate it being consumed elsewhere between insert
 * and approval, the same pattern already used for Sales Order/Production's own shortfall tests.
 *
 * Not fixed here — deferred per this project's "queue bugs, don't fix" policy. This test
 * characterizes the CURRENT behavior on purpose.
 */
class ProductIssuesApprovalAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece

    /** @return array{variant: SuppliesVariant, stock: SuppliesStock} */
    private function createSuppliesFixture(string $label, int $startingStock): array
    {
        $supplies = new Supplies();
        $supplies->supplies_name = "Atomicity Test Supplies $label";
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        // accProductIssues's log-writing step unconditionally does
        // Supplier::find($variant->supplier_id)->supplier_name with no null-guard — a real,
        // separate finding (see KNOWN_ISSUES.md) not the one this test targets, so supplier_id
        // must be a real row to avoid tripping over it here.
        $supplierId = (int) DB::table('suppliers')->where('status', 1)->value('supplier_id');

        $variant = new SuppliesVariant();
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplier_id = $supplierId;
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

    public function test_item_two_failing_leaves_the_document_permanently_flipped_to_accepted_with_only_item_one_mutated(): void
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

        // Documents current behavior: a bare -1, not a clean {status:-1, message:...} shape.
        $this->assertSame('-1', $accResponse->getContent());

        // Item A (processed first) is permanently, fully mutated.
        $itemA['stock']->refresh();
        $this->assertSame(100 - $qtyA, $itemA['stock']->ss_stock, "item A's stock deduction survives item B's later failure");
        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $itemA['variant']->supplies_id,
            'log_jumlah' => $qtyA,
        ]);

        // Item B is never touched beyond the manual drain above.
        $itemB['stock']->refresh();
        $this->assertSame(2, $itemB['stock']->ss_stock, "item B's stock must be untouched by accProductIssues since its own guard rejected it");

        // The real bug: the document is left status=2 (Accepted) despite the client receiving an
        // error response and only HALF the items being mutated.
        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(
            2,
            (int) $pi->status,
            'BUG: the document is flipped to Accepted mid-loop, on item A\'s own iteration — not left pending like accPO/accProduction\'s (pre-fix) atomicity gaps'
        );

        // Confirms the document is now stuck: a repeat approve/decline both report "already
        // processed" instead of allowing a clean retry or reversal.
        $repeatAccResponse = $this->post('/accProductIssues', ['pi_id' => $piId]);
        $repeatAccResponse->assertJson(['status' => -2]);
        $repeatDeclineResponse = $this->post('/declineProductIssues', ['pi_id' => $piId]);
        $repeatDeclineResponse->assertJson(['status' => -2]);

        // Only one new log_stocks row exists (item A's) — item B never gets one.
        $this->assertSame($logCountBefore + 1, DB::table('log_stocks')->count());
        $itemBDetail = ProductIssuesDetail::where('pi_id', $piId)->where('item_id', $itemB['variant']->supplies_variant_id)->firstOrFail();
        $this->assertSame(1, (int) $itemBDetail->status, 'item B\'s detail row is untouched, still marked active');
    }
}
