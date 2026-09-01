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
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Support\StockOpname\BahanOpnameLifecycle;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Covers the guard half of GitHub #83's self-heal fix (companion to
 * ProductionAccSelfHealTest): self-heal must NOT auto-revert an orphaned production stock cut
 * if a Stock Opname Bahan has already been APPROVED for the same item+warehouse since the
 * orphan was cut. See docs/laporan-opname-vs-pending-production-2026-08-01.md (the PR0258
 * incident) — by that point the opname's approved physical count already reflects the
 * (incorrectly) reduced quantity as ground truth, so silently adding the quantity back would
 * inject stock that was never physically found on the shelf.
 *
 * In that situation ACC must refuse with a clear "needs manual review" response instead of
 * either (a) auto-restoring and corrupting the approved opname, or (b) the pre-fix behavior of
 * silently double-deducting.
 */
class ProductionAccSelfHealBlockedByOpnameTest extends TestCase
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
        $category->category_name = 'Self-Heal Blocked Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Self-Heal Blocked Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::UNIT_ID]);
        $product->unit_id = self::UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Self-Heal Blocked Test Variant';
        $variant->product_variant_sku = 'WF-SELFHEAL-BLOCKED-'.uniqid();
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
        $supplies->supplies_name = 'Self-Heal Blocked Test Supplies';
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

    public function test_retrying_acc_is_blocked_when_an_opname_already_locked_the_orphaned_cut(): void
    {
        $staff = $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $fx = $this->createFixture();
        $pdQty = 10;
        $correctDeduction = $pdQty * self::BOM_DETAIL_QTY; // 20

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Self-heal blocked-by-opname test production',
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

        // Same hand-simulated stuck state as ProductionAccSelfHealTest: an ACC attempt that
        // already cut ingredient stock and wrote its log, but never reached the status update.
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

        // Unlike the happy-path test: a Stock Opname Bahan now counts this exact item in this
        // exact warehouse AFTER the orphan cut, finds it matches the (already reduced) system
        // stock, and gets approved — freezing 980 as the physically-verified truth.
        $stob = new StockOpnameBahan();
        $stob->stob_date = now()->toDateString();
        $stob->stob_code = 'SB'.substr((string) microtime(true), -4);
        $stob->staff_id = (int) $staff->staff_id;
        $stob->status = 1;
        $stob->is_draft = false;
        $stob->is_old_version = false;
        $stob->warehouse_id = self::WAREHOUSE_ID;
        $stob->save();

        StockOpnameBahanLine::upsertLine([
            'stob_id' => $stob->stob_id,
            'supplies_id' => $fx['supplies']->supplies_id,
            'unit_id' => self::UNIT_ID,
            'sobl_counted_qty' => (int) $fx['suppliesStock']->fresh()->ss_stock,
            'sobl_notes' => null,
        ]);

        $lifecycle = new BahanOpnameLifecycle();
        $lifecycle->publish($stob);
        $lifecycle->freezeSystemQty($stob); // membekukan 980 sebagai "sistem" yang disetujui
        $stob->status = 2;
        $stob->save();
        $lifecycle->stampDecision($stob, (int) $staff->staff_id);

        // A plain retry — the same request a user clicking ACC again sends.
        $accResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $accResponse->assertStatus(200);
        $accResponse->assertJson(['status' => -4]);
        $accResponse->assertJsonFragment(['header' => 'Perlu Peninjauan Manual']);
        $this->assertStringContainsString(
            $stob->stob_code,
            $accResponse->json('message'),
            'the rejection must name the specific Stock Opname blocking the auto-restore, for whoever has to review it'
        );

        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'ACC must be refused outright — not silently processed with a stale deduction');

        $fx['suppliesStock']->refresh();
        $this->assertSame(
            self::STARTING_SUPPLIES_STOCK - $correctDeduction,
            $fx['suppliesStock']->ss_stock,
            'stock must be left exactly as the approved opname already verified it — neither restored (phantom stock) nor double-deducted'
        );

        // The orphaned log must survive untouched — self-heal never ran.
        $this->assertDatabaseHas('log_stocks', [
            'log_kode' => $production->production_code,
            'log_notes' => 'Pengurangan bahan untuk produksi (orphaned, simulating a pre-fix crash)',
            'status' => 1,
        ]);
    }
}
