<?php

namespace Tests\DatabaseTransaction;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\ProductionDetails;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * ✅ FIXED (confirmed 2026-08-29) — this file used to characterize a real defect and now guards
 * against its return.
 *
 * History: `accDeleteProduction` (approving a request to cancel an already-approved production)
 * had NO `DB::transaction()` anywhere in its ~300-line body — the same gap `accProduction` had
 * before `main` commit `2d73633` fixed it, never applied here. Its reversal loop also carried the
 * identical "ladder split" null-guard gap characterized for `accProduction` itself
 * (`$ps_depan->ps_stock -= $kurangDos;` against a `ProductStock` row that doesn't exist at the
 * larger unit), and its own reversibility pre-check only verified the COMBINED total across both
 * unit levels — so a product with enough stock entirely at the smaller unit passed the pre-check,
 * then crashed mid-reversal. The result was the worst possible outcome: a 500, with the production
 * already permanently cancelled and stock neither reversed nor consistent.
 *
 * Both halves are now closed on `fase2/main`: `accDeleteProduction` runs inside a real
 * `DB::beginTransaction()`/`commit()`/`rollBack()`, and the reversal goes through
 * `ProductUnitStock::deductQty()` instead of the raw ladder split, so a missing larger-unit row is
 * never written to. This test now asserts the correct end state — cancelled production, output
 * reversed, ingredient restored, no invented larger-unit row.
 *
 * The production/detail rows here are still seeded directly (bypassing `insertProduction`/
 * `accProduction`) to precisely control the "already approved" starting state — the point is
 * `accDeleteProduction`'s own behavior, not re-verifying approval.
 */
class ProductionCancelRequestAtomicityTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_approving_a_cancel_request_reverses_stock_atomically(): void
    {
        $this->actingAsSuperAdminStaff();
        // Wajib: insertProduction()/accProduction() menolak kalau gudang aktif sesi bukan gudang
        // utama, dan menolaknya sebagai HTTP 200 + body error -- tanpa pin ini insert-nya gagal
        // diam-diam lalu test mengambil produksi lama yang sudah di-ACC (gagal "-2 sudah
        // diterma/ditolak"). Lihat memory pegasus-testing-db-multiwarehouse-drift.
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Cancel Atomicity Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Cancel Atomicity Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Cancel Atomicity Test Variant';
        $variant->product_variant_sku = 'WF-CANCELATOMIC-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // Only a Piece-level ProductStock row exists — deliberately NO DOS-level row, even though
        // a ladder relation is configured below. 20 pieces in stock is enough to pass the
        // reversal's own "is there enough combined stock" pre-check on its own.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 20;
        $pieceStock->status = 1;
        $pieceStock->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID; // larger unit — NO ProductStock row exists here
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Cancel Atomicity Test Supplies';
        $supplies->supplies_unit = json_encode([self::PIECE_UNIT_ID]);
        $supplies->supplies_default_unit = self::PIECE_UNIT_ID;
        $supplies->status = 1;
        $supplies->save();

        $suppliesStock = new SuppliesStock();
        $suppliesStock->supplies_id = $supplies->supplies_id;
        $suppliesStock->unit_id = self::PIECE_UNIT_ID;
        $suppliesStock->warehouse_id = self::WAREHOUSE_ID;
        $suppliesStock->ss_stock = 1000;
        $suppliesStock->status = 1;
        $suppliesStock->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id;
        $bom->bom_qty = 1;
        $bom->unit_id = self::PIECE_UNIT_ID;
        $bom->status = 1;
        $bom->save();

        $bomDetail = new BomDetail();
        $bomDetail->bom_id = $bom->bom_id;
        $bomDetail->supplies_id = $supplies->supplies_id;
        $bomDetail->bom_detail_qty = 2;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        // Seed an already-approved production directly (bypassing insertProduction/accProduction)
        // — pd_qty=20 is >= the 12-per-DOS ladder value, triggering the crashing branch.
        $production = new Production();
        $production->production_date = now()->toDateString();
        $production->production_code = 'PRCNCLTST';
        $production->production_desc = 'Cancel atomicity test production';
        $production->production_created_by = 1;
        $production->status = 2;
        $production->save();

        $detail = new ProductionDetails();
        $detail->production_id = $production->production_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->pd_qty = 20;
        $detail->unit_id = self::PIECE_UNIT_ID;
        $detail->bom_id = $bom->bom_id;
        $detail->status = 1;
        $detail->save();

        $this->post('/deleteProduction', [
            'production_id' => $production->production_id,
            'delete_reason' => 'DB transaction atomicity test',
        ])->assertStatus(200);

        $production->refresh();
        $this->assertSame(4, (int) $production->status, 'the cancel request is pending');

        $accResponse = $this->post('/accDeleteProduction', ['production_id' => $production->production_id]);

        // No longer an uncaught 500 — the cancel now completes cleanly.
        $accResponse->assertStatus(200);

        // The production really is cancelled...
        $production->refresh();
        $this->assertSame(3, (int) $production->status, 'the cancel request is approved and the production ends cancelled');

        // ...and, unlike before, the stock state actually MATCHES that outcome. The 20 produced
        // Piece are reversed out instead of being stranded at their post-approval value.
        $pieceStock->refresh();
        $this->assertSame(0, $pieceStock->ps_stock, 'the produced output is reversed, consistent with the cancelled status');

        // Still no phantom larger-unit row: the reversal goes through ProductUnitStock::deductQty()
        // rather than the old raw "$ps_depan->ps_stock -= ..." ladder split, so a missing DOS-level
        // row is simply never written to (previously it was what crashed the request).
        $this->assertSame(
            0,
            ProductStock::where('product_variant_id', $variant->product_variant_id)->where('unit_id', self::DOS_UNIT_ID)->count(),
            'no larger-unit row is invented by the reversal'
        );

        // The ingredient restoration loop now runs too — it used to be unreachable, because the
        // product-stock loop ahead of it crashed first.
        $suppliesStock->refresh();
        $this->assertSame(1040, $suppliesStock->ss_stock, 'the ingredient consumed by the production is restored');
    }
}
