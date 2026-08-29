<?php

namespace Tests\Workflow;

use App\Http\Controllers\StockController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Follow-up to GitHub #53, reported by the user after the PDF-highlight fix shipped:
 * stod_system/stod_selisih are frozen at creation/edit time and never refresh. The race that
 * exposes it: Opname A is created (freezing "system stock" at that instant), then — before A is
 * approved — Opname B for the SAME product is approved, which overwrites the real system stock.
 * A's PDF, if printed while still pending, would silently show the stale pre-B system figure.
 *
 * Fix (StockController):
 *  - accStockOpname()/accStockOpnameBahan() now re-freeze stod_system/stod_selisih (Bahan:
 *    stobd_*) to the TRUE value used at approval — the same "before" figure already written to
 *    log_stocks — instead of leaving the creation-time snapshot in place.
 *  - refreshLiveSystemQty() (called from generateStockOpname()/generateStockOpnameBahan() only
 *    for status==1/pending docs) recomputes system/selisih live from the already-attached
 *    ->stock collection (see ProductVariant::getProductVariantBulk()/Supplies::getSuppliesBulk())
 *    instead of trusting the stored string — an approved/rejected doc's frozen snapshot is a
 *    deliberate historical record and must NOT be recomputed live.
 *
 * PM task (2026-08-24): refreshLiveSystemQty() used to only mutate the in-memory clone handed to
 * the PDF view — the underlying stock_opname_details/stock_opname_detail_bahans row stayed stale
 * until accStockOpname()/accStockOpnameBahan() froze it. Now it also writes stod_system/
 * stod_selisih (stobd_* for Bahan) back to the real row it was cloned from, via
 * $detailModelClass::find($item->{$detailIdKey}), every time the PDF is downloaded — but the
 * status==1 gate around the call site is unchanged, so this still only ever fires for a
 * draft (is_draft=true, "fase 2") or submitted-but-pending document (both have status==1 — see
 * StockOpname::insertStockOpname()/submitStockOpname(), is_draft never touches status), never for
 * an approved/rejected one.
 */
class StockOpnamePdfLiveSystemStockTest extends TestCase
{
    use ActingAsStaff;

    /**
     * Built fresh via Eloquent rather than picked from seeded/live data (per cdocs/testing's
     * "fixtures too entangled to hand-pick cleanly" guidance): refreshLiveSystemQty() builds
     * stod_system/stod_selisih from the item's WHOLE ->stock collection (every ACTIVE ProductStock
     * row for that product_variant_id across ALL warehouses, no warehouse_id filter at all — see
     * ProductVariant::getProductVariantBulk()), keyed only by unit_short_name. The local DB this
     * suite runs against has real multi-warehouse data where the SAME unit has several rows across
     * different warehouses (confirmed empirically) — picking one of those would make the live
     * recompute silently collide/overwrite across warehouses and produce a flaky, meaningless
     * result. A brand-new product+variant with exactly ONE ProductStock row total sidesteps that
     * entirely, same technique as ProductionFlowTest::createFixture().
     */
    private function pickFixtureStock(): ProductStock
    {
        $unit = Unit::where('status', 1)->firstOrFail();

        $category = new Category();
        $category->category_name = 'Opname PDF Live Stock Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname PDF Live Stock Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Opname PDF Live Stock Test Variant';
        $variant->product_variant_sku = 'WF-OPNAME-PDF-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $unit->unit_id;
        $stock->warehouse_id = 1;
        $stock->ps_stock = 100;
        $stock->status = 1;
        $stock->save();

        return $stock;
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function unitShortName(ProductStock $stock): string
    {
        return (string) (Unit::find($stock->unit_id)->unit_short_name ?? 'pcs');
    }

    /**
     * Bangun dokumen LAMA secara langsung.
     *
     * Sejak rancang ulang 2026-08-27, /insertStockOpname membuat dokumen VERSI BARU, sementara
     * seluruh berkas test ini subjeknya justru mekanika penyimpanan LAMA: string stod_system yang
     * dibekukan sekali lalu ditulis ULANG oleh refreshLiveSystemQty() setiap kali PDF diunduh.
     * Jalur itu masih hidup dan masih harus dijaga selama dokumen lama masih ada di produksi,
     * jadi test-nya diarahkan ke sana, bukan diubah maknanya.
     *
     * Padanan versi barunya sudah ada dan berperilaku BERBEDA dengan sengaja -- membaca dokumen
     * yang masih menunggu tidak lagi menulis apa pun ke DB. Lihat
     * Tests\Workflow\StockOpnameV2LifecycleTest.
     */
    private function insertStockOpname(ProductStock $stock, string $unit, int $realQty): int
    {
        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'LG'.substr((string) microtime(true), -4);
        $sto->staff_id = $this->staffId();
        $sto->category_id = -1;
        $sto->status = 1;
        $sto->is_draft = 0;
        $sto->save();
        $this->assertTrue((bool) $sto->refresh()->is_old_version);

        $d = new StockOpnameDetail();
        $d->sto_id = $sto->sto_id;
        $d->product_id = $stock->product_id;
        $d->product_variant_id = $stock->product_variant_id;
        $d->stod_system = $stock->ps_stock.' '.$unit;
        $d->stod_real = $realQty.' '.$unit;
        $d->stod_selisih = ($realQty - $stock->ps_stock).' '.$unit;
        $d->stod_touched = 1;
        $d->status = 1;
        $d->save();

        return (int) $sto->sto_id;
    }

    private function approve(ProductStock $stock, int $stoId, int $realQty): void
    {
        $this->post('/accStockOpname', [
            'sto_id' => $stoId,
            'item' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'units' => [[
                    'unit_id' => $stock->unit_id,
                    'real_qty' => $realQty,
                ]],
            ]]),
        ])->assertStatus(200);
    }

    private function parseQty(?string $string, string $unit): ?int
    {
        if (!$string) {
            return null;
        }
        foreach (explode(',', $string) as $part) {
            [$qty, $u] = array_pad(explode(' ', trim($part)), 2, null);
            if ($u === $unit) {
                return (int) $qty;
            }
        }
        return null;
    }

    public function test_a_still_pending_document_pdf_data_reflects_live_stock_not_the_stale_creation_time_snapshot(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        // A created first, matching system stock at that instant.
        $stoIdA = $this->insertStockOpname($stock, $unit, realQty: $startingStock);

        // Before A is approved, B (same product) is approved and moves the real stock.
        $stoIdB = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 15);
        $this->approve($stock, $stoIdB, $startingStock - 15);

        $stock->refresh();
        $this->assertSame($startingStock - 15, $stock->ps_stock, "sanity: B's approval must have moved the live stock");

        // Prove the stored row really is stale (this is the bug being guarded against).
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoIdA,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => $startingStock.' '.$unit,
        ]);

        // What generateStockOpname() feeds the PDF for a still-pending doc must be live, not that
        // stale row -- refreshLiveSystemQty() is private, called only from there, so invoke it
        // directly (ReflectionMethod is already an established pattern in this suite, see
        // tests/Health/SchemaConsistencyTest.php).
        $detail = StockOpnameDetail::getDetail(['sto_id' => $stoIdA]);
        $refresh = new ReflectionMethod(StockController::class, 'refreshLiveSystemQty');
        $refresh->setAccessible(true);
        $refresh->invoke(new StockController(), $detail, 'stod_real', 'stod_system', 'stod_selisih', 'ps_stock', StockOpnameDetail::class, 'stod_id');

        $row = $detail->firstWhere('product_variant_id', $stock->product_variant_id);
        $this->assertSame($startingStock - 15, $this->parseQty($row->stod_system, $unit), 'PDF data for a pending doc must show the CURRENT live system stock');
        $this->assertSame(15, $this->parseQty($row->stod_selisih, $unit), 'selisih must be recomputed against the live system stock too');

        // PM task (2026-08-24): this must now also be true of the REAL stock_opname_details row,
        // not just the in-memory clone handed to the PDF view.
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoIdA,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => ($startingStock - 15).' '.$unit,
            'stod_selisih' => '15 '.$unit,
        ]);
    }

    public function test_downloading_the_pdf_persists_live_stock_to_the_real_row_for_both_draft_and_waiting_documents(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        // Draft fase 2 (is_draft=1) — status-nya tetap 1 di bawah kap. Dibangun langsung sebagai
        // dokumen LAMA, sama alasannya dengan insertStockOpname() di atas.
        $stoIdDraft = $this->insertStockOpname($stock, $unit, $startingStock);
        $draft = StockOpname::find($stoIdDraft);
        $draft->is_draft = 1;
        $draft->save();
        $this->assertDatabaseHas('stock_opnames', ['sto_id' => $stoIdDraft, 'is_draft' => 1, 'status' => 1]);

        // Some OTHER opname moves the live stock before the draft's PDF is ever downloaded.
        $otherStoId = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 7);
        $this->approve($stock, $otherStoId, $startingStock - 7);
        $stock->refresh();
        $this->assertSame($startingStock - 7, $stock->ps_stock, 'sanity: the other approval must have moved the live stock');

        // Downloading the still-draft doc's PDF must persist the now-current live stock to its row.
        $this->get('/generateStockOpname/'.$stoIdDraft)->assertStatus(200);
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoIdDraft,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => ($startingStock - 7).' '.$unit,
        ]);

        // Submitting (leaving draft) then downloading again must keep persisting — status is
        // unchanged by submitStockOpname(), only is_draft flips.
        $this->post('/submitStockOpname', ['sto_id' => $stoIdDraft])->assertStatus(200);
        $this->assertDatabaseHas('stock_opnames', ['sto_id' => $stoIdDraft, 'is_draft' => 0, 'status' => 1]);

        $stoIdC = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 20);
        $this->approve($stock, $stoIdC, $startingStock - 20);
        $stock->refresh();

        $this->get('/generateStockOpname/'.$stoIdDraft)->assertStatus(200);
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoIdDraft,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => $stock->ps_stock.' '.$unit,
        ]);
    }

    public function test_downloading_the_pdf_for_an_already_decided_document_does_not_touch_its_frozen_row(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        $stoId = $this->insertStockOpname($stock, $unit, realQty: $startingStock);
        $this->approve($stock, $stoId, $startingStock);

        $frozenBefore = StockOpnameDetail::where('sto_id', $stoId)
            ->where('product_variant_id', $stock->product_variant_id)
            ->firstOrFail();

        // Some OTHER opname moves the live stock after this one was already decided.
        $otherStoId = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 5);
        $this->approve($stock, $otherStoId, $startingStock - 5);

        $this->get('/generateStockOpname/'.$stoId)->assertStatus(200);

        // The status==1 gate around refreshLiveSystemQty() must keep this frozen -- an
        // approved/rejected document's PDF is a historical record, not a live view.
        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoId,
            'product_variant_id' => $stock->product_variant_id,
            'stod_system' => $frozenBefore->stod_system,
            'stod_selisih' => $frozenBefore->stod_selisih,
        ]);
    }

    public function test_approving_freezes_the_true_system_stock_at_that_moment_not_the_creation_time_one(): void
    {
        $this->actingAsSuperAdminStaff();
        $stock = $this->pickFixtureStock();
        $unit = $this->unitShortName($stock);
        $startingStock = $stock->ps_stock;

        $stoIdA = $this->insertStockOpname($stock, $unit, realQty: $startingStock);
        $stoIdB = $this->insertStockOpname($stock, $unit, realQty: $startingStock - 15);
        $this->approve($stock, $stoIdB, $startingStock - 15);

        // A is approved with real_qty = $startingStock (the count taken before B's approval
        // moved anything) -- accStockOpname() re-fetches live stock right before overwriting, so
        // the true "before" value here is $startingStock - 15, NOT the stale creation-time
        // $startingStock baked into A's stod_system.
        $this->approve($stock, $stoIdA, $startingStock);

        $row = StockOpnameDetail::where('sto_id', $stoIdA)
            ->where('product_variant_id', $stock->product_variant_id)
            ->firstOrFail();

        $this->assertSame($startingStock - 15, $this->parseQty($row->stod_system, $unit), 'approval must freeze the TRUE system stock at approval time');
        $this->assertSame(15, $this->parseQty($row->stod_selisih, $unit), 'the frozen selisih must reflect the true discrepancy found at approval, not a stale one');

        $stock->refresh();
        $this->assertSame($startingStock, $stock->ps_stock, "A's approval must still overwrite with A's own real_qty, unaffected by this fix");
    }
}
