<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #53: the Stock Opname PDF (Backoffice/PDF/Opname.blade.php) must show three highlight
 * states per row, not the old binary yellow/none:
 *   - untouched (real-stock input left blank client-side, real qty just defaulted to system qty)
 *     -> no highlight
 *   - touched, no selisih (staff typed a value and it matches the system) -> green
 *   - touched, has selisih -> yellow (unchanged behaviour)
 *
 * See cdocs/testing/workflows/STOCK_OPNAME_FLOW.md — stod_system/stod_real/stod_selisih are pure
 * display strings never read by accStockOpname(), so stod_touched is purely a display flag too.
 */
class StockOpnamePdfHighlightTest extends TestCase
{
    use ActingAsStaff;

    private const YELLOW = 'background-color: #FFF9C4;';
    private const GREEN = 'background-color: #C8E6C9;';

    /**
     * Bikin varian sendiri, bukan memilih "baris pertama yang cocok" dari DB testing -- DB itu
     * membawa data multi-gudang asli, dan dua ProductStock hasil filter bisa jatuh pada varian
     * yang SAMA sehingga keyBy('product_variant_id') di bawah saling menimpa. Itu penyebab test
     * ini sudah merah di baseline sebelum rancang ulang 2026-08-27, tidak berhubungan dengannya.
     */
    private function makeVariant(): ProductVariant
    {
        $category = new Category();
        $category->category_name = 'Opname Highlight Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname Highlight Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([]);
        $product->unit_id = (int) DB::table('units')->where('status', 1)->value('unit_id');
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Default';
        $variant->product_variant_sku = 'HL-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return $variant;
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /**
     * stod_touched adalah mekanika dokumen LAMA -- dokumen versi baru tidak punya flag ini sama
     * sekali, karena "belum dihitung" sudah terwakili sol_counted_qty NULL (lihat
     * Tests\Workflow\StockOpnameV2LifecycleTest). Jadi dokumennya dibangun langsung sebagai
     * dokumen lama; /insertStockOpname sekarang membuat dokumen versi baru.
     */
    public function test_legacy_document_persists_stod_touched_per_row(): void
    {
        $this->actingAsSuperAdminStaff();

        $touched = $this->makeVariant();
        $untouched = $this->makeVariant();

        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'LG'.substr((string) microtime(true), -4);
        $sto->staff_id = $this->staffId();
        $sto->category_id = -1;
        $sto->status = 1;
        $sto->is_draft = 0;
        $sto->save();
        $this->assertTrue((bool) $sto->refresh()->is_old_version);

        foreach ([[$touched, 1], [$untouched, 0]] as [$variant, $flag]) {
            $d = new StockOpnameDetail();
            $d->sto_id = $sto->sto_id;
            $d->product_id = $variant->product_id;
            $d->product_variant_id = $variant->product_variant_id;
            $d->stod_system = '10 pcs';
            $d->stod_real = '10 pcs';
            $d->stod_selisih = '0 pcs';
            $d->stod_touched = $flag;
            $d->status = 1;
            $d->save();
        }

        // getDetail() (yang dipakai generateStockOpname() untuk memberi makan view PDF) harus
        // meneruskan flag-nya apa adanya.
        $detail = StockOpnameDetail::getDetail(['sto_id' => $sto->sto_id])->keyBy('product_variant_id');
        $this->assertSame(1, (int) $detail[$touched->product_variant_id]->stod_touched);
        $this->assertSame(0, (int) $detail[$untouched->product_variant_id]->stod_touched);
    }

    private function renderOpnameRow(array $item): string
    {
        return View::make('Backoffice.PDF.Opname', [
            'stockOpname' => ['sto_code' => 'SP0001', 'sto_date' => now()->toDateString(), 'sto_notes' => null],
            'staff_name' => ['staff_name' => 'Tester'],
            'status' => 'Disetujui',
            'detail' => [$item],
        ])->render();
    }

    public function test_untouched_row_gets_no_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-UNTOUCHED',
            'pr_name' => 'Produk Untouched',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '10 pcs',
            'stod_selisih' => '0 pcs',
            'stod_notes' => null,
            'stod_touched' => 0,
        ]);

        $this->assertStringNotContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }

    public function test_touched_row_with_no_selisih_gets_green_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-MATCH',
            'pr_name' => 'Produk Match',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '10 pcs',
            'stod_selisih' => '0 pcs',
            'stod_notes' => null,
            'stod_touched' => 1,
        ]);

        $this->assertStringContainsString(self::GREEN, $html);
        $this->assertStringNotContainsString(self::YELLOW, $html);
    }

    /**
     * Perbaikan 2026-08-27 (SP0071 baris MRHK1LM, dilaporkan dari PDF produksi): sistem 3 DOS,
     * real 8 DOS, selisih 5 DOS -- tapi stod_touched = 0, jadi baris dengan selisih paling perlu
     * ditindaklanjuti justru tampil polos, tidak bisa dibedakan dari baris yang memang tidak
     * pernah dihitung. Terjadi pada dokumen pra-GitHub #78: real-nya terlanjur tersimpan sebagai
     * angka fallback (= stok sistem saat itu) meski input dibiarkan kosong, lalu selama dokumen
     * masih berstatus Menunggu, refreshLiveSystemQty() menulis ulang stod_system/stod_selisih dari
     * stok live yang sudah bergerak. Selisih bukan nol sekarang selalu kuning, apa pun flag-nya.
     */
    public function test_untouched_row_with_selisih_still_gets_yellow_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'MRHK1LM',
            'pr_name' => 'MINYAK REM HIKARI MERAH',
            'product_variant_name' => '16 X 1 LITER',
            'stod_system' => '3 DOS, 0 pcs',
            'stod_real' => '8 DOS, 0 pcs',
            'stod_selisih' => '5 DOS, 0 pcs',
            'stod_notes' => null,
            'stod_touched' => 0,
        ]);

        $this->assertStringContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }

    /**
     * Batas yang tidak boleh ikut bergeser: baris yang benar-benar tidak dihitung tetap polos.
     * humanizeUntouchedForPdf() sudah menormalkan satuan "-" jadi real = sistem dan selisih 0,
     * jadi aturan "selisih != 0 -> kuning" tidak pernah menyentuh baris seperti ini.
     */
    public function test_untouched_row_with_zero_selisih_stays_unhighlighted(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-UNCOUNTED',
            'pr_name' => 'Produk Tidak Dihitung',
            'product_variant_name' => 'Default',
            'stod_system' => '12 DOS, 0 pcs',
            'stod_real' => '12 DOS, 0 pcs',
            'stod_selisih' => '0 DOS, 0 pcs',
            'stod_notes' => null,
            'stod_touched' => 0,
        ]);

        $this->assertStringNotContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }

    /**
     * Perbaikan 2026-08-27 (lanjutan): kolom Selisih yang DICETAK tidak boleh diambil dari string
     * tersimpan, karena tidak ada yang menjamin string itu konsisten dengan kolom Sistem/Real yang
     * dicetak di sebelahnya (stod_system bisa ditulis ulang belakangan oleh refreshLiveSystemQty()
     * sementara stod_selisih berasal dari penyimpanan lain). humanizeUntouchedForPdf() sekarang
     * selalu menghitung ulang (real - sistem) per satuan, jadi aritmetika satu baris cetakan benar
     * menurut definisi -- dan highlight-nya ikut benar karena blade membaca hasil pass itu.
     */
    public function test_printed_selisih_is_recomputed_from_the_printed_system_and_real_columns(): void
    {
        $rows = collect([(object) [
            'product_variant_sku' => 'SKU-STALE',
            'pr_name' => 'Produk Selisih Basi',
            'product_variant_name' => 'Default',
            'stod_system' => '3 DOS, 0 pcs',
            'stod_real' => '8 DOS, 0 pcs',
            // Sengaja bertentangan dengan Sistem/Real di atas -- inilah yang tidak boleh dipercaya.
            'stod_selisih' => '0 DOS, 0 pcs',
            'stod_notes' => null,
            'stod_touched' => 0,
        ]]);

        $humanize = new \ReflectionMethod(\App\Http\Controllers\StockController::class, 'humanizeUntouchedForPdf');
        $humanize->setAccessible(true);
        $humanize->invoke(new \App\Http\Controllers\StockController(), $rows, 'stod_real', 'stod_system', 'stod_selisih');

        $this->assertSame('5 DOS, 0 pcs', $rows[0]->stod_selisih, 'Selisih cetak harus 8 - 3, bukan string tersimpan');

        $html = $this->renderOpnameRow((array) $rows[0]);
        $this->assertStringContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }

    /**
     * SP0071 baris SHPWW5L: Stock Sistem tercetak "0 pcs, 0 DOS" sementara Stock Real "0 DOS,
     * 0 pcs" -- urutan satuannya terbalik antar kolom. Pengurangan harus dicocokkan per NAMA
     * satuan, bukan per posisi, atau selisih palsu akan muncul di dokumen yang sebenarnya cocok.
     */
    public function test_recomputed_selisih_matches_units_by_name_not_by_position(): void
    {
        $rows = collect([(object) [
            'product_variant_sku' => 'SHPWW5L',
            'pr_name' => 'PEGASUS SHAMPOO WASH & WAX',
            'product_variant_name' => '4 x 5 liter',
            'stod_system' => '7 pcs, 2 DOS',
            'stod_real' => '2 DOS, 7 pcs',
            'stod_selisih' => '0 DOS, 0 pcs',
            'stod_notes' => null,
            'stod_touched' => 1,
        ]]);

        $humanize = new \ReflectionMethod(\App\Http\Controllers\StockController::class, 'humanizeUntouchedForPdf');
        $humanize->setAccessible(true);
        $humanize->invoke(new \App\Http\Controllers\StockController(), $rows, 'stod_real', 'stod_system', 'stod_selisih');

        $this->assertSame('0 DOS, 0 pcs', $rows[0]->stod_selisih);

        $html = $this->renderOpnameRow((array) $rows[0]);
        $this->assertStringContainsString(self::GREEN, $html);
        $this->assertStringNotContainsString(self::YELLOW, $html);
    }

    public function test_touched_row_with_selisih_gets_yellow_highlight(): void
    {
        $html = $this->renderOpnameRow([
            'product_variant_sku' => 'SKU-DIFF',
            'pr_name' => 'Produk Diff',
            'product_variant_name' => 'Default',
            'stod_system' => '10 pcs',
            'stod_real' => '8 pcs',
            'stod_selisih' => '-2 pcs',
            'stod_notes' => null,
            'stod_touched' => 1,
        ]);

        $this->assertStringContainsString(self::YELLOW, $html);
        $this->assertStringNotContainsString(self::GREEN, $html);
    }
}
