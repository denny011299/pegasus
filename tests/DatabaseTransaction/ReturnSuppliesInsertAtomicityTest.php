<?php

namespace Tests\DatabaseTransaction;

use App\Models\ProductIssues;
use App\Models\PurchaseOrder;
use App\Models\ReturnSupplies;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-24): `SupplierController::insertReturnSupplies()` had no `DB::transaction()`,
 * and — like `updateSalesOrder()` — its worst exit was a *normal business path*, not a crash.
 *
 * The reachable trigger is a genuine logic gap, not a contrived one: the pre-check loop validates
 * each return line INDEPENDENTLY against the full current stock (`$ss->ss_stock - $value['rsd_qty']
 * < 0`), while the write loop deducts CUMULATIVELY. Two lines for the same supplies + unit that
 * each individually fit, but together exceed stock, therefore sail through the pre-check and only
 * blow up partway through the writes — at which point `return -1` used to exit with the PO total
 * already reduced, the ProductIssues + ReturnSupplies headers already created, and the FIRST
 * line's stock already deducted and logged. All permanent.
 *
 * That left the books doubly wrong: supplier stock reduced for a return that was reported back to
 * the caller as failed (`-1`), plus an orphaned ProductIssues/ReturnSupplies pair referencing it.
 *
 * The fix wraps everything from the first write (`$po->po_total -= $total`) onward in one
 * transaction, with `rollBack()` on that `-1` path and on any throw. The cumulative-vs-independent
 * pre-check discrepancy itself is left as-is (unchanged behavior, still returns -1) — this test
 * pins the atomicity, not a redesign of the validation.
 *
 * Fixtures built fresh via Eloquent — see memory `pegasus-testing-db-multiwarehouse-drift`.
 */
class ReturnSuppliesInsertAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const STARTING_STOCK = 10;
    private const QTY_PER_LINE = 6; // 6 <= 10 individually, but 6 + 6 = 12 > 10 cumulatively

    /** @return array{0: SuppliesStock, 1: SuppliesVariant, 2: PurchaseOrder} */
    private function createFixture(): array
    {
        $unit = Unit::where('status', 1)->firstOrFail();
        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Return Atomicity Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([$unit->unit_id]);
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_variant_name = 'Return Atomicity Variant';
        $variant->supplies_variant_sku = 'DBTX-RET-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_barcode = 'DBTX-BC-'.uniqid();
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        // Exactly ONE stock row and no supplies_relations, so stockCheck() has no bongkar
        // ladder to fall back on — keeps the scenario purely about cumulative deduction.
        $stock = new SuppliesStock();
        $stock->supplies_id = $supplies->supplies_id;
        $stock->unit_id = $unit->unit_id;
        $stock->warehouse_id = 1;
        $stock->ss_stock = self::STARTING_STOCK;
        $stock->status = 1;
        $stock->save();

        $po = new PurchaseOrder();
        $po->po_number = 'PO-DBTX-RET-'.uniqid();
        $po->po_supplier = $supplier->supplier_id;
        $po->po_date = now()->toDateString();
        $po->po_img = json_encode([]);
        $po->po_total = 1000000;
        $po->status = 2;
        $po->save();

        return [$stock, $variant, $po];
    }

    public function test_a_cumulative_shortfall_mid_loop_rolls_back_every_earlier_write(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stock, $variant, $po] = $this->createFixture();

        $startingStock = $stock->ss_stock;
        $startingPoTotal = $po->po_total;
        $logCountBefore = DB::table('log_stocks')->count();
        $piCountBefore = ProductIssues::count();
        $rsCountBefore = ReturnSupplies::count();

        $line = [
            'supplies_id' => $stock->supplies_id,
            'supplies_variant_id' => $variant->supplies_variant_id,
            'supplies_variant_name' => $variant->supplies_variant_name,
            'unit_id' => $stock->unit_id,
            'rsd_qty' => self::QTY_PER_LINE,
            'rsd_price' => 1000,
        ];

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $po->po_id,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Return supplies atomicity test',
            'rs_total' => self::QTY_PER_LINE * 2 * 1000,
            'returs' => json_encode([$line, $line]), // same supplies+unit twice
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            '-1',
            trim($response->getContent()),
            'the second line still fails with the existing -1 response (behavior unchanged)'
        );

        // THE FIX: none of the first line's writes may survive.
        $stock->refresh();
        $this->assertSame(
            $startingStock,
            $stock->ss_stock,
            'BUG WOULD BE: stock reduced by the first line only, for a return reported as failed'
        );

        $po->refresh();
        $this->assertSame(
            $startingPoTotal,
            (int) $po->po_total,
            'the PO total reduction must roll back with everything else'
        );

        $this->assertSame(
            $logCountBefore,
            DB::table('log_stocks')->count(),
            'no log_stocks row may survive'
        );
        $this->assertSame(
            $piCountBefore,
            ProductIssues::count(),
            'no orphaned ProductIssues header may survive'
        );
        $this->assertSame(
            $rsCountBefore,
            ReturnSupplies::count(),
            'no orphaned ReturnSupplies header may survive'
        );
    }

    public function test_a_return_that_fits_still_commits_normally(): void
    {
        $this->actingAsSuperAdminStaff();

        [$stock, $variant, $po] = $this->createFixture();
        $startingStock = $stock->ss_stock;
        $qty = 4; // 4 + 4 = 8 <= 10, so both lines fit cumulatively

        $line = [
            'supplies_id' => $stock->supplies_id,
            'supplies_variant_id' => $variant->supplies_variant_id,
            'supplies_variant_name' => $variant->supplies_variant_name,
            'unit_id' => $stock->unit_id,
            'rsd_qty' => $qty,
            'rsd_price' => 1000,
        ];

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $po->po_id,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Return supplies atomicity happy path',
            'rs_total' => $qty * 2 * 1000,
            'returs' => json_encode([$line, $line]),
        ]);
        $response->assertStatus(200);

        // Proves the transaction COMMITS rather than swallowing valid work.
        $stock->refresh();
        $this->assertSame(
            $startingStock - ($qty * 2),
            $stock->ss_stock,
            'a return that fits must still deduct both lines and commit'
        );
        $this->assertSame(
            1,
            ReturnSupplies::where('po_id', $po->po_id)->where('status', 1)->count(),
            'the ReturnSupplies header is created and kept'
        );
    }
}
