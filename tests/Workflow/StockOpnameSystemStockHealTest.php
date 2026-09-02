<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\Unit;
use App\Support\StockOpname\OpnameLifecycle;
use App\Support\StockOpname\OpnameLineReader;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Permintaan user 2026-08-31 (GitHub #91, di-port dari main `aabafe2`): kalau stok LIVE sudah stuck
 * under-rolled dari sebelum GitHub #87 (mis. HKCP60P: 12 DOS + 24 Piece padahal 1 DOS = 24 Piece,
 * seharusnya 13 DOS + 0 Piece), membuat Stock Opname harus ikut menyembuhkan angka live itu untuk
 * satuan yang TIDAK dihitung di dokumennya -- bukan cuma menggulung nilai hitungan di dalam dokumen
 * (rollUpUnits(), yang sudah ada lebih dulu dan tidak menyentuh ps_stock sama sekali).
 *
 * Dipicu di dua titik (lihat StockController): insertStockOpname() (dokumen baru langsung
 * menunggu) dan submitStockOpname() (draft -> menunggu). TIDAK dipicu selama masih draft.
 *
 * BEDA DARI VERSI MAIN: di sini penyembuhannya dipin ke gudang DOKUMEN, bukan gudang aktif sesi --
 * main tidak punya konsep gudang untuk stok sama sekali. Lihat
 * test_only_heals_stock_in_the_documents_own_warehouse() di bawah, yang mengunci justru kasus itu.
 */
class StockOpnameSystemStockHealTest extends TestCase
{
    use ActingAsStaff;

    private const WAREHOUSE_ID = 1;
    private const OTHER_WAREHOUSE_ID = 13;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /** @return array{0: ProductVariant, 1: ProductStock, 2: ProductStock, 3: Unit, 4: Unit} */
    private function makeFixtureWithLadder(int $dosStock, int $pcsStock, int $ratio = 24, int $warehouseId = self::WAREHOUSE_ID): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $units->count(), 'fixture butuh minimal 2 satuan aktif');
        [$dosUnit, $pcsUnit] = $units->all();

        $category = new Category();
        $category->category_name = 'Opname Heal Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname Heal Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $product->unit_id = $dosUnit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Varian Uji Heal';
        $variant->product_variant_sku = 'HEAL-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = $dosUnit->unit_id; // besar
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $pcsUnit->unit_id; // kecil
        $relation->pr_unit_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        $stocks = [];
        foreach ([[$dosUnit, $dosStock], [$pcsUnit, $pcsStock]] as [$unit, $qty]) {
            $s = new ProductStock();
            $s->product_id = $product->product_id;
            $s->product_variant_id = $variant->product_variant_id;
            $s->unit_id = $unit->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ps_stock = $qty;
            $s->status = 1;
            $s->save();
            $stocks[] = $s;
        }

        return [$variant, $stocks[0], $stocks[1], $dosUnit, $pcsUnit];
    }

    /**
     * Baca ulang satu baris stok TANPA global scope `active_warehouse` -- assertion di test ini
     * harus melihat baris yang memang dimaksud, bukan baris gudang aktif sesi yang kebetulan
     * berjalan. (Di main tidak perlu: tidak ada scope-nya sama sekali di sana.)
     */
    private function freshStock(ProductStock $stock): ProductStock
    {
        return ProductStock::withoutGlobalScope('active_warehouse')->findOrFail($stock->ps_id);
    }

    private function makeDocument(bool $isDraft, int $warehouseId = self::WAREHOUSE_ID): StockOpname
    {
        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'H'.substr((string) microtime(true), -5);
        $sto->staff_id = $this->staffId();
        $sto->category_id = -1;
        $sto->warehouse_id = $warehouseId;
        $sto->status = OpnameLineReader::STATUS_MENUNGGU;
        $sto->is_draft = $isDraft;
        $sto->is_old_version = false;
        $sto->save();

        return $sto;
    }

    /** @param array<int, int|null> $countedByUnitId */
    private function addLines(StockOpname $sto, ProductVariant $variant, array $countedByUnitId): void
    {
        foreach ($countedByUnitId as $unitId => $counted) {
            StockOpnameLine::upsertLine([
                'sto_id' => $sto->sto_id,
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'unit_id' => $unitId,
                'sol_counted_qty' => $counted,
                'sol_notes' => null,
            ]);
        }
    }

    /** Persis contoh user: 12 DOS + 24 Piece (1 DOS = 24 Piece) -> 13 DOS + 0 Piece, kedua satuan tak dihitung. */
    public function test_heals_untouched_stuck_under_rolled_stock_on_a_pending_document(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(13, (int) $this->freshStock($dos)->ps_stock);
        $this->assertSame(0, (int) $this->freshStock($pcs)->ps_stock);
    }

    /** Satuan yang SEDANG dihitung staf tidak boleh disentuh -- ACC yang menimpanya langsung. */
    public function test_does_not_touch_a_unit_currently_being_counted(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);
        $sto = $this->makeDocument(isDraft: false);
        // Piece sedang dihitung staf (5) -- DOS dibiarkan kosong.
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => 5]);

        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(24, (int) $this->freshStock($pcs)->ps_stock, 'satuan yang sedang dihitung tidak boleh disembuhkan di sini');
    }

    /** Draft tidak boleh memicu penyembuhan sama sekali. */
    public function test_does_nothing_while_still_draft(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);
        $sto = $this->makeDocument(isDraft: true);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(12, (int) $this->freshStock($dos)->ps_stock);
        $this->assertSame(24, (int) $this->freshStock($pcs)->ps_stock);
    }

    /** Stok yang sudah benar tidak boleh berubah dan tidak boleh menulis log apa pun. */
    public function test_is_a_no_op_when_stock_is_already_canonical(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 13, pcsStock: 0, ratio: 24);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        $before = LogStock::count();
        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(13, (int) $this->freshStock($dos)->ps_stock);
        $this->assertSame(0, (int) $this->freshStock($pcs)->ps_stock);
        $this->assertSame($before, LogStock::count(), 'stok yang sudah kanonik tidak boleh menulis log');
    }

    /** Berjalan berkali-kali tidak boleh mengubah apa pun lagi setelah sembuh sekali. */
    public function test_is_idempotent(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        $lifecycle = new OpnameLifecycle();
        $lifecycle->healUntouchedSystemStock($sto);
        $lifecycle->healUntouchedSystemStock($sto);

        $this->assertSame(13, (int) $this->freshStock($dos)->ps_stock);
        $this->assertSame(0, (int) $this->freshStock($pcs)->ps_stock);
    }

    /**
     * fase2-only, tidak ada di main: dokumen milik gudang A tidak boleh menyembuhkan (atau bahkan
     * MEMBACA) stok gudang B. Kalau lookup-nya lupa dipin ke gudang dokumen, stok kedua gudang
     * akan tergabung jadi satu kolam dan gudang B ikut ditulis ulang -- korupsi diam-diam persis
     * seperti yang sudah diperbaiki di seluruh alur Stock Opname pada Batch 11.
     */
    public function test_only_heals_stock_in_the_documents_own_warehouse(): void
    {
        $this->actingAsSuperAdminStaff();

        // Gudang A: stuck (12 DOS + 24 Piece) -- ini yang dimiliki dokumen.
        [$variant, $dosA, $pcsA, $dosUnit, $pcsUnit] = $this->makeFixtureWithLadder(
            dosStock: 12, pcsStock: 24, ratio: 24, warehouseId: self::WAREHOUSE_ID
        );

        // Gudang B: varian yang SAMA, juga stuck, tapi bukan urusan dokumen ini sama sekali.
        $dosB = new ProductStock();
        $dosB->product_id = $variant->product_id;
        $dosB->product_variant_id = $variant->product_variant_id;
        $dosB->unit_id = $dosUnit->unit_id;
        $dosB->warehouse_id = self::OTHER_WAREHOUSE_ID;
        $dosB->ps_stock = 7;
        $dosB->status = 1;
        $dosB->save();

        $pcsB = new ProductStock();
        $pcsB->product_id = $variant->product_id;
        $pcsB->product_variant_id = $variant->product_variant_id;
        $pcsB->unit_id = $pcsUnit->unit_id;
        $pcsB->warehouse_id = self::OTHER_WAREHOUSE_ID;
        $pcsB->ps_stock = 48; // 48 Piece = 2 DOS -- juga stuck, tapi harus DIBIARKAN
        $pcsB->status = 1;
        $pcsB->save();

        $sto = $this->makeDocument(isDraft: false, warehouseId: self::WAREHOUSE_ID);
        $this->addLines($sto, $variant, [$dosUnit->unit_id => null, $pcsUnit->unit_id => null]);

        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        // Gudang A disembuhkan...
        $this->assertSame(13, (int) $this->freshStock($dosA)->ps_stock);
        $this->assertSame(0, (int) $this->freshStock($pcsA)->ps_stock);

        // ...gudang B sama sekali tidak tersentuh, meski sama-sama stuck dan varian yang sama.
        $this->assertSame(7, (int) $this->freshStock($dosB)->ps_stock, 'stok gudang lain tidak boleh ikut disembuhkan');
        $this->assertSame(48, (int) $this->freshStock($pcsB)->ps_stock, 'stok gudang lain tidak boleh ikut disembuhkan');
    }

    /** Menembus insertStockOpname() sungguhan -- dokumen baru langsung menunggu memicu penyembuhan. */
    public function test_wired_into_insert_stock_opname_endpoint(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::WAREHOUSE_ID);

        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);

        $items = json_encode([[
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->product_variant_id,
            'units' => [
                ['unit_id' => $dos->unit_id, 'real_qty' => null],
                ['unit_id' => $pcs->unit_id, 'real_qty' => null],
            ],
        ]]);

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => -1,
            'is_draft' => 0,
            'item' => $items,
        ]);

        $response->assertStatus(200);

        $this->assertSame(13, (int) $this->freshStock($dos)->ps_stock);
        $this->assertSame(0, (int) $this->freshStock($pcs)->ps_stock);
    }
}
