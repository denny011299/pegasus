<?php

namespace Tests\Regression;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Production;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Ported from main's `d416aba` (GitHub #87 follow-up). Confirms two things about a 3-level output
 * ladder (Piece -> DOS -> Sak) where NEITHER the middle (DOS) nor the top (Sak) `ProductStock` row
 * exists yet, and the produced quantity is an EXACT multiple of the whole ladder (24 Piece = 2 DOS
 * = 1 Sak) — so DOS's own net credit lands on exactly 0 (everything that reaches it immediately
 * carries on up to Sak, nothing stays behind):
 *
 * 1. The roll-up still reaches the TOP level correctly even though the row it passes THROUGH
 *    (DOS) never existed at all — walking `UnitRollUp::plan()`'s chain only ever depends on
 *    whether a level's combined total crosses ITS ratio, never on what credit that level nets to,
 *    so a middle level netting to 0 does not stop the cascade.
 * 2. The pre-check that asks the user to confirm "a new stock row will be created at 0" only lists
 *    DOS/Sak-style levels that will ACTUALLY be created. fase2's pre-check already guarded this
 *    correctly (`if ($credit['qty'] <= 0) continue;`) before this batch — this test locks that
 *    behavior in explicitly rather than leaving it unverified, now that the planner underneath it
 *    (`UnitRollUp::planProductOutput()`) is existing-aware (GitHub #87).
 */
class ProductionOutputCascadesThroughAMissingMiddleUnitTest extends TestCase
{
    use ActingAsStaff;

    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const SAK_UNIT_ID = 25;
    private const WAREHOUSE_ID = 1;

    public function test_a_middle_level_with_no_stock_row_and_a_zero_net_credit_does_not_block_the_cascade_or_get_falsely_promised(): void
    {
        $this->actingAsSuperAdminStaff();
        // Wajib: insertProduction()/accProduction() menolak kalau gudang aktif sesi bukan gudang
        // utama -- lihat memory pegasus-testing-db-multiwarehouse-drift.
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        $category = new Category();
        $category->category_name = 'Missing Middle Cascade Regression Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Missing Middle Cascade Regression Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Missing Middle Cascade Regression Variant';
        $variant->product_variant_sku = 'WF-CASCADE-MISSING-MID-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        // Only the Piece-level row exists. DOS and Sak are both missing entirely — not just zero.
        $pieceStock = new ProductStock();
        $pieceStock->product_id = $product->product_id;
        $pieceStock->product_variant_id = $variant->product_variant_id;
        $pieceStock->unit_id = self::PIECE_UNIT_ID;
        $pieceStock->warehouse_id = self::WAREHOUSE_ID;
        $pieceStock->ps_stock = 0;
        $pieceStock->status = 1;
        $pieceStock->save();

        // Level 1: 1 DOS = 12 Piece
        $relationDos = new ProductRelation();
        $relationDos->product_variant_id = $variant->product_variant_id;
        $relationDos->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relationDos->pr_unit_value_1 = 1;
        $relationDos->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $relationDos->pr_unit_value_2 = 12;
        $relationDos->pr_default = 0;
        $relationDos->status = 1;
        $relationDos->save();

        // Level 2: 1 Sak = 2 DOS (= 24 Piece)
        $relationSak = new ProductRelation();
        $relationSak->product_variant_id = $variant->product_variant_id;
        $relationSak->pr_unit_id_1 = self::SAK_UNIT_ID;
        $relationSak->pr_unit_value_1 = 1;
        $relationSak->pr_unit_id_2 = self::DOS_UNIT_ID;
        $relationSak->pr_unit_value_2 = 2;
        $relationSak->pr_default = 0;
        $relationSak->status = 1;
        $relationSak->save();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Missing Middle Cascade Regression Ingredient';
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
        $bomDetail->bom_detail_qty = 1;
        $bomDetail->unit_id = self::PIECE_UNIT_ID;
        $bomDetail->status = 1;
        $bomDetail->save();

        $pdQty = 24; // exact multiple all the way up: DOS's own net credit lands on exactly 0

        $insertResponse = $this->post('/insertProduction', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Missing middle cascade regression test',
            'detail' => json_encode([[
                'bom_id' => $bom->bom_id,
                'product_variant_id' => $variant->product_variant_id,
                'pd_qty' => $pdQty,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
            'list_bahan' => json_encode([[
                'supplies_id' => $supplies->supplies_id,
                'bom_detail_qty' => 1,
                'unit_id' => self::PIECE_UNIT_ID,
            ]]),
        ]);
        $insertResponse->assertStatus(200);
        $production = Production::orderByDesc('production_id')->firstOrFail();

        // First call: no confirmation flag. Must be blocked, and must ONLY ask about Sak — DOS's
        // own net credit is 0 so it will never actually be created, and must not be promised here.
        $firstResponse = $this->post('/accProduction', ['production_id' => $production->production_id]);
        $firstResponse->assertStatus(200);
        $firstResponse->assertJson(['status' => -3, 'header' => 'Konfirmasi Diperlukan']);
        $firstResponse->assertJsonFragment([
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => self::SAK_UNIT_ID,
        ]);
        $missingStock = $firstResponse->json('missing_stock');
        $this->assertCount(1, $missingStock, 'only Sak should be listed — DOS nets to 0 and will never actually be created');
        $this->assertSame(self::SAK_UNIT_ID, $missingStock[0]['unit_id']);

        $production->refresh();
        $this->assertSame(1, (int) $production->status, 'blocked pending confirmation — must not be approved yet');
        $this->assertNull(
            ProductStock::where('product_variant_id', $variant->product_variant_id)
                ->where('unit_id', self::SAK_UNIT_ID)
                ->where('status', 1)
                ->first(),
            'blocked pending confirmation — the Sak row must not be created yet either'
        );

        // Second call: confirmed. The cascade must still reach Sak even though it walks THROUGH a
        // DOS row that never existed and never gets created (its own net credit is 0).
        $secondResponse = $this->post('/accProduction', [
            'production_id' => $production->production_id,
            'confirm_create_stock' => 1,
        ]);
        $secondResponse->assertStatus(200);

        $production->refresh();
        $this->assertSame(2, (int) $production->status);

        $pieceStock->refresh();
        $this->assertEquals(0, $pieceStock->ps_stock, '24 is an exact multiple of the whole ladder — nothing left over at Piece');

        $dosStock = ProductStock::where('product_variant_id', $variant->product_variant_id)
            ->where('unit_id', self::DOS_UNIT_ID)
            ->where('status', 1)
            ->first();
        $this->assertNull($dosStock, 'DOS nets to a 0 credit — it is a pure pass-through and is never actually provisioned');

        $sakStock = ProductStock::where('product_variant_id', $variant->product_variant_id)
            ->where('unit_id', self::SAK_UNIT_ID)
            ->where('status', 1)
            ->first();
        $this->assertNotNull($sakStock, 'the cascade reached all the way past the missing DOS row to Sak');
        $this->assertEquals(1, $sakStock->ps_stock, '24 Piece = 2 DOS = 1 Sak, credited directly at the top');

        $this->assertDatabaseHas('log_stocks', [
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::SAK_UNIT_ID,
            'log_jumlah' => 1,
        ]);
        // No log at all for DOS — nothing was ever credited there to log.
        $this->assertDatabaseMissing('log_stocks', [
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => self::DOS_UNIT_ID,
        ]);
    }
}
