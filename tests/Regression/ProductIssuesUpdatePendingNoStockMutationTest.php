<?php

namespace Tests\Regression;

use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\ProductStock;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #157 (2026-09-07): `StockController::updateProductIssue()` (editing a Produk Bermasalah
 * document that is still pending/not yet ACC'd) used to mutate `ps_stock`/`ss_stock` directly
 * (flat, no roll-up -- same class of bug as #155) via 3 separate, overlapping blocks
 * (`stockCheck()`'s bongkar side effect, an unconditional "Kembalikan stock semua" block that
 * treated every detail row as a supplies return even for `tipe_return == 2`, and a manual
 * `ps_stock -=`/`insertProductIssuesDetail()` pair for new rows). That contradicted the invariant
 * `ProductIssuesFlowTest` already asserts for insert: a `product_issues` document only ever
 * mutates stock at `accProductIssues()`, never before. This endpoint was also confirmed unreachable
 * from the production UI (its edit button is commented out in `Product_Issues.js`) — see
 * `cdocs/docs/flows/produk-bermasalah/FLOW.md`.
 *
 * Fixed by stripping every stock mutation out of `updateProductIssue()`/
 * `ProductIssuesDetail::updateProductIssuesDetail()`, mirroring `insertProductIssue()`: pre-check
 * only (read-only, supplier-return side), write `product_issues`/`product_issues_details`, never
 * touch `ps_stock`/`ss_stock`.
 */
class ProductIssuesUpdatePendingNoStockMutationTest extends TestCase
{
    use ActingAsStaff;

    private function insertProductIssue(int $tipeReturn, array $items): int
    {
        $response = $this->post('/insertProductIssues', [
            'tipe_return' => $tipeReturn,
            'pi_type' => $tipeReturn == 1 ? 2 : 1,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'GH #157 regression test',
            'items' => json_encode($items),
        ]);
        $response->assertStatus(200);

        return (int) ProductIssues::orderByDesc('pi_id')->value('pi_id');
    }

    public function test_editing_a_pending_armada_return_never_touches_product_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->firstOrFail();
        $startingStock = $stock->ps_stock;

        $piId = $this->insertProductIssue(2, [[
            'product_variant_id' => $stock->product_variant_id,
            'pr_name' => 'GH157 test product',
            'unit_id' => $stock->unit_id,
            'pid_qty' => 5,
        ]]);
        $pi = ProductIssues::findOrFail($piId);
        $existingDetail = ProductIssuesDetail::where('pi_id', $piId)->where('status', 1)->firstOrFail();

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'inserting must not touch stock');

        // Edit: bump the existing item's qty AND add a brand new item -- both should update
        // product_issues_details, neither should touch ps_stock at all.
        $updateResponse = $this->post('/updateProductIssues', [
            'pi_id' => $piId,
            'pi_code' => $pi->pi_code,
            'pi_type' => $pi->pi_type,
            'ref_num' => 0,
            'tipe_return' => 2,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'GH #157 regression test, edited',
            'items' => json_encode([
                [
                    'pid_id' => $existingDetail->pid_id,
                    'product_variant_id' => $stock->product_variant_id,
                    'unit_id' => $stock->unit_id,
                    'pid_qty' => 9, // was 5
                ],
                [
                    'product_variant_id' => $stock->product_variant_id,
                    'unit_id' => $stock->unit_id,
                    'pid_qty' => 3, // brand new line
                ],
            ]),
        ]);
        $updateResponse->assertStatus(200);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, 'editing a pending document must never touch stock');

        $pi->refresh();
        $this->assertSame(1, (int) $pi->status, 'still pending -- unaffected by edit');

        $existingDetail->refresh();
        $this->assertSame(9, $existingDetail->pid_qty, 'existing line qty must be updated');
        $this->assertSame(1, (int) $existingDetail->status, 'existing line stays active');

        $this->assertDatabaseHas('product_issues_details', [
            'pi_id' => $piId,
            'item_id' => $stock->product_variant_id,
            'pid_qty' => 3,
            'status' => 1,
        ]);
        $this->assertSame(
            2,
            ProductIssuesDetail::where('pi_id', $piId)->where('status', 1)->count(),
            'exactly the 2 lines from the edit payload should remain active'
        );
    }

    public function test_removing_a_line_on_edit_marks_it_inactive_without_touching_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $stock = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', 1)
            ->firstOrFail();
        $startingStock = $stock->ps_stock;

        $piId = $this->insertProductIssue(2, [[
            'product_variant_id' => $stock->product_variant_id,
            'pr_name' => 'GH157 test product',
            'unit_id' => $stock->unit_id,
            'pid_qty' => 5,
        ]]);
        $pi = ProductIssues::findOrFail($piId);
        $existingDetail = ProductIssuesDetail::where('pi_id', $piId)->where('status', 1)->firstOrFail();

        // Edit with an EMPTY items array -- the one existing line should be dropped (status 0),
        // no stock reversal since none was ever applied.
        $this->post('/updateProductIssues', [
            'pi_id' => $piId,
            'pi_code' => $pi->pi_code,
            'pi_type' => $pi->pi_type,
            'ref_num' => 0,
            'tipe_return' => 2,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'GH #157 regression test, line removed',
            'items' => json_encode([]),
        ])->assertStatus(200);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock);

        $existingDetail->refresh();
        $this->assertSame(0, (int) $existingDetail->status, 'line dropped from the payload must be marked inactive');
    }

    public function test_editing_a_pending_supplier_return_never_touches_supplies_stock(): void
    {
        $this->actingAsSuperAdminStaff();

        $variant = SuppliesVariant::where('status', 1)->firstOrFail();
        $stock = SuppliesStock::where('supplies_id', $variant->supplies_id)->where('status', 1)->where('ss_stock', '>', 10)->firstOrFail();
        $startingStock = $stock->ss_stock;

        $piId = $this->insertProductIssue(1, [[
            'supplies_variant_id' => $variant->supplies_variant_id,
            'supplies_name' => 'GH157 test supplies',
            'unit_id' => $stock->unit_id,
            'pid_qty' => 3,
        ]]);
        $pi = ProductIssues::findOrFail($piId);
        $existingDetail = ProductIssuesDetail::where('pi_id', $piId)->where('status', 1)->firstOrFail();

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock);

        $this->post('/updateProductIssues', [
            'pi_id' => $piId,
            'pi_code' => $pi->pi_code,
            'pi_type' => $pi->pi_type,
            'ref_num' => 0,
            'tipe_return' => 1,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'GH #157 regression test, edited',
            'items' => json_encode([[
                'pid_id' => $existingDetail->pid_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_name' => 'GH157 test supplies',
                'unit_id' => $stock->unit_id,
                'pid_qty' => 7, // was 3
            ]]),
        ])->assertStatus(200);

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ss_stock, 'editing a pending document must never touch supplies stock');

        $existingDetail->refresh();
        $this->assertSame(7, $existingDetail->pid_qty);
    }
}
