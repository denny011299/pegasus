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
 * Stock Transfer real-case matrix (docs/backlog-stock-multi-gudang.md):
 * - Main → Main: ship unit as-is, receive same unit (no conversion/packing).
 * - Main → Retail: may ship DOS/default; Terima converts to retail_unit.
 * - Retail → Main: retail ships Piece only; main receives Piece.
 * - Kirim: packing OFF; main source may unpack ancestors; Cancel restores from logs.
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
     * Cancel Kirim restores sent-unit cut when Piece stock alone covers the ship
     * (no unpack needed). Ship 22 Piece from 30 Piece (+ unused DOS) → Cancel
     * restores Piece to 30, DOS untouched.
     */
    public function test_cancel_kirim_restores_sent_unit_stock_without_packing(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);
        $mainWarehouse2 = $this->createSecondMainWarehouse();

        $dosStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 5);
        $pieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 30);

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
        $this->assertSame(5.0, (float) $dosStock->ps_stock, 'Kirim Piece must not unpack/touch DOS when Piece alone is enough');
        $this->assertSame(8.0, (float) $pieceStock->ps_stock, '30 - 22 = 8 Piece left');

        $header->refresh();
        $this->assertSame(2, (int) $header->status, 'Kirim');

        $this->post('/cancelKirimStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $dosStock->refresh();
        $pieceStock->refresh();
        $this->assertSame(5.0, (float) $dosStock->ps_stock, 'DOS still untouched after Cancel Kirim');
        $this->assertSame(30.0, (float) $pieceStock->ps_stock, 'Cancel Kirim restores the 22 Piece');

        $header->refresh();
        $this->assertSame(5, (int) $header->status, 'Cancel Kirim');
    }

    /**
     * Main source may unpack ancestors on Kirim (packing still OFF).
     * 10 Piece + 5 DOS (1 DOS = 12 Piece) can ship 22 Piece by unpacking 1 DOS.
     * Cancel Kirim must restore exact pre-ship composition via log net delta.
     */
    public function test_ship_unpacks_ancestor_when_sent_unit_stock_is_short(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);
        $mainWarehouse2 = $this->createSecondMainWarehouse();

        $dosStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 5);
        $pieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 10);

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
        $header->refresh();
        // Unpack 1 DOS → +12 Piece, then ship 22: DOS 4, Piece 0.
        $this->assertSame(4.0, (float) $dosStock->ps_stock, '1 DOS unpacked to cover Piece shortfall');
        $this->assertSame(0.0, (float) $pieceStock->ps_stock, '10 + 12 - 22 = 0 Piece');
        $this->assertSame(2, (int) $header->status, 'Kirim');

        $check = $this->post('/checkTransferStock', [
            'from_warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'to_warehouse_id' => (int) $mainWarehouse2->id,
            'items' => [[
                'product_variant_id' => $fx['variant']->product_variant_id,
                'unit_id' => self::PIECE_UNIT_ID,
                'qty' => 50,
                'label' => 'short after ship',
            ]],
        ])->assertStatus(200)->json();
        $this->assertFalse($check['ok'] ?? true);
        $available = (float) ($check['shortages'][0]['available'] ?? -1);
        // Remaining: 4 DOS × 12 = 48 Piece equivalent.
        $this->assertEqualsWithDelta(48.0, $available, 1e-6);

        $this->post('/cancelKirimStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $dosStock->refresh();
        $pieceStock->refresh();
        $this->assertSame(5.0, (float) $dosStock->ps_stock, 'Cancel restores pre-ship DOS');
        $this->assertSame(10.0, (float) $pieceStock->ps_stock, 'Cancel restores pre-ship Piece');
    }

    /**
     * Even with unpack, Kirim refuses when ancestor equivalent is still short.
     * 1 Piece + 1 DOS (=12) cannot cover 20 Piece.
     */
    public function test_ship_refuses_when_ancestor_equivalent_still_short(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(defaultUnitId: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);
        $mainWarehouse2 = $this->createSecondMainWarehouse();

        $dosStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 1);
        $pieceStock = $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::PIECE_UNIT_ID, 1);

        $header = $this->createPendingTransfer(
            $fx['product'],
            $fx['variant'],
            self::MAIN_WAREHOUSE_ID,
            (int) $mainWarehouse2->id,
            self::PIECE_UNIT_ID,
            20
        );

        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $this->post('/shipStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => -1]);

        $dosStock->refresh();
        $pieceStock->refresh();
        $header->refresh();
        $this->assertSame(1.0, (float) $dosStock->ps_stock);
        $this->assertSame(1.0, (float) $pieceStock->ps_stock);
        $this->assertSame(1, (int) $header->status, 'must remain Pending');
    }

    /**
     * Main → retail: may ship in a larger unit (DOS), but Terima must land as retail_unit (Piece).
     */
    public function test_receiving_into_retail_converts_to_retail_unit(): void
    {
        $this->actingAsSuperAdminStaff();

        $fx = $this->createProductFixture(defaultUnitId: self::DOS_UNIT_ID, retailUnit: self::PIECE_UNIT_ID);
        $this->createDosPieceRelation($fx['variant']);

        $this->createProductStock($fx['variant'], self::MAIN_WAREHOUSE_ID, self::DOS_UNIT_ID, 2);

        $header = $this->createPendingTransfer(
            $fx['product'],
            $fx['variant'],
            self::MAIN_WAREHOUSE_ID,
            self::RETAIL_WAREHOUSE_ID,
            self::DOS_UNIT_ID,
            2
        );

        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $this->post('/shipStockTransfer', ['id' => $header->st_id])->assertJson(['status' => 1]);

        $this->withActiveWarehouse(self::RETAIL_WAREHOUSE_ID);
        $this->post('/accStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson(['status' => 1]);

        $destPiece = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', self::RETAIL_WAREHOUSE_ID)
            ->where('product_variant_id', $fx['variant']->product_variant_id)
            ->where('unit_id', self::PIECE_UNIT_ID)
            ->first();
        $destDos = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', self::RETAIL_WAREHOUSE_ID)
            ->where('product_variant_id', $fx['variant']->product_variant_id)
            ->where('unit_id', self::DOS_UNIT_ID)
            ->first();

        $this->assertNotNull($destPiece);
        $this->assertSame(24.0, (float) $destPiece->ps_stock, '2 DOS x 12 Piece = 24 Piece at retail');
        $this->assertTrue(
            $destDos === null || (float) $destDos->ps_stock === 0.0,
            'retail destination must not keep DOS stock'
        );

        $detail = StockTransferDetail::query()->where('st_id', $header->st_id)->first();
        $this->assertSame(self::PIECE_UNIT_ID, (int) $detail->received_unit_id);
        $this->assertSame(24.0, (float) $detail->qty_received);
    }

    /**
     * Real case pergudangan: receiving into a MAIN destination keeps the shipped unit
     * as-is (no repack to product default unit). Ship 50 Piece main→main → destination
     * gets 50 Piece, not 4 DOS + 2 Piece / 4.1667 DOS.
     */
    public function test_receiving_into_a_main_warehouse_keeps_sent_unit_without_conversion(): void
    {
        $this->actingAsSuperAdminStaff();

        // Default unit = DOS — old behaviour would have forced receive into DOS.
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

        $this->assertTrue(
            $destDos === null || (float) $destDos->ps_stock === 0.0,
            'main destination must NOT auto-pack Piece into DOS'
        );
        $this->assertNotNull($destPiece);
        $this->assertSame(50.0, (float) $destPiece->ps_stock, '50 Piece received as 50 Piece');

        $detail = StockTransferDetail::query()->where('st_id', $header->st_id)->first();
        $this->assertSame(self::PIECE_UNIT_ID, (int) $detail->received_unit_id);
        $this->assertSame(50.0, (float) $detail->qty_received);

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
