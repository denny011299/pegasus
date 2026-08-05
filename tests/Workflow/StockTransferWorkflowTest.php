<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Covers the four fixes from docs/backlog-stock-multi-gudang.md items #6-#9:
 * 1) Retail warehouses must not unpack non-retail units (ProductUnitStock hardening).
 * 2) `updateStockTransfer` must validate a pending production ST with the production
 *    unit rules (same flag `shipStockTransfer` already used).
 * 3) Cancel Kirim must restore the EXACT pre-ship unit composition, even when Kirim
 *    used packing/unpacking across the unit chain (`allow_packing=true`, main warehouse).
 * 4) Receiving into a main warehouse must split a non-exact conversion into whole
 *    target-unit qty + remainder in the smaller/sent unit, instead of storing a
 *    fractional `ps_stock` (e.g. 4 DOS + 2 Piece, not 4.1667 DOS).
 *
 * Real seed warehouse ids: 1 = Gudang Pusat (main), 2 = Gudang Eceran Toko (retail).
 * Real seed unit ids: 7 = DOS, 9 = Piece.
 */
class StockTransferWorkflowTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;
    private const RETAIL_WAREHOUSE_ID = 2;
    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;

    /** Second main warehouse — needed for item #4's "utama -> utama" scenario. */
    private function createSecondMainWarehouse(): Warehouse
    {
        $mainTypeId = (int) Warehouse::query()->findOrFail(self::MAIN_WAREHOUSE_ID)->warehouse_type_id;

        $wh = new Warehouse();
        $wh->warehouse_name = 'WF Second Main ' . uniqid();
        $wh->warehouse_type_id = $mainTypeId;
        $wh->status = 1;
        $wh->save();

        return $wh;
    }

    /** @return array{variant: ProductVariant, product: Product} */
    private function createProductFixture(int $defaultUnitId, ?int $retailUnit = null): array
    {
        $category = new Category();
        $category->category_name = 'ST Workflow Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'ST Workflow Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID, self::DOS_UNIT_ID]);
        $product->unit_id = $defaultUnitId;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'ST Workflow Test Variant';
        $variant->product_variant_sku = 'WF-ST-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $retailUnit;
        $variant->status = 1;
        $variant->save();

        return compact('variant', 'product');
    }

    private function createDosPieceRelation(ProductVariant $variant): void
    {
        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;   // larger unit
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PIECE_UNIT_ID; // smaller unit
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->pr_default = 0;
        $relation->status = 1;
        $relation->save();
    }

    private function createProductStock(ProductVariant $variant, int $warehouseId, int $unitId, float $stock): ProductStock
    {
        $ps = new ProductStock();
        $ps->product_id = $variant->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $unitId;
        $ps->warehouse_id = $warehouseId;
        $ps->ps_stock = $stock;
        $ps->status = 1;
        $ps->save();

        return $ps;
    }

    private function createPendingTransfer(
        Product $product,
        ProductVariant $variant,
        int $fromWarehouseId,
        int $toWarehouseId,
        int $unitId,
        float $qty,
        ?string $sourceType = null
    ): StockTransfer {
        $header = new StockTransfer();
        $header->transfer_code = 'WFST' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->sender_id = (int) (session('user')->staff_id ?? 0);
        $header->from_warehouse_id = $fromWarehouseId;
        $header->to_warehouse_id = $toWarehouseId;
        $header->source_type = $sourceType;
        $header->status = 1;
        $header->save();

        $detail = new StockTransferDetail();
        $detail->st_id = $header->st_id;
        $detail->product_id = $product->product_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->unit_id = $unitId;
        $detail->qty = $qty;
        $detail->status = 1;
        $detail->save();

        return $header;
    }

    /**
     * Item #2 — `updateStockTransfer` must pass `isProduction` into `validateTransferItems`
     * (same as `shipStockTransfer`/`checkItems`). Reproduces the exact divergence from the
     * backlog: destination is a main warehouse, so the *production* rule accepts any sent
     * unit as-is (`resolveTransferUnits`'s early return for production + main destination),
     * while the *normal* rule demands the sent unit be inside the default-unit conversion
     * chain. Sending in DOS with a product whose default unit (Piece) has NO relation to
     * DOS makes the two rules disagree — only the production rule allows it.
     */
    public function test_updating_a_pending_production_transfer_validates_with_production_unit_rules(): void
    {
        $this->actingAsSuperAdminStaff();

        // Default unit = Piece, and deliberately NO product_relations row at all, so DOS
        // is not reachable from Piece via any conversion chain.
        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID);
        $mainWarehouse2 = $this->createSecondMainWarehouse();
        // Enough DOS stock so the post-edit stock check (exact unit, no packing for
        // production) passes and the only thing under test is the matrix validation.
        $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 5);

        $header = $this->createPendingTransfer(
            $fx['product'],
            $fx['variant'],
            self::MAIN_WAREHOUSE_ID,
            (int) $mainWarehouse2->id,
            self::PIECE_UNIT_ID,
            10,
            sourceType: 'production'
        );

        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        // Edit: switch the sent unit to DOS, which has no conversion chain to the
        // product's default unit (Piece). Only the production rule (destination is a
        // main warehouse => unit accepted as-is) allows this; the normal rule would
        // reject it with "rantai konversi" — proving the isProduction flag now reaches
        // validateTransferItems from updateStockTransfer.
        $response = $this->post('/updateStockTransfer', [
            'id' => $header->st_id,
            'transfer_date' => now()->toDateString(),
            'from_warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'to_warehouse_id' => (int) $mainWarehouse2->id,
            'items' => [[
                'product_variant_id' => $fx['variant']->product_variant_id,
                'unit_id' => self::DOS_UNIT_ID,
                'qty' => 3,
            ]],
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 1]);

        $detail = StockTransferDetail::query()
            ->where('st_id', $header->st_id)
            ->where('status', 1)
            ->first();
        $this->assertNotNull($detail, 'the edited item must have been persisted');
        $this->assertSame(self::DOS_UNIT_ID, (int) $detail->unit_id);
        $this->assertEquals(3, (float) $detail->qty);
    }

    /**
     * Item #3 — Cancel Kirim must restore the exact pre-ship unit composition, not just
     * addQty in the sent unit. Ship 22 Piece from a main warehouse holding 5 DOS + 2 Piece
     * (62 Piece-equivalent) forces `deductPackedQty` to unpack 2 DOS -> 24 Piece then
     * deduct 22, leaving 3 DOS + 4 Piece. "DOS belum dibuka": Cancel Kirim must bring it
     * back to exactly 5 DOS + 2 Piece, reversing the packing delta too (not just the 22
     * Piece that was nominally shipped).
     */
    public function test_cancel_kirim_restores_the_exact_pre_ship_unit_composition_after_packing(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);
        $mainWarehouse2 = $this->createSecondMainWarehouse();

        $dosStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 5);
        $pieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 2);

        $header = $this->createPendingTransfer(
            $fx['product'],
            $fx['variant'],
            self::MAIN_WAREHOUSE_ID,
            (int) $mainWarehouse2->id,
            self::PIECE_UNIT_ID,
            22
        );

        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $this->post('/shipStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $dosStock->refresh();
        $pieceStock->refresh();
        $this->assertSame(3.0, (float) $dosStock->ps_stock, 'sanity check: 2 DOS were unpacked to cover the 22 Piece shortfall (5-2=3)');
        $this->assertSame(4.0, (float) $pieceStock->ps_stock, 'sanity check: 2(2)+2 DOS x 12=26 available, 26-22=4 left');

        $header->refresh();
        $this->assertSame(2, (int) $header->status, 'Kirim');

        $this->post('/cancelKirimStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $dosStock->refresh();
        $pieceStock->refresh();
        $this->assertSame(5.0, (float) $dosStock->ps_stock, 'Cancel Kirim must restore DOS to its exact pre-ship qty (DOS belum dibuka)');
        $this->assertSame(2.0, (float) $pieceStock->ps_stock, 'Cancel Kirim must restore Piece to its exact pre-ship qty, not add the 22 shipped back on top');

        $header->refresh();
        $this->assertSame(5, (int) $header->status, 'Cancel Kirim');
    }

    /**
     * Item #4 — receiving into a MAIN destination warehouse must split a non-exact
     * conversion into a whole target-unit qty + remainder in the sent unit, instead of
     * storing a fractional `ps_stock`. Example straight from the user's spec: ship 50
     * Piece, 1 DOS = 12 Piece -> destination must end up with 4 DOS + 2 Piece (not
     * 4.1667 DOS).
     */
    public function test_receiving_into_a_main_warehouse_splits_into_whole_units_plus_remainder(): void
    {
        $this->actingAsSuperAdminStaff();

        // Default unit = DOS, so a main->main transfer's target unit resolves to DOS.
        $fx = $this->createProductFixture(defaultUnitId: self::DOS_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);
        $mainWarehouse2 = $this->createSecondMainWarehouse();

        $sourcePieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 50);

        $header = $this->createPendingTransfer(
            $fx['product'],
            $fx['variant'],
            self::MAIN_WAREHOUSE_ID,
            (int) $mainWarehouse2->id,
            self::PIECE_UNIT_ID,
            50
        );

        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $this->post('/shipStockTransfer', ['id' => $header->st_id])->assertJson(['status' => 1]);

        $sourcePieceStock->refresh();
        $this->assertSame(0.0, (float) $sourcePieceStock->ps_stock, 'all 50 Piece left the source warehouse');

        $this->withActiveWarehouse((int) $mainWarehouse2->id);
        $this->post('/accStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $destDos = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $mainWarehouse2->id)
            ->where('product_variant_id', $fx['variant']->product_variant_id)
            ->where('unit_id', self::DOS_UNIT_ID)
            ->first();
        $destPiece = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $mainWarehouse2->id)
            ->where('product_variant_id', $fx['variant']->product_variant_id)
            ->where('unit_id', self::PIECE_UNIT_ID)
            ->first();

        $this->assertNotNull($destDos);
        $this->assertNotNull($destPiece);
        $this->assertSame(4.0, (float) $destDos->ps_stock, '50 Piece / 12 = 4 whole DOS');
        $this->assertSame(2.0, (float) $destPiece->ps_stock, 'remainder of 50 Piece / 12 = 2 Piece, not folded into a fractional DOS');

        $header->refresh();
        $this->assertSame(4, (int) $header->status, 'Terkirim');
    }

    /**
     * Item #1 (runtime hardening) — a retail warehouse must never unpack a non-retail
     * unit, even for leftover/dirty `product_stocks` rows in a larger unit (DOS). This
     * guards against the exact scenario documented in backlog item #7: a retail
     * warehouse still holding DOS stock alongside (or instead of) its retail_unit (Piece).
     */
    public function test_retail_warehouse_deduct_refuses_to_unpack_leftover_non_retail_stock(): void
    {
        $this->actingAsSuperAdminStaff();
        \App\Support\ProductUnitStock::clearCache();

        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID, retailUnit: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);

        // Dirty data: leftover DOS stock in the retail warehouse, no retail_unit (Piece)
        // stock row at all.
        $dosStock = $this->createProductStock($fx['variant'], self::RETAIL_WAREHOUSE_ID, self::DOS_UNIT_ID, 10);

        $result = \App\Support\ProductUnitStock::deductQty(
            self::RETAIL_WAREHOUSE_ID,
            (int) $fx['variant']->product_variant_id,
            self::PIECE_UNIT_ID,
            5,
            'WF-TEST',
            'test deduct',
            true // allow_packing requested by caller -> must still be refused for retail
        );

        $this->assertFalse($result['ok'], 'a retail warehouse must never unpack DOS to satisfy a Piece deduction');

        $dosStock->refresh();
        $this->assertSame(10.0, (float) $dosStock->ps_stock, 'the leftover DOS row must be untouched');
    }
}
