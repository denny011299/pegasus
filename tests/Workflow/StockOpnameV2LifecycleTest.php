<?php

namespace Tests\Workflow;

use App\Models\Category;
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
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * Rancang ulang Stock Opname 2026-08-27 -- lapisan data + siklus hidup snapshot.
 *
 * Kebijakan yang dikunci di sini (keputusan PM):
 *   draft              -> tidak ada snapshot sama sekali
 *   publish            -> snapshot identitas, TANPA stok sistem
 *   menunggu           -> stok sistem live, tidak menulis apa pun
 *   disetujui/ditolak  -> stok sistem dibekukan, dokumen berhenti bergerak
 *
 * Fixture dibangun fresh lewat Eloquent, bukan memilih "baris pertama yang cocok" -- DB testing
 * membawa data multi-gudang asli, lihat catatan drift di cdocs/testing/.
 */
class StockOpnameV2LifecycleTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /** @return array{0: ProductVariant, 1: ProductStock, 2: ProductStock, 3: Unit, 4: Unit} */
    private function makeFixture(int $dosStock = 12, int $pcsStock = 42, int $warehouseId = 1): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $units->count(), 'fixture butuh minimal 2 satuan aktif');
        [$dosUnit, $pcsUnit] = $units->all();

        $category = new Category();
        $category->category_name = 'Opname V2 Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname V2 Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $product->unit_id = $dosUnit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Varian Uji';
        $variant->product_variant_sku = 'V2-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

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

    private function makeDocument(bool $isDraft): StockOpname
    {
        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'V2'.substr((string) microtime(true), -4);
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

    // ---------------------------------------------------------------- pemisah versi

    /**
     * Default kolomnya SENGAJA true supaya migrasinya murni ADD COLUMN: setiap dokumen yang sudah
     * ada otomatis benar tanpa backfill dan tanpa peluang salah melabeli data historis.
     */
    public function test_existing_documents_default_to_old_version(): void
    {
        $id = DB::table('stock_opnames')->insertGetId([
            'sto_date' => now()->toDateString(),
            'sto_code' => 'V2OLD',
            'staff_id' => $this->staffId(),
            'category_id' => -1,
            'status' => 1,
            'is_draft' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(1, (int) DB::table('stock_opnames')->where('sto_id', $id)->value('is_old_version'));
    }

    // ---------------------------------------------------------------- siklus hidup snapshot

    public function test_draft_holds_no_snapshot_at_all(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture();
        $sto = $this->makeDocument(isDraft: true);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);

        (new OpnameLifecycle())->publish($sto);

        $sto->refresh();
        $this->assertNull($sto->sto_staff_name, 'draft tidak boleh membekukan nama penanggung jawab');
        foreach (StockOpnameLine::getLines($sto->sto_id) as $line) {
            $this->assertNull($line->sol_product_name, 'draft tidak boleh punya snapshot identitas');
            $this->assertNull($line->sol_system_qty_final);
        }
    }

    public function test_publish_snapshots_identity_but_never_system_stock(): void
    {
        [$variant, $dos, $pcs, $dosUnit] = $this->makeFixture();
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);

        (new OpnameLifecycle())->publish($sto);

        $sto->refresh();
        $this->assertNotNull($sto->sto_staff_name);

        $line = StockOpnameLine::getLines($sto->sto_id)->firstWhere('unit_id', $dos->unit_id);
        $this->assertNotNull($line->sol_product_name);
        $this->assertSame($variant->product_variant_sku, $line->sol_variant_sku);
        $this->assertSame($dosUnit->unit_short_name, $line->sol_unit_short_name);
        // Inti keputusan PM: membekukan stok sistem sedini ini persis mekanisme yang melahirkan #78.
        $this->assertNull($line->sol_system_qty_final, 'publish TIDAK boleh membekukan stok sistem');
    }

    /**
     * Alur UI sekarang membuat dokumen LANGSUNG non-draft (CreateStockOpname.blade.php cuma
     * memasang .btn-save, yang mengirim is_draft = 0 dan tidak pernah lewat /submitStockOpname).
     * publish() karena itu dipicu keadaan, bukan tombol, dan harus aman dipanggil berkali-kali.
     */
    public function test_publish_is_idempotent_and_never_refreshes_frozen_names(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture();
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new OpnameLifecycle();
        $lifecycle->publish($sto);
        $frozen = StockOpnameLine::getLines($sto->sto_id)->firstWhere('unit_id', $dos->unit_id)->sol_product_name;

        // Katalog berubah setelah dokumen diajukan.
        Product::where('product_id', $variant->product_id)->update(['product_name' => 'NAMA BARU SETELAH PUBLISH']);
        $lifecycle->publish($sto->refresh());

        $this->assertSame(
            $frozen,
            StockOpnameLine::getLines($sto->sto_id)->firstWhere('unit_id', $dos->unit_id)->sol_product_name,
            'nama yang sudah beku tidak boleh ikut berubah saat publish dipanggil ulang'
        );
    }

    // ---------------------------------------------------------------- baca: menunggu vs diputuskan

    public function test_pending_document_reads_system_stock_live_and_writes_nothing(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture(dosStock: 12);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);
        (new OpnameLifecycle())->publish($sto);

        $reader = new OpnameLineReader();
        $before = $reader->read($sto)->first();
        $this->assertSame(12, $before['units'][0]['system']);
        $this->assertSame(-4, $before['units'][0]['selisih']);

        // Stok bergerak (penjualan wajar) selagi dokumen masih menunggu.
        ProductStock::where('ps_id', $dos->ps_id)->update(['ps_stock' => 3]);

        $after = $reader->read($sto->refresh())->first();
        $this->assertSame(3, $after['units'][0]['system'], 'dokumen menunggu harus ikut stok live');
        $this->assertSame(5, $after['units'][0]['selisih']);

        // ...tapi membaca TIDAK BOLEH menulis apa pun (refreshLiveSystemQty() lama justru menulis).
        $this->assertNull(
            StockOpnameLine::getLines($sto->sto_id)->firstWhere('unit_id', $dos->unit_id)->sol_system_qty_final,
            'membaca dokumen menunggu tidak boleh membekukan apa pun'
        );
    }

    public function test_decided_document_is_frozen_and_ignores_later_stock_movement(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture(dosStock: 12);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new OpnameLifecycle();
        $lifecycle->publish($sto);

        // Urutan wajib: bekukan dulu, baru stok ditimpa hasil hitung.
        $lifecycle->freezeSystemQty($sto);
        ProductStock::where('ps_id', $dos->ps_id)->update(['ps_stock' => 8]);
        $sto->status = 2;
        $sto->save();
        $lifecycle->stampDecision($sto, $this->staffId());

        $reader = new OpnameLineReader();
        $row = $reader->read($sto->refresh())->first();
        $this->assertSame(12, $row['units'][0]['system'], 'stok sistem harus yang dibekukan saat keputusan');
        $this->assertSame(-4, $row['units'][0]['selisih'], 'selisih = yang benar-benar dikoreksi');

        // Stok terus bergerak sesudahnya -- dokumen tidak boleh ikut bergeser.
        ProductStock::where('ps_id', $dos->ps_id)->update(['ps_stock' => 999]);
        $this->assertSame(12, $reader->read($sto->refresh())->first()['units'][0]['system']);

        $sto->refresh();
        $this->assertNotNull($sto->sto_acc_name);
        $this->assertNotNull($sto->sto_decided_at);
    }

    /**
     * Permintaan eksplisit PM: dokumen yang sudah diputuskan adalah bukti, jadi tidak boleh rusak
     * karena master data berubah/dihapus. Relasi sengaja longgar (tanpa foreign key) dan yang
     * dipakai mencetak adalah snapshot teks -- satuan tetap terbaca manusia walau unit-nya hilang.
     */
    public function test_deleted_unit_and_product_do_not_break_a_decided_document(): void
    {
        [$variant, $dos, $pcs, $dosUnit] = $this->makeFixture();
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new OpnameLifecycle();
        $lifecycle->publish($sto);
        $lifecycle->freezeSystemQty($sto);
        $sto->status = 3; // Ditolak pun ikut dibekukan.
        $sto->save();
        $lifecycle->stampDecision($sto, $this->staffId());

        $expectedUnit = $dosUnit->unit_short_name;
        $expectedProduct = StockOpnameLine::getLines($sto->sto_id)->first()->sol_product_name;

        // Master data lenyap sesudah dokumen final.
        Unit::where('unit_id', $dosUnit->unit_id)->delete();
        ProductVariant::where('product_variant_id', $variant->product_variant_id)->delete();
        Product::where('product_id', $variant->product_id)->delete();
        ProductStock::where('product_variant_id', $variant->product_variant_id)->delete();

        $row = (new OpnameLineReader())->read($sto->refresh())->first();
        $this->assertSame($expectedProduct, $row['product_name']);
        $this->assertSame($expectedUnit, $row['units'][0]['unit'], 'satuan harus tetap terbaca manusia');
        $this->assertSame(8, $row['units'][0]['counted']);
    }

    // ---------------------------------------------------------------- selisih & highlight

    public function test_uncounted_unit_is_null_and_never_invents_a_selisih(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture(dosStock: 12, pcsStock: 42);
        $sto = $this->makeDocument(isDraft: false);
        // Kedua satuan dibiarkan kosong: tidak ada satu pun yang dihitung.
        $this->addLines($sto, $variant, [$dos->unit_id => null, $pcs->unit_id => null]);
        (new OpnameLifecycle())->publish($sto);

        $row = (new OpnameLineReader())->read($sto)->first();

        // Data mentahnya tetap NULL apa adanya -- inilah yang dipakai menentukan highlight dan
        // yang dikirim balik ke halaman edit sebagai real_qty (biar input tetap tampil kosong).
        $this->assertNull($row['units'][0]['counted']);
        $this->assertNull($row['units'][0]['selisih'], 'satuan tak dihitung tidak boleh punya selisih');
        $this->assertFalse($row['has_selisih']);
        $this->assertNull($row['highlight'], 'baris yang tidak pernah dihitung tidak di-highlight');

        // TAPI yang tercetak di kertas bukan tanda hubung telanjang (GitHub #78 follow-up: "-"
        // terbaca sebagai data hilang di atas kertas) -- tercetak "cocok dengan sistem" persis
        // seperti dokumen lama sudah lakukan lewat humanizeUntouchedForPdf().
        $this->assertStringNotContainsString('-', $row['real_text'], 'yang DICETAK harus dihumanisasi, bukan tanda hubung telanjang');
        $dosUnitName = $row['units'][0]['unit'];
        $pcsUnitName = $row['units'][1]['unit'];
        $this->assertSame('12 '.$dosUnitName.', 42 '.$pcsUnitName, $row['real_text']);
        $this->assertSame('0 '.$dosUnitName.', 0 '.$pcsUnitName, $row['selisih_text']);
    }

    /**
     * Bentuk persis SP0071 baris MRHK1LM: sistem 3, real 8. Di format lama warnanya ikut flag
     * terpisah dan bisa bertentangan dengan angkanya. Di sini warna dan angka berasal dari
     * sumber yang sama, jadi bertentangan tidak mungkin terjadi.
     */
    public function test_selisih_row_is_yellow_and_matched_row_is_green(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture(dosStock: 3, pcsStock: 0);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => 0]);
        (new OpnameLifecycle())->publish($sto);

        $reader = new OpnameLineReader();
        $row = $reader->read($sto)->first();
        $this->assertSame(5, $row['units'][0]['selisih']);
        $this->assertTrue($row['has_selisih']);
        $this->assertSame('yellow', $row['highlight']);

        // Dihitung dan ternyata cocok -> hijau.
        StockOpnameLine::where('sto_id', $sto->sto_id)->where('unit_id', $dos->unit_id)
            ->update(['sol_counted_qty' => 3]);
        $this->assertSame('green', $reader->read($sto)->first()['highlight']);
    }

    // ---------------------------------------------------------------- laporan

    /**
     * Laporan Selisih Opname (ReportController) dulu hanya men-JOIN stock_opname_details, jadi
     * begitu dokumen baru berhenti menulis ke tabel itu, dokumen baru akan HILANG diam-diam dari
     * laporan -- laporan tetap tampil "berhasil", cuma isinya kurang. Cutover-nya harus ikut
     * memindahkan pembacanya, bukan cuma penulisnya.
     */
    public function test_selisih_report_includes_new_version_documents(): void
    {
        $this->actingAsSuperAdminStaff();

        [$variant, $dos, $pcs] = $this->makeFixture(dosStock: 3, pcsStock: 0);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 8, $pcs->unit_id => null]);
        (new OpnameLifecycle())->publish($sto);

        $reflection = new \ReflectionMethod(\App\Http\Controllers\ReportController::class, 'selisihRowsFromOpnameLines');
        $reflection->setAccessible(true);
        $rows = $reflection->invoke(new \App\Http\Controllers\ReportController(), null, null, null);

        $mine = collect($rows)->firstWhere('kode', $sto->sto_code);
        $this->assertNotNull($mine, 'dokumen versi baru harus ikut muncul di laporan selisih');
        $this->assertSame('produk', $mine->sumber);
        $this->assertStringContainsString('5', $mine->selisih_text, 'selisih 8 - 3 harus ikut terbawa ke laporan');
    }

    // ---------------------------------------------------------------- gulung satuan (rollUpUnits)

    /** @return array{0: ProductVariant, 1: ProductStock, 2: ProductStock, 3: Unit, 4: Unit} */
    private function makeFixtureWithLadder(int $dosStock = 12, int $pcsStock = 42, int $ratio = 12, int $warehouseId = 1): array
    {
        [$variant, $dos, $pcs, $dosUnit, $pcsUnit] = $this->makeFixture($dosStock, $pcsStock, $warehouseId);

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = $dosUnit->unit_id; // besar
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $pcsUnit->unit_id; // kecil
        $relation->pr_unit_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        return [$variant, $dos, $pcs, $dosUnit, $pcsUnit];
    }

    /**
     * Contoh persis dari PM: 1 DOS = 12 pcs, isi 30 pcs -> tersimpan 2 DOS + 6 pcs. DOS live
     * sengaja 0 di sini -- kasus DOS yang SUDAH punya stok live dipegang terpisah oleh
     * test_roll_up_folds_existing_live_stock_of_an_untouched_unit_instead_of_erasing_it().
     */
    public function test_roll_up_converts_a_filled_small_unit_into_an_untouched_big_one(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$pcs->unit_id]->sol_counted_qty);
        $this->assertSame(2, (int) $lines[$dos->unit_id]->sol_counted_qty, 'DOS harus ikut terisi otomatis dari kelebihan pcs');
    }

    /** "Baik draft maupun langsung menunggu" -- keputusan PM eksplisit, keduanya harus tergulung. */
    public function test_roll_up_applies_equally_to_draft_and_pending_documents(): void
    {
        foreach ([true, false] as $isDraft) {
            [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
            $sto = $this->makeDocument(isDraft: $isDraft);
            $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

            (new OpnameLifecycle())->rollUpUnits($sto);

            $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
            $this->assertSame(2, (int) $lines[$dos->unit_id]->sol_counted_qty, 'is_draft='.($isDraft ? '1' : '0'));
            $this->assertSame(6, (int) $lines[$pcs->unit_id]->sol_counted_qty, 'is_draft='.($isDraft ? '1' : '0'));
        }
    }

    /**
     * Setara GitHub #78: satuan besar yang diisi (walau 0) tidak boleh membuat satuan kecil yang
     * tidak pernah disentuh ikut "disimpulkan" -- itu mengarang data yang tidak pernah diperiksa.
     */
    public function test_roll_up_never_infers_a_smaller_unit_from_a_bigger_ones_entry(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder();
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$dos->unit_id => 0, $pcs->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(0, (int) $lines[$dos->unit_id]->sol_counted_qty);
        $this->assertNull($lines[$pcs->unit_id]->sol_counted_qty, 'pcs tidak boleh ikut jadi 0 hanya karena DOS diisi 0');
    }

    /** Menjalankan roll-up dua kali pada dokumen yang sama tidak boleh mengubah apa pun lagi. */
    public function test_roll_up_is_idempotent_across_repeated_saves(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder();
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

        $lifecycle = new OpnameLifecycle();
        $lifecycle->rollUpUnits($sto);
        $after1 = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id')
            ->map(fn ($l) => $l->sol_counted_qty)->all();

        $lifecycle->rollUpUnits($sto);
        $after2 = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id')
            ->map(fn ($l) => $l->sol_counted_qty)->all();

        $this->assertSame($after1, $after2);
    }

    /** Produk tanpa relasi satuan sama sekali harus dibiarkan apa adanya -- tidak ada yang digulung. */
    public function test_roll_up_leaves_a_product_with_no_unit_ladder_untouched(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture(); // tanpa ProductRelation
        $sto = $this->makeDocument(isDraft: false);
        $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$pcs->unit_id]->sol_counted_qty);
        $this->assertNull($lines[$dos->unit_id]->sol_counted_qty);
    }

    /**
     * Bug dilaporkan user (multi-gudang, 2026-08-31): stok live SUDAH kanonik (84 DOS + 0 Piece,
     * 1 DOS = 12 Piece). Staf isi HANYA Piece = 1000, DOS dibiarkan kosong. Sebelum perbaikan ini,
     * rollUpUnits() menggulung 1000 pcs SENDIRIAN (buta terhadap 84 DOS yang sudah ada) jadi
     * cuma 83 DOS + 4 Piece -- 84 DOS lama lenyap diam-diam, dan angka rekaan itu ikut tertulis ke
     * ps_stock saat ACC karena tidak lagi NULL. Perbaikannya melipat stok live yang sudah ada di
     * DOS (satuan yang tidak disentuh) ke dalam carry gulungan.
     */
    public function test_roll_up_folds_existing_live_stock_of_an_untouched_unit_instead_of_erasing_it(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 84, pcsStock: 0, ratio: 12);
        $sto = $this->makeDocument(isDraft: false);
        $sto->warehouse_id = 1;
        $sto->save();
        $this->addLines($sto, $variant, [$pcs->unit_id => 1000, $dos->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(4, (int) $lines[$pcs->unit_id]->sol_counted_qty);
        $this->assertSame(167, (int) $lines[$dos->unit_id]->sol_counted_qty, '84 DOS lama harus ikut terbawa, bukan digantikan angka dari Piece saja');
    }

    /** rollUpUnits() harus keluar lebih dulu untuk dokumen lama, tidak boleh menyentuh apa pun. */
    public function test_roll_up_does_nothing_on_a_legacy_document(): void
    {
        $sto = $this->makeDocument(isDraft: false);
        $sto->is_old_version = true;
        $sto->save();

        (new OpnameLifecycle())->rollUpUnits($sto);
        $this->assertSame(0, StockOpnameLine::where('sto_id', $sto->sto_id)->count());
    }

    /**
     * Keputusan user 2026-09-02: skema multi-gudang membatasi gudang eceran untuk cuma
     * menghitung satuan eceran-nya sendiri (retail_unit) -- menggulung ke satuan atas di sana
     * bertabrakan langsung dengan aturan itu, jadi gudang eceran TIDAK digulung sama sekali,
     * beda dari gudang utama yang tetap tergulung seperti biasa (lihat test-test di atas).
     */
    public function test_roll_up_skips_a_retail_warehouse_document_entirely(): void
    {
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId();

        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0, pcsStock: 42, ratio: 12, warehouseId: $retailWarehouseId);
        $sto = $this->makeDocument(isDraft: false);
        $sto->warehouse_id = $retailWarehouseId;
        $sto->save();
        $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$pcs->unit_id]->sol_counted_qty, 'pcs harus tetap apa adanya, tidak digulung');
        $this->assertNull($lines[$dos->unit_id]->sol_counted_qty, 'DOS tidak boleh ikut terisi otomatis di gudang eceran');
    }

    /** Dokumen tanpa gudang sama sekali (warehouse_id null) tetap digulung seperti sebelum multi-gudang ada. */
    public function test_roll_up_still_applies_when_document_has_no_warehouse_pinned(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
        $sto = $this->makeDocument(isDraft: false);
        $this->assertNull($sto->warehouse_id);
        $this->addLines($sto, $variant, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new OpnameLifecycle())->rollUpUnits($sto);

        $lines = StockOpnameLine::getLines($sto->sto_id)->keyBy('unit_id');
        $this->assertSame(2, (int) $lines[$dos->unit_id]->sol_counted_qty);
        $this->assertSame(6, (int) $lines[$pcs->unit_id]->sol_counted_qty);
    }

    // ---------------------------------------------------------------- identitas baris

    /**
     * Bug alur lama: updateStockOpname() cuma memperbarui header dan JS tidak pernah mengirim
     * stod_id, jadi tiap simpan menyisipkan ULANG semua baris. Unique index membuatnya mustahil.
     */
    public function test_resaving_a_line_updates_it_instead_of_duplicating(): void
    {
        [$variant, $dos, $pcs] = $this->makeFixture();
        $sto = $this->makeDocument(isDraft: false);

        $this->addLines($sto, $variant, [$dos->unit_id => 8]);
        $this->addLines($sto, $variant, [$dos->unit_id => 9]);
        $this->addLines($sto, $variant, [$dos->unit_id => 10]);

        $lines = StockOpnameLine::getLines($sto->sto_id);
        $this->assertCount(1, $lines, 'menyimpan ulang tidak boleh menggandakan baris');
        $this->assertSame(10, (int) $lines->first()->sol_counted_qty);
    }
}
