<?php

namespace Tests\Regression;

use App\Http\Controllers\ProductionController;
use App\Models\Bom;
use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Support\ProductUnitStock;
use Illuminate\Http\Request;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #134 — "[Bug] [Fase 2] Produksi module", 4 separate bugs bundled in one issue. See
 * cdocs/testing/KNOWN_ISSUES.md for the write-up of each. Bug #1 (cancel-production modal left
 * open/empty on a rejected batal request) is a pure front-end fix in
 * public/Custom_js/Backoffice/Production/Production.js — not covered here, nothing server-side
 * to assert against.
 */
class ProductionModuleIssue134Test extends TestCase
{
    use ActingAsStaff;

    private const WAREHOUSE_ID = 1;
    private const DOS_UNIT_ID = 7;   // DOS
    private const PCS_UNIT_ID = 9;   // Piece

    /**
     * Bug #2: reversing a production output that needs to "bongkar" (unpack) a bigger unit to
     * satisfy the smaller unit being deducted used to hardcode "Stock Transfer - ..." log notes
     * regardless of caller — even when the deduction has nothing to do with a Stock Transfer
     * (e.g. cancelling a plain warehouse-mode production). ProductUnitStock::deductQty() now
     * accepts an explicit $unpackNotePrefix so callers outside the ST flow can say what they
     * actually are.
     */
    public function test_deduct_qty_unpack_notes_use_the_callers_own_context_not_stock_transfer(): void
    {
        $variant = $this->createLadderVariant();

        // Stock exists at both levels, but Piece is at 0 — deducting Piece must bongkar 1 DOS.
        $this->createProductStock($variant->product_variant_id, self::DOS_UNIT_ID, 5);
        $this->createProductStock($variant->product_variant_id, self::PCS_UNIT_ID, 0);

        $result = ProductUnitStock::deductQty(
            self::WAREHOUSE_ID,
            $variant->product_variant_id,
            self::PCS_UNIT_ID,
            10, // < 1 DOS (12 pcs) worth, forces an unpack
            'PR-TEST-134',
            'Pembatalan produksi PR-TEST-134',
            false,
            true,
            'Pembatalan produksi'
        );

        $this->assertTrue($result['ok'], $result['message'] ?? 'expected ok');

        $notes = LogStock::where('log_kode', 'PR-TEST-134')->pluck('log_notes')->all();
        $this->assertNotEmpty($notes);
        foreach ($notes as $note) {
            $this->assertStringNotContainsString('Stock Transfer', $note);
            $this->assertStringContainsString('Pembatalan produksi', $note);
        }
    }

    /** Default behaviour (no $unpackNotePrefix passed) is unchanged for existing ST callers. */
    public function test_deduct_qty_unpack_notes_default_to_stock_transfer_when_no_prefix_given(): void
    {
        $variant = $this->createLadderVariant();
        $this->createProductStock($variant->product_variant_id, self::DOS_UNIT_ID, 5);
        $this->createProductStock($variant->product_variant_id, self::PCS_UNIT_ID, 0);

        $result = ProductUnitStock::deductQty(
            self::WAREHOUSE_ID,
            $variant->product_variant_id,
            self::PCS_UNIT_ID,
            10,
            'ST-TEST-134',
            'Stock Transfer ST-TEST-134 - keluar gudang asal'
        );

        $this->assertTrue($result['ok'], $result['message'] ?? 'expected ok');

        $notes = LogStock::where('log_kode', 'ST-TEST-134')
            ->where('log_notes', 'like', '%bongkar%')
            ->pluck('log_notes')->all();
        $this->assertNotEmpty($notes);
        foreach ($notes as $note) {
            $this->assertStringContainsString('Stock Transfer', $note);
        }
    }

    /**
     * Bug #3: Bom::searchForAutocomplete() used to always inject the BOM's own recipe unit
     * (boms.unit_id) into the produksi unit dropdown even when Daftar Produk's configured
     * product_unit list didn't include it (real-world case: UNICAL LITHIUM configured with only
     * Pail in Daftar Produk, but its BOM recipe was set up in Piece — could then pick Piece in
     * Produksi even though Daftar Produk only offers Pail).
     */
    public function test_bom_autocomplete_unit_choices_do_not_leak_the_recipe_unit_when_not_configured(): void
    {
        $category = new Category();
        $category->category_name = 'Issue134 Bom Unit Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Issue134 Pail Only Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::DOS_UNIT_ID]); // only "DOS" configured
        $product->unit_id = self::DOS_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Default';
        $variant->product_variant_sku = 'ISSUE134-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $bom = new Bom();
        $bom->product_id = $variant->product_variant_id; // boms.product_id is actually a variant id
        $bom->bom_qty = 1;
        $bom->unit_id = self::PCS_UNIT_ID; // recipe written in Piece, NOT in product_unit
        $bom->status = 1;
        $bom->save();

        $result = (new Bom())->searchForAutocomplete(['search' => 'Issue134 Pail Only Product'], 30);
        $this->assertNotEmpty($result['data']);
        $row = collect($result['data'])->first();

        $unitIds = collect($row->pr_unit)->pluck('unit_id')->all();
        $this->assertContains(self::DOS_UNIT_ID, $unitIds, 'the configured product_unit must stay selectable');
        $this->assertNotContains(
            self::PCS_UNIT_ID,
            $unitIds,
            'a BOM recipe unit that is not part of product_unit must not leak into the dropdown'
        );
    }

    /**
     * Bug #4: a race between "+Tambah" (checkProductionStock) and "Tambah Produksi"
     * (insertProduction) could let an empty `detail` payload reach insertProduction()
     * unblocked — validateProductionItems() finds nothing wrong in an empty array, so it used to
     * create a Production header row with zero ProductionDetails ("transaksi kosongan" in the
     * list). insertProduction() now rejects an empty/missing detail up front.
     */
    public function test_insert_production_rejects_empty_detail_instead_of_creating_an_empty_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $countBefore = \App\Models\Production::count();

        $req = Request::create('/insertProduction', 'POST', [
            'production_date' => now()->toDateString(),
            'production_desc' => 'Issue134 empty detail race',
            'detail' => json_encode([]),
            'list_bahan' => json_encode([]),
        ]);

        $response = (new ProductionController())->insertProduction($req);
        $payload = json_decode($response->getContent(), true);

        $this->assertSame(0, $payload['status']);
        $this->assertSame(\App\Models\Production::count(), $countBefore, 'no Production row should be created');
    }

    private function createLadderVariant(): ProductVariant
    {
        $category = new Category();
        $category->category_name = 'Issue134 Ladder Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Issue134 Ladder Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::DOS_UNIT_ID, self::PCS_UNIT_ID]);
        $product->unit_id = self::DOS_UNIT_ID;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Default';
        $variant->product_variant_sku = 'ISSUE134-LADDER-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = self::PCS_UNIT_ID;
        $relation->pr_unit_value_2 = 12; // 1 DOS = 12 Piece
        $relation->status = 1;
        $relation->save();

        return $variant;
    }

    private function createProductStock(int $productVariantId, int $unitId, float $qty): ProductStock
    {
        $stock = new ProductStock();
        $stock->product_id = ProductVariant::find($productVariantId)->product_id;
        $stock->product_variant_id = $productVariantId;
        $stock->unit_id = $unitId;
        $stock->warehouse_id = self::WAREHOUSE_ID;
        $stock->ps_stock = $qty;
        $stock->status = 1;
        $stock->save();

        return $stock;
    }
}
