<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Supplier;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (2026-08-06): the `$siapkanStok`/`$siapkanStokCek`/`$siapkanStokProd` closures
 * duplicated across `ProductIssuesDetail.php` (×2), `StockController.php` (×1), and
 * `ProductionController.php` (×3) all find the "unit above" via a `ProductRelation`/
 * `SuppliesRelation` lookup (`pr_unit_id_1`/`su_id_1`), not a fixed array index — so a
 * malformed/circular relation chain (unit A's parent is B, B's parent is A) recursed
 * indefinitely with no depth counter of its own. The outer `$safety > 500` loop next to each of
 * these does NOT catch this, since the unbounded recursion happens *inside* one single call to
 * the closure, never returning control to the loop to check that counter.
 *
 * Fixed by adding a `$depth` parameter (default 0, incremented on each recursive call, capped at
 * 20 — mirrors the `$guard < 20` convention already used elsewhere in this codebase for exactly
 * this kind of traversal guard) to all 6 at-risk closures. The 2 *other* `$siapkanStok` closures
 * in `CustomerController.php` were NOT touched — they recurse via a fixed array index
 * (`$targetKey + 1`), which cannot cycle, so they were never actually at risk.
 *
 * This test proves the depth guard actually engages on deliberately circular relation data
 * (Return Supplies / `ProductIssuesDetail::stockCheck()` and Production /
 * `ProductionController::insertProduction()`'s ingredient-sufficiency precheck, one representative
 * call site each — the other 4 share byte-identical closure bodies and the same fix) — it does NOT
 * reproduce the pre-fix infinite-recursion behavior directly, since doing so would hang or crash
 * the PHP process running the test suite itself rather than raising a catchable exception.
 */
class BongkarRecursionDepthGuardTest extends TestCase
{
    use ActingAsStaff;

    private const LITER = 3;
    private const DRUM = 5;
    private const JERIGEN = 2;
    private const PIECE = 9;

    public function test_return_supplies_bongkar_does_not_hang_on_a_circular_supplies_relation_chain(): void
    {
        $this->actingAsSuperAdminStaff();

        $supplier = Supplier::where('status', 1)->whereNotNull('bank_id')->firstOrFail();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Circular Relation Depth Guard Test Ingredient';
        $supplies->supplies_unit = json_encode([self::LITER, self::DRUM]);
        $supplies->supplies_default_unit = self::LITER;
        $supplies->status = 1;
        $supplies->save();

        $variant = new SuppliesVariant();
        $variant->supplier_id = $supplier->supplier_id;
        $variant->supplies_id = $supplies->supplies_id;
        $variant->supplies_variant_name = 'Circular Relation Depth Guard Test Variant';
        $variant->supplies_variant_sku = 'WF-CIRCGUARD-'.uniqid();
        $variant->supplies_variant_barcode = 'WF-CIRCGUARD-BC-'.uniqid();
        $variant->supplies_variant_price = 1000;
        $variant->supplies_variant_stock = 0;
        $variant->status = 1;
        $variant->save();

        // Deliberately circular: Liter's "unit above" is Drum, AND Drum's "unit above" is Liter.
        // Real product/supplies data never looks like this (every real chain traced this session
        // has been a clean, acyclic ladder) — this is purely to prove the guard engages.
        $relationUp = new SuppliesRelation();
        $relationUp->supplies_id = $supplies->supplies_id;
        $relationUp->su_id_1 = self::DRUM;
        $relationUp->su_id_2 = self::LITER;
        $relationUp->sr_value_1 = 1;
        $relationUp->sr_value_2 = 200;
        $relationUp->status = 1;
        $relationUp->save();

        $relationDown = new SuppliesRelation();
        $relationDown->supplies_id = $supplies->supplies_id;
        $relationDown->su_id_1 = self::LITER;
        $relationDown->su_id_2 = self::DRUM;
        $relationDown->sr_value_1 = 1;
        $relationDown->sr_value_2 = 1;
        $relationDown->status = 1;
        $relationDown->save();

        // Both stock rows start at zero, so the bongkar closure can never find a positive balance
        // anywhere in the (circular) chain to actually terminate on — the only thing that stops it
        // is the depth guard.
        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = self::LITER;
        $literStock->warehouse_id = 1;
        $literStock->ss_stock = 0;
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = self::DRUM;
        $drumStock->warehouse_id = 1;
        $drumStock->ss_stock = 0;
        $drumStock->status = 1;
        $drumStock->save();

        $po = new PurchaseOrder();
        $po->po_number = 'WF-CIRCGUARD-'.uniqid();
        $po->po_supplier = $supplier->supplier_id;
        $po->po_date = now()->toDateString();
        $po->po_total = 1000000;
        $po->jenis_discount = 1;
        $po->po_desc = 'Circular relation depth guard test PO';
        $po->po_img = json_encode([]);
        $po->status = 1;
        $po->save();

        $startedAt = microtime(true);

        $response = $this->post('/insertReturnSupplies', [
            'po_id' => $po->po_id,
            'rs_date' => now()->toDateString(),
            'rs_notes' => 'Circular relation depth guard test',
            'rs_total' => 10,
            'returs' => json_encode([[
                'supplies_id' => $variant->supplies_id,
                'supplies_variant_id' => $variant->supplies_variant_id,
                'supplies_variant_name' => $variant->supplies_variant_name,
                'unit_id' => self::LITER,
                'rsd_qty' => 10,
                'rsd_price' => 1,
            ]]),
        ]);

        $elapsed = microtime(true) - $startedAt;

        $response->assertStatus(200);
        $this->assertLessThan(3.0, $elapsed, 'the depth guard must make this fail fast, not hang');
        $response->assertJson(['status' => -1]);

        $literStock->refresh();
        $drumStock->refresh();
        $this->assertSame(0, $literStock->ss_stock, 'a guarded-off bongkar must not have mutated anything');
        $this->assertSame(0, $drumStock->ss_stock, 'a guarded-off bongkar must not have mutated anything');
    }

    /**
     * Mirrors tests/Workflow/ProductionUnitConversionFlowTest.php's fixture exactly (same
     * field/payload shapes: `Bom::product_id` stores a product_variant_id,
     * `BomDetail::bom_detail_qty`, `/insertProduction`'s `detail`/`list_bahan` payload keys) — only
     * difference is the deliberately circular SuppliesRelation chain and zero starting stock on
     * both units, to prove the depth guard engages instead of hanging/crashing.
     */
    public function test_production_ingredient_bongkar_does_not_hang_on_a_circular_supplies_relation_chain(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);

        $category = new Category();
        $category->category_name = 'Circular Relation Depth Guard Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Circular Relation Depth Guard Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE]);
        $product->unit_id = self::PIECE;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Circular Relation Depth Guard Variant';
        $variant->product_variant_sku = 'WF-CIRCGUARD-PROD-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $productStock = new ProductStock();
        $productStock->product_id = $product->product_id;
        $productStock->product_variant_id = $variant->product_variant_id;
        $productStock->unit_id = self::PIECE;
        $productStock->warehouse_id = 1;
        $productStock->ps_stock = 0;
        $productStock->status = 1;
        $productStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id; // stores a product_variant_id
        $bom->bom_qty = 1;
        $bom->unit_id = self::PIECE;
        $bom->status = 1;
        $bom->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Circular Relation Depth Guard Test Ingredient';
        $supplies->supplies_unit = json_encode([self::LITER, self::DRUM, self::JERIGEN]);
        $supplies->supplies_default_unit = self::LITER;
        $supplies->status = 1;
        $supplies->save();

        // insertProduction() runs a separate, EARLIER precheck (validateBomSuppliesSmallestUnit())
        // that rejects the recipe outright if its own unit has any downward relation
        // (su_id_1 == recipe's unit) — so the cycle can't sit directly on Liter (the recipe's
        // unit) without tripping that different guard first. Instead: Liter's parent is Drum
        // (one-way, keeps Liter validly "smallest"), and the cycle sits one level up, between Drum
        // and Jerigen — Drum's parent is Jerigen, AND Jerigen's parent is Drum. Once bongkar-ing
        // Liter needs to climb past Drum, it hits that 2-cycle and would recurse forever without
        // the depth guard. Real supplies data never looks like this (every real chain traced this
        // session has been a clean, acyclic ladder) — this is purely to prove the guard engages.
        $literParentIsDrum = new SuppliesRelation();
        $literParentIsDrum->supplies_id = $supplies->supplies_id;
        $literParentIsDrum->su_id_1 = self::DRUM;
        $literParentIsDrum->su_id_2 = self::LITER;
        $literParentIsDrum->sr_value_1 = 1;
        $literParentIsDrum->sr_value_2 = 200;
        $literParentIsDrum->status = 1;
        $literParentIsDrum->save();

        $drumParentIsJerigen = new SuppliesRelation();
        $drumParentIsJerigen->supplies_id = $supplies->supplies_id;
        $drumParentIsJerigen->su_id_1 = self::JERIGEN;
        $drumParentIsJerigen->su_id_2 = self::DRUM;
        $drumParentIsJerigen->sr_value_1 = 1;
        $drumParentIsJerigen->sr_value_2 = 5;
        $drumParentIsJerigen->status = 1;
        $drumParentIsJerigen->save();

        $jerigenParentIsDrum = new SuppliesRelation();
        $jerigenParentIsDrum->supplies_id = $supplies->supplies_id;
        $jerigenParentIsDrum->su_id_1 = self::DRUM;
        $jerigenParentIsDrum->su_id_2 = self::JERIGEN;
        $jerigenParentIsDrum->sr_value_1 = 1;
        $jerigenParentIsDrum->sr_value_2 = 1;
        $jerigenParentIsDrum->status = 1;
        $jerigenParentIsDrum->save();

        // All three stock rows start at zero, so the bongkar closure can never find a positive
        // balance anywhere in the (circular) chain above Liter to actually terminate on — only the
        // depth guard stops it.
        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = self::LITER;
        $literStock->warehouse_id = 1;
        $literStock->ss_stock = 0;
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = self::DRUM;
        $drumStock->warehouse_id = 1;
        $drumStock->ss_stock = 0;
        $drumStock->status = 1;
        $drumStock->save();

        $jerigenStock = new SuppliesStock();
        $jerigenStock->supplies_id = $supplies->supplies_id;
        $jerigenStock->unit_id = self::JERIGEN;
        $jerigenStock->warehouse_id = 1;
        $jerigenStock->ss_stock = 0;
        $jerigenStock->status = 1;
        $jerigenStock->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 10;
        $bomDetail->unit_id = self::LITER;
        $bomDetail->status = 1;
        $bomDetail->save();

        // This closure is exercised at INSERT time (ProductionController::insertProduction()'s own
        // ingredient-sufficiency precheck), not at accProduction() — a guarded-off bongkar means
        // the insert itself is cleanly rejected, no Production row ever gets created.
        $productionCountBefore = Production::count();
        $startedAt = microtime(true);

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Circular relation depth guard test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => 1,
                'unit_id' => self::PIECE,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 10,
                'unit_id' => self::LITER,
            ]]),
        ]);

        $elapsed = microtime(true) - $startedAt;

        $this->assertLessThan(3.0, $elapsed, 'the depth guard must make this fail fast, not hang');
        $insertResponse->assertStatus(200);
        $insertResponse->assertJson(['status' => -1]);
        $this->assertSame($productionCountBefore, Production::count(), 'a guarded-off bongkar must reject the insert, not create a Production row');

        $literStock->refresh();
        $drumStock->refresh();
        $jerigenStock->refresh();
        $this->assertSame(0, $literStock->ss_stock, 'a guarded-off bongkar must not have mutated anything');
        $this->assertSame(0, $drumStock->ss_stock, 'a guarded-off bongkar must not have mutated anything');
        $this->assertSame(0, $jerigenStock->ss_stock, 'a guarded-off bongkar must not have mutated anything');
    }
}
