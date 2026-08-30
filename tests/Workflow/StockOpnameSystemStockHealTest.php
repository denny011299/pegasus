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
 * Permintaan user 2026-08-31: kalau stok LIVE sudah stuck under-rolled dari sebelum GitHub #87
 * (mis. HKCP60P: 12 DOS + 24 Piece padahal 1 DOS = 24 Piece, seharusnya 13 DOS + 0 Piece), membuat
 * Stock Opname harus ikut menyembuhkan angka live itu untuk satuan yang TIDAK dihitung di
 * dokumennya -- bukan cuma menggulung nilai hitungan di dalam dokumen (rollUpUnits(), yang sudah
 * ada lebih dulu dan tidak menyentuh ps_stock sama sekali).
 *
 * Dipicu di dua titik (lihat StockController): insertStockOpname() (dokumen baru langsung
 * menunggu) dan submitStockOpname() (draft -> menunggu). TIDAK dipicu selama masih draft.
 */
class StockOpnameSystemStockHealTest extends TestCase
{
    use ActingAsStaff;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /** @return array{0: ProductVariant, 1: ProductStock, 2: ProductStock, 3: Unit, 4: Unit} */
    private function makeFixtureWithLadder(int $dosStock, int $pcsStock, int $ratio = 24): array
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
            $s->warehouse_id = 1;
            $s->ps_stock = $qty;
            $s->status = 1;
            $s->save();
            $stocks[] = $s;
        }

        return [$variant, $stocks[0], $stocks[1], $dosUnit, $pcsUnit];
    }

    private function makeDocument(bool $isDraft): StockOpname
    {
        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'H'.substr((string) microtime(true), -5);
        $sto->staff_id = $this->staffId();
        $sto->category_id = -1;
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

        $this->assertSame(13, (int) ProductStock::find($dos->ps_id)->ps_stock);
        $this->assertSame(0, (int) ProductStock::find($pcs->ps_id)->ps_stock);
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

        $this->assertSame(24, (int) ProductStock::find($pcs->ps_id)->ps_stock, 'satuan yang sedang dihitung tidak boleh disembuhkan di sini');
    }

    /** Draft tidak boleh memicu penyembuhan sama sekali. */
    public function test_does_nothing_while_still_draft(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);
        $sto = $this->makeDocument(isDraft: true);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(12, (int) ProductStock::find($dos->ps_id)->ps_stock);
        $this->assertSame(24, (int) ProductStock::find($pcs->ps_id)->ps_stock);
    }

    /** Stok yang sudah benar tidak boleh berubah dan tidak boleh menulis log apa pun. */
    public function test_is_a_no_op_when_stock_is_already_canonical(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 13, pcsStock: 0, ratio: 24);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);

        $before = LogStock::count();
        (new OpnameLifecycle())->healUntouchedSystemStock($sto);

        $this->assertSame(13, (int) ProductStock::find($dos->ps_id)->ps_stock);
        $this->assertSame(0, (int) ProductStock::find($pcs->ps_id)->ps_stock);
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

        $this->assertSame(13, (int) ProductStock::find($dos->ps_id)->ps_stock);
        $this->assertSame(0, (int) ProductStock::find($pcs->ps_id)->ps_stock);
    }

    /** Menembus insertStockOpname() sungguhan -- dokumen baru langsung menunggu memicu penyembuhan. */
    public function test_wired_into_insert_stock_opname_endpoint(): void
    {
        $this->actingAsSuperAdminStaff();

        [$variant, $dos, $pcs, , $pcsUnit] = $this->makeFixtureWithLadder(dosStock: 12, pcsStock: 24, ratio: 24);

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

        $this->assertSame(13, (int) ProductStock::find($dos->ps_id)->ps_stock);
        $this->assertSame(0, (int) ProductStock::find($pcs->ps_id)->ps_stock);
    }
}
