<?php

namespace Tests\Workflow;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Covers the self-heal mechanism added in main commit `2d73633` (merged into this branch),
 * `ProductionController::revertPendingProductionStockMutations()` — see
 * `docs/production-acc-stock-safety.md` and `cdocs/testing/KNOWN_ISSUES.md`'s finished-goods
 * null-guard entry for the real incident this was built to recover from (`PR0258`: an ACC attempt
 * crashed partway through, leaving `supplies_stocks` already decremented and `log_stocks` already
 * written while `productions.status` stayed at `1`/pending).
 *
 * Before this fix, retrying ACC on such a stuck production would apply ANOTHER full ingredient
 * deduction on top of the orphaned one already sitting in stock — silently doubling the loss and,
 * per the real incident report, making the "bahan baku tidak mencukupi" shortfall message grow
 * worse on every retry rather than resolving it. This test reproduces that exact stuck state by
 * hand (bypassing the now-fixed crash path entirely) and confirms a plain retry recovers cleanly:
 * self-heal reverts the orphaned mutation BEFORE the pre-check runs, then the real ACC applies the
 * correct deduction exactly once.
 */
class ProductionAccSelfHealTest extends TestCase
{
    use ActingAsStaff;

    private const UNIT_ID = 9; // Piece
    private const WAREHOUSE_ID = 1;
    private const BOM_DETAIL_QTY = 2;
    private const STARTING_SUPPLIES_STOCK = 1000;

    /** @return array{variant: ProductVariant, productStock: ProductStock, supplies: Supplies, suppliesStock: SuppliesStock, bom: Bom} */
    private function createFixture(): array
    {
        $category = new Category();
        $category->category_name = 'Self-Heal Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Self-Heal Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Self-Heal Test Variant';
        $variant->product_variant_sku = 'WF-SELFHEAL-'.uniqid();
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
        $supplies->supplies_name = 'Self-Heal Test Supplies';
        $supplies->supplies_unit = json_encode([self::UNIT_ID]);
        $supplies->supplies_default_unit = self::UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = self::STARTING_SUPPLIES_STOCK;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
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

        return compact('variant', 'productStock', 'supplies', 'suppliesStock', 'bom');
    }

    public function test_retrying_acc_on_a_stuck_pending_production_self_heals_before_reapplying(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createFixture();
        $pdQty = 10;
        $correctDeduction = $pdQty * self::BOM_DETAIL_QTY; // 20

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Self-heal test production',
            'detail' => json_encode([[
                'bom_id' => $fx['bom']->bom_id,
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $fx['supplies']->supplies_id,
                'bom_detail_qty' => self::BOM_DETAIL_QTY,
                'unit_id' => self::UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        // Hand-simulate the OLD bug's aftermath: an ACC attempt that already cut ingredient stock
        // and wrote its log, but crashed before ever reaching the status update — bypassing the
        // (now-fixed) crash path entirely, since reproducing it isn't the point of this test.
        $fx['suppliesStock']->ss_stock = self::STARTING_SUPPLIES_STOCK - $correctDeduction;
        $fx['suppliesStock']->save();
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => $production->production_code,
            'log_type' => 2,
            'log_category' => 2,
            'log_item_id' => $fx['supplies']->supplies_id,
            'log_notes' => 'Pengurangan bahan untuk produksi (orphaned, simulating a pre-fix crash)',
            'log_jumlah' => $correctDeduction,
            'unit_id' => self::UNIT_ID,
        ]);
        $this->assertSame(1, (int) $production->fresh()->status, 'the production must still look pending, exactly like the real stuck-production incident');

        // A plain retry — no special payload, this is exactly what a user clicking ACC again does.
        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status, 'the retried ACC must succeed cleanly once self-heal clears the orphaned state');

        $fx['suppliesStock']->refresh();
        $this->assertSame(
            self::STARTING_SUPPLIES_STOCK - $correctDeduction,
            $fx['suppliesStock']->ss_stock,
            'self-heal must undo the orphaned cut, then the real ACC deducts the correct amount exactly ONCE — not double-deducted (1000-20-20=960) and not left under-deducted'
        );

        $fx['productStock']->refresh();
        $this->assertSame($pdQty, $fx['productStock']->ps_stock, 'output credit must apply normally once the retry succeeds');

        // The orphaned log is gone (deleted by self-heal); only fresh logs from the successful
        // retry remain for this production_code.
        $this->assertDatabaseMissing('log_stocks', [
            'log_kode' => $production->production_code,
            'log_notes' => 'Pengurangan bahan untuk produksi (orphaned, simulating a pre-fix crash)',
        ]);
        $remainingLogs = DB::table('log_stocks')->where('log_kode', $production->production_code)->get();
        $this->assertTrue($remainingLogs->every(fn ($l) => $l->log_notes !== 'Pengurangan bahan untuk produksi (orphaned, simulating a pre-fix crash)'));
    }
}
