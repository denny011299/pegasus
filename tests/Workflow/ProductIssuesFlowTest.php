<?php

namespace Tests\Workflow;

use App\Models\ProductIssues;
use App\Models\ProductStock;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * See cdocs/testing/workflows/PRODUCT_ISSUES_FLOW.md for the fully-traced flow this asserts
 * against (and cdocs/docs/flows/produk-bermasalah/FLOW.md for the exhaustive business-flow trace
 * this was built from). Covers both `tipe_return` directions' happy paths, decline, and the
 * repeat-approve guard. The critical mid-loop atomicity bug has its own dedicated test —
 * see tests/DatabaseTransaction/ProductIssuesApprovalAtomicityTest.php.
 */
class ProductIssuesFlowTest extends TestCase
{
    use ActingAsStaff;

    private function insertProductIssue(string $tipeReturn, array $items): int
    {
        $response = $this->post('/insertProductIssues', [
            'tipe_return' => $tipeReturn,
            'pi_type' => $tipeReturn == 1 ? 2 : 1,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'Workflow test product issue',
            'items' => json_encode($items),
        ]);
        $response->assertStatus(200);

        return (int) ProductIssues::orderByDesc('pi_id')->value('pi_id');
    }

    public function test_return_to_supplier_insert_then_approve_deducts_supplies_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->where('ss_stock', '>', 10)->firstOrFail();
        $startingStock = $stock->ss_stock;
        $qty = 3;
        $logCountBefore = DB::table('log_stocks')->count();

        $piId = $this->insertProductIssue(1, [[
            'supplies_variant_id' => $variant->supplies_variant_id,
            'supplies_name' => 'Workflow test supplies',
            'unit_id' => $stock->unit_id,
            'pid_qty' => $qty,
        ]]);

        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(1, (int) $pi->status, 'a freshly inserted document should be pending approval');
        $this->assertSame(1, (int) $pi->tipe_return);
        $this->assertDatabaseHas('product_issues_details', [
            'pi_id' => $piId,
            'item_id' => $variant->supplies_variant_id,
            'pid_qty' => $qty,
        ]);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'inserting must not touch stock before approval');
        $this->assertSame($logCountBefore, DB::table('log_stocks')->count(), 'inserting must not write a log_stocks row');

        $accResponse = $this->post('/accProductIssues', ['pi_id' => $piId]);
        $accResponse->assertStatus(200);

        $pi->refresh();
        $this->assertSame(2, (int) $pi->status, 'approving sets status to 2');

        $stock->refresh();
        $this->assertSame($startingStock - $qty, $stock->ss_stock, 'approval must deduct the returned qty from supplies stock');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $variant->supplies_id,
            'log_jumlah' => $qty,
        ]);
    }

    public function test_return_from_armada_insert_then_approve_increments_product_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->firstOrFail();
        $startingStock = $stock->ps_stock;
        $qty = 5;

        $piId = $this->insertProductIssue(2, [[
            'product_variant_id' => $stock->product_variant_id,
            'pr_name' => 'Workflow test product',
            'unit_id' => $stock->unit_id,
            'pid_qty' => $qty,
        ]]);

        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(2, (int) $pi->tipe_return);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'inserting must not touch stock before approval');

        $this->post('/accProductIssues', ['pi_id' => $piId])->assertStatus(200);

        $pi->refresh();
        $this->assertSame(2, (int) $pi->status);

        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ps_stock, 'approval must add the returned qty to product stock — no upper bound guard exists');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $stock->product_variant_id,
            'log_jumlah' => $qty,
        ]);
    }

    public function test_decline_a_pending_document_leaves_stock_untouched(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->firstOrFail();
        $startingStock = $stock->ps_stock;

        $piId = $this->insertProductIssue(2, [[
            'product_variant_id' => $stock->product_variant_id,
            'pr_name' => 'Workflow test product',
            'unit_id' => $stock->unit_id,
            'pid_qty' => 4,
        ]]);

        $this->post('/declineProductIssues', ['pi_id' => $piId])->assertStatus(200);

        $pi = ProductIssues::findOrFail($piId);
        $this->assertSame(3, (int) $pi->status);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'declining must not touch stock — nothing was ever mutated');
    }

    public function test_approving_an_already_approved_document_is_blocked_and_does_not_double_apply(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->firstOrFail();
        $startingStock = $stock->ps_stock;
        $qty = 2;

        $piId = $this->insertProductIssue(2, [[
            'product_variant_id' => $stock->product_variant_id,
            'pr_name' => 'Workflow test product',
            'unit_id' => $stock->unit_id,
            'pid_qty' => $qty,
        ]]);

        $this->post('/accProductIssues', ['pi_id' => $piId])->assertStatus(200);
        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ps_stock);

        $response = $this->post('/accProductIssues', ['pi_id' => $piId]);
        $response->assertJson(['status' => -2]);

        $stock->refresh();
        $this->assertSame($startingStock + $qty, $stock->ps_stock, 'a repeat approve must be blocked and not add the qty a second time');
    }
}
