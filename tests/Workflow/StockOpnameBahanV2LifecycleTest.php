<?php

namespace Tests\Workflow;

use App\Models\StockOpnameBahan;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Support\StockOpname\BahanOpnameLifecycle;
use App\Support\StockOpname\BahanOpnameLineReader;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\Support\ResolvesTestWarehouses;
use Tests\TestCase;

/**
 * Kembaran persis Tests\Workflow\StockOpnameV2LifecycleTest, untuk Stock Opname BAHAN (Supplies).
 * Kebijakan siklus hidupnya IDENTIK (keputusan PM yang sama berlaku untuk kedua modul):
 *   draft              -> tidak ada snapshot sama sekali
 *   publish            -> snapshot identitas, TANPA stok sistem
 *   menunggu           -> stok sistem live, tidak menulis apa pun
 *   disetujui/ditolak  -> stok sistem dibekukan, dokumen berhenti bergerak
 *
 * Beda dari kembarannya: identitas Supplies cuma nama (tidak ada varian/SKU), dan fixture-nya
 * dibangun via Supplies/SuppliesStock bukan Product/ProductVariant/ProductStock.
 */
class StockOpnameBahanV2LifecycleTest extends TestCase
{
    use ActingAsStaff;
    use ResolvesTestWarehouses;

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    /** @return array{0: Supplies, 1: SuppliesStock, 2: SuppliesStock, 3: Unit, 4: Unit} */
    private function makeFixture(int $dosStock = 12, int $pcsStock = 42, int $warehouseId = 1): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $units->count(), 'fixture butuh minimal 2 satuan aktif');
        [$dosUnit, $pcsUnit] = $units->all();

        $supplies = new Supplies();
        $supplies->supplies_name = 'Opname Bahan V2 Test Ingredient '.uniqid();
        $supplies->supplies_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $supplies->supplies_default_unit = $dosUnit->unit_id;
        $supplies->status = 1;
        $supplies->save();

        $stocks = [];
        foreach ([[$dosUnit, $dosStock], [$pcsUnit, $pcsStock]] as [$unit, $qty]) {
            $s = new SuppliesStock();
            $s->supplies_id = $supplies->supplies_id;
            $s->unit_id = $unit->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ss_stock = $qty;
            $s->status = 1;
            $s->save();
            $stocks[] = $s;
        }

        return [$supplies, $stocks[0], $stocks[1], $dosUnit, $pcsUnit];
    }

    private function makeDocument(bool $isDraft): StockOpnameBahan
    {
        $stob = new StockOpnameBahan();
        $stob->stob_date = now()->toDateString();
        $stob->stob_code = 'VB'.substr((string) microtime(true), -4);
        $stob->staff_id = $this->staffId();
        $stob->status = BahanOpnameLineReader::STATUS_MENUNGGU;
        $stob->is_draft = $isDraft;
        $stob->is_old_version = false;
        $stob->save();

        return $stob;
    }

    /** @param array<int, int|null> $countedByUnitId */
    private function addLines(StockOpnameBahan $stob, Supplies $supplies, array $countedByUnitId): void
    {
        foreach ($countedByUnitId as $unitId => $counted) {
            StockOpnameBahanLine::upsertLine([
                'stob_id' => $stob->stob_id,
                'supplies_id' => $supplies->supplies_id,
                'unit_id' => $unitId,
                'sobl_counted_qty' => $counted,
                'sobl_notes' => null,
            ]);
        }
    }

    // ---------------------------------------------------------------- pemisah versi

    public function test_existing_documents_default_to_old_version(): void
    {
        $id = DB::table('stock_opname_bahans')->insertGetId([
            'stob_date' => now()->toDateString(),
            'stob_code' => 'VBOLD',
            'staff_id' => $this->staffId(),
            'status' => 1,
            'is_draft' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(1, (int) DB::table('stock_opname_bahans')->where('stob_id', $id)->value('is_old_version'));
    }

    // ---------------------------------------------------------------- siklus hidup snapshot

    public function test_draft_holds_no_snapshot_at_all(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: true);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        (new BahanOpnameLifecycle())->publish($stob);

        $stob->refresh();
        $this->assertNull($stob->stob_staff_name, 'draft tidak boleh membekukan nama penanggung jawab');
        foreach (StockOpnameBahanLine::getLines($stob->stob_id) as $line) {
            $this->assertNull($line->sobl_supplies_name, 'draft tidak boleh punya snapshot identitas');
            $this->assertNull($line->sobl_system_qty_final);
        }
    }

    public function test_publish_snapshots_identity_but_never_system_stock(): void
    {
        [$supplies, $dos, $pcs, $dosUnit] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        (new BahanOpnameLifecycle())->publish($stob);

        $stob->refresh();
        $this->assertNotNull($stob->stob_staff_name);

        $line = StockOpnameBahanLine::getLines($stob->stob_id)->firstWhere('unit_id', $dos->unit_id);
        $this->assertNotNull($line->sobl_supplies_name);
        $this->assertSame($dosUnit->unit_short_name, $line->sobl_unit_short_name);
        $this->assertNull($line->sobl_system_qty_final, 'publish TIDAK boleh membekukan stok sistem');
    }

    public function test_publish_is_idempotent_and_never_refreshes_frozen_names(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new BahanOpnameLifecycle();
        $lifecycle->publish($stob);
        $frozen = StockOpnameBahanLine::getLines($stob->stob_id)->firstWhere('unit_id', $dos->unit_id)->sobl_supplies_name;

        Supplies::where('supplies_id', $supplies->supplies_id)->update(['supplies_name' => 'NAMA BARU SETELAH PUBLISH']);
        $lifecycle->publish($stob->refresh());

        $this->assertSame(
            $frozen,
            StockOpnameBahanLine::getLines($stob->stob_id)->firstWhere('unit_id', $dos->unit_id)->sobl_supplies_name,
            'nama yang sudah beku tidak boleh ikut berubah saat publish dipanggil ulang'
        );
    }

    // ---------------------------------------------------------------- baca: menunggu vs diputuskan

    public function test_pending_document_reads_system_stock_live_and_writes_nothing(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture(dosStock: 12);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);
        (new BahanOpnameLifecycle())->publish($stob);

        $reader = new BahanOpnameLineReader();
        $before = $reader->read($stob)->first();
        $this->assertSame(12, $before['units'][0]['system']);
        $this->assertSame(-4, $before['units'][0]['selisih']);

        SuppliesStock::where('ss_id', $dos->ss_id)->update(['ss_stock' => 3]);

        $after = $reader->read($stob->refresh())->first();
        $this->assertSame(3, $after['units'][0]['system'], 'dokumen menunggu harus ikut stok live');
        $this->assertSame(5, $after['units'][0]['selisih']);

        $this->assertNull(
            StockOpnameBahanLine::getLines($stob->stob_id)->firstWhere('unit_id', $dos->unit_id)->sobl_system_qty_final,
            'membaca dokumen menunggu tidak boleh membekukan apa pun'
        );
    }

    public function test_decided_document_is_frozen_and_ignores_later_stock_movement(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture(dosStock: 12);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new BahanOpnameLifecycle();
        $lifecycle->publish($stob);

        $lifecycle->freezeSystemQty($stob);
        SuppliesStock::where('ss_id', $dos->ss_id)->update(['ss_stock' => 8]);
        $stob->status = 2;
        $stob->save();
        $lifecycle->stampDecision($stob, $this->staffId());

        $reader = new BahanOpnameLineReader();
        $row = $reader->read($stob->refresh())->first();
        $this->assertSame(12, $row['units'][0]['system'], 'stok sistem harus yang dibekukan saat keputusan');
        $this->assertSame(-4, $row['units'][0]['selisih']);

        SuppliesStock::where('ss_id', $dos->ss_id)->update(['ss_stock' => 999]);
        $this->assertSame(12, $reader->read($stob->refresh())->first()['units'][0]['system']);

        $stob->refresh();
        $this->assertNotNull($stob->stob_acc_name);
        $this->assertNotNull($stob->stob_decided_at);
    }

    public function test_deleted_unit_and_supplies_do_not_break_a_decided_document(): void
    {
        [$supplies, $dos, $pcs, $dosUnit] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $lifecycle = new BahanOpnameLifecycle();
        $lifecycle->publish($stob);
        $lifecycle->freezeSystemQty($stob);
        $stob->status = 3; // Ditolak pun ikut dibekukan.
        $stob->save();
        $lifecycle->stampDecision($stob, $this->staffId());

        $expectedUnit = $dosUnit->unit_short_name;
        $expectedName = StockOpnameBahanLine::getLines($stob->stob_id)->first()->sobl_supplies_name;

        Unit::where('unit_id', $dosUnit->unit_id)->delete();
        Supplies::where('supplies_id', $supplies->supplies_id)->delete();
        SuppliesStock::where('supplies_id', $supplies->supplies_id)->delete();

        $row = (new BahanOpnameLineReader())->read($stob->refresh())->first();
        $this->assertSame($expectedName, $row['supplies_name']);
        $this->assertSame($expectedUnit, $row['units'][0]['unit'], 'satuan harus tetap terbaca manusia');
        $this->assertSame(8, $row['units'][0]['counted']);
    }

    // ---------------------------------------------------------------- selisih & highlight

    public function test_uncounted_unit_is_null_and_never_invents_a_selisih(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture(dosStock: 12, pcsStock: 42);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => null, $pcs->unit_id => null]);
        (new BahanOpnameLifecycle())->publish($stob);

        $row = (new BahanOpnameLineReader())->read($stob)->first();

        $this->assertNull($row['units'][0]['counted']);
        $this->assertNull($row['units'][0]['selisih']);
        $this->assertFalse($row['has_selisih']);
        $this->assertNull($row['highlight']);

        // GitHub #78 follow-up: yang DICETAK bukan tanda hubung telanjang, tapi "cocok dengan
        // sistem" -- lihat OpnameLineReader::text() (kembarannya di BahanOpnameLineReader).
        $this->assertStringNotContainsString('-', $row['real_text']);
        $dosUnitName = $row['units'][0]['unit'];
        $pcsUnitName = $row['units'][1]['unit'];
        $this->assertSame('12 '.$dosUnitName.', 42 '.$pcsUnitName, $row['real_text']);
        $this->assertSame('0 '.$dosUnitName.', 0 '.$pcsUnitName, $row['selisih_text']);
    }

    public function test_selisih_row_is_yellow_and_matched_row_is_green(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture(dosStock: 3, pcsStock: 0);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => 0]);
        (new BahanOpnameLifecycle())->publish($stob);

        $reader = new BahanOpnameLineReader();
        $row = $reader->read($stob)->first();
        $this->assertSame(5, $row['units'][0]['selisih']);
        $this->assertTrue($row['has_selisih']);
        $this->assertSame('yellow', $row['highlight']);

        StockOpnameBahanLine::where('stob_id', $stob->stob_id)->where('unit_id', $dos->unit_id)
            ->update(['sobl_counted_qty' => 3]);
        $this->assertSame('green', $reader->read($stob)->first()['highlight']);
    }

    // ---------------------------------------------------------------- identitas baris

    public function test_resaving_a_line_updates_it_instead_of_duplicating(): void
    {
        [$supplies, $dos] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: false);

        $this->addLines($stob, $supplies, [$dos->unit_id => 8]);
        $this->addLines($stob, $supplies, [$dos->unit_id => 9]);
        $this->addLines($stob, $supplies, [$dos->unit_id => 10]);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id);
        $this->assertCount(1, $lines, 'menyimpan ulang tidak boleh menggandakan baris');
        $this->assertSame(10, (int) $lines->first()->sobl_counted_qty);
    }

    /**
     * Bug dilaporkan user (2026-09-02): draft tidak pernah lewat publish() (lihat
     * BahanOpnameLifecycle::publish()'s guard), jadi sobl_unit_short_name masih NULL selama masih
     * draft. legacyItems() DULU jatuh ke fallback literal "unit#12" untuk label satuan yang
     * dicetak ke stobd_real/stobd_system -- padahal 'units' (katalog) di payload yang SAMA tetap
     * memuat nama asli ("pcs"). CreateStockOpnameSupplies.js (seedSavedValuesFromItems()) mem-
     * PARSE ULANG stobd_real lalu mencocokkan namanya ke katalog itu untuk mengisi ulang form saat
     * draft dibuka lagi -- "unit#12" vs "pcs" tidak pernah cocok, jadi angka yang barusan diinput
     * staf hilang dari tampilan (walau tetap tersimpan benar di DB). Simulasikan persis logika
     * JS itu di sini supaya regresi ini tertangkap tanpa perlu test browser.
     */
    public function test_draft_legacy_items_unit_label_matches_the_catalog_name_it_will_be_matched_against(): void
    {
        [$supplies, $dos, $pcs, $dosUnit, $pcsUnit] = $this->makeFixture();
        $stob = $this->makeDocument(isDraft: true);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);

        $items = (new BahanOpnameLineReader())->legacyItems($stob);
        $this->assertCount(1, $items);
        $item = $items[0];

        // Baris di stobd_real cuma boleh mencetak nama satuan katalog asli -- tidak pernah lagi
        // fallback "unit#N" selama unit-nya sendiri masih ada di database.
        $this->assertStringContainsString('8 '.$dosUnit->unit_short_name, $item['stobd_real']);
        $this->assertStringNotContainsString('unit#', $item['stobd_real']);

        // Simulasi persis seedSavedValuesFromItems() (CreateStockOpnameSupplies.js): urai
        // stobd_real jadi peta nama->qty, lalu cocokkan tiap unit katalog ke peta itu.
        $realMap = [];
        foreach (explode(', ', $item['stobd_real']) as $part) {
            [$qty, $name] = array_pad(explode(' ', trim($part), 2), 2, '');
            $realMap[$name] = $qty;
        }

        $restored = [];
        foreach ($item['units'] as $u) {
            if (array_key_exists($u['unit_short_name'], $realMap) && $realMap[$u['unit_short_name']] !== '-') {
                $restored[$u['unit_id']] = (int) $realMap[$u['unit_short_name']];
            }
        }

        $this->assertSame(8, $restored[$dosUnit->unit_id] ?? null, 'nilai yang sudah diinput harus berhasil dipulihkan ke form saat draft dibuka lagi');
        $this->assertArrayNotHasKey($pcsUnit->unit_id, $restored, 'satuan yang belum diisi tidak boleh ikut "dipulihkan"');
    }

    // ---------------------------------------------------------------- laporan

    public function test_selisih_report_includes_new_version_bahan_documents(): void
    {
        $this->actingAsSuperAdminStaff();

        [$supplies, $dos, $pcs] = $this->makeFixture(dosStock: 3, pcsStock: 0);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 8, $pcs->unit_id => null]);
        (new BahanOpnameLifecycle())->publish($stob);

        $reflection = new \ReflectionMethod(\App\Http\Controllers\ReportController::class, 'selisihRowsFromOpnameBahanLines');
        $reflection->setAccessible(true);
        $rows = $reflection->invoke(new \App\Http\Controllers\ReportController(), null, null, null);

        $mine = collect($rows)->firstWhere('kode', $stob->stob_code);
        $this->assertNotNull($mine, 'dokumen Bahan versi baru harus ikut muncul di laporan selisih');
        $this->assertSame('bahan', $mine->sumber);
        $this->assertStringContainsString('5', $mine->selisih_text, 'selisih 8 - 3 harus ikut terbawa ke laporan');
    }

    // ---------------------------------------------------------------- gulung satuan (rollUpUnits)

    /** Kembaran persis makeFixtureWithLadder() (Produk) -- lihat StockOpnameV2LifecycleTest. */
    private function makeFixtureWithLadder(int $dosStock = 12, int $pcsStock = 42, int $ratio = 12, int $warehouseId = 1): array
    {
        [$supplies, $dos, $pcs, $dosUnit, $pcsUnit] = $this->makeFixture($dosStock, $pcsStock, $warehouseId);

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $dosUnit->unit_id; // besar
        $relation->sr_value_1 = 1;
        $relation->su_id_2 = $pcsUnit->unit_id; // kecil
        $relation->sr_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        return [$supplies, $dos, $pcs, $dosUnit, $pcsUnit];
    }

    /**
     * Contoh persis dari PM: 1 DOS = 12 pcs, isi 30 pcs -> tersimpan 2 DOS + 6 pcs. DOS live
     * sengaja 0 di sini -- kasus DOS yang SUDAH punya stok live dipegang terpisah oleh
     * test_roll_up_does_not_fold_live_stock_hangus_replace_policy().
     */
    public function test_roll_up_converts_a_filled_small_unit_into_an_untouched_big_one(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(6, (int) $lines[$pcs->unit_id]->sobl_counted_qty);
        $this->assertSame(2, (int) $lines[$dos->unit_id]->sobl_counted_qty, 'DOS harus ikut terisi otomatis dari kelebihan pcs');
    }

    /**
     * rollUpUnits() sendiri tetap bisa digulung pada dokumen draft kalau dipanggil langsung.
     * Endpoint insert/update draft TIDAK memanggilnya — lihat E2E.
     */
    public function test_roll_up_applies_to_draft_and_published_documents(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
        $draft = $this->makeDocument(isDraft: true);
        $this->addLines($draft, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($draft);

        $lines = StockOpnameBahanLine::getLines($draft->stob_id)->keyBy('unit_id');
        $this->assertSame(2, (int) $lines[$dos->unit_id]->sobl_counted_qty, 'helper rollUpUnits tetap jalan di draft');
        $this->assertSame(6, (int) $lines[$pcs->unit_id]->sobl_counted_qty, 'helper rollUpUnits tetap jalan di draft');
    }

    /**
     * Hangus 2026-09-05: isi hanya DOS → pcs yang kosong jadi 0 (bukan null).
     * Beda dari #78 lama (null = tidak dihitung); sekarang bahan disentuh → unit lain 0.
     */
    public function test_roll_up_hangus_untouched_smaller_unit_to_zero_when_bigger_filled(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder();
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$dos->unit_id => 0, $pcs->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(0, (int) $lines[$dos->unit_id]->sobl_counted_qty);
        $this->assertSame(0, (int) $lines[$pcs->unit_id]->sobl_counted_qty, 'pcs hangus jadi 0');
    }

    /** Menjalankan roll-up dua kali pada dokumen yang sama tidak boleh mengubah apa pun lagi. */
    public function test_roll_up_is_idempotent_across_repeated_saves(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder();
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        $lifecycle = new BahanOpnameLifecycle();
        $lifecycle->rollUpUnits($stob);
        $after1 = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id')
            ->map(fn ($l) => $l->sobl_counted_qty)->all();

        $lifecycle->rollUpUnits($stob);
        $after2 = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id')
            ->map(fn ($l) => $l->sobl_counted_qty)->all();

        $this->assertSame($after1, $after2);
    }

    /** Bahan tanpa relasi: diganti oleh test_roll_up_hangus_sibling_unit_when_no_ladder. */

    /**
     * Hangus 2026-09-05: isi HANYA Piece — DOS live TIDAK dilipat; DOS jadi hasil roll-up saja
     * (1000 pcs → 83 DOS + 4 pcs). Unit yang tidak di hasil tetap 0 jika ada di line group.
     */
    public function test_roll_up_does_not_fold_live_stock_hangus_replace_policy(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 84, pcsStock: 0, ratio: 12);
        $stob = $this->makeDocument(isDraft: false);
        $stob->warehouse_id = 1;
        $stob->save();
        $this->addLines($stob, $supplies, [$pcs->unit_id => 1000, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(4, (int) $lines[$pcs->unit_id]->sobl_counted_qty);
        $this->assertSame(83, (int) $lines[$dos->unit_id]->sobl_counted_qty, 'DOS live 84 tidak dilipat; diganti hasil roll pcs');
    }

    /** Tanpa ladder: unit diisi tetap; unit kosong hangus → 0. */
    public function test_roll_up_hangus_sibling_unit_when_no_ladder(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixture(); // tanpa SuppliesRelation
        $stob = $this->makeDocument(isDraft: false);
        $this->addLines($stob, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$pcs->unit_id]->sobl_counted_qty);
        $this->assertSame(0, (int) $lines[$dos->unit_id]->sobl_counted_qty, 'DOS hangus jadi 0');
    }

    /** rollUpUnits() harus keluar lebih dulu untuk dokumen lama, tidak boleh menyentuh apa pun. */
    public function test_roll_up_does_nothing_on_a_legacy_document(): void
    {
        $stob = $this->makeDocument(isDraft: false);
        $stob->is_old_version = true;
        $stob->save();

        (new BahanOpnameLifecycle())->rollUpUnits($stob);
        $this->assertSame(0, StockOpnameBahanLine::where('stob_id', $stob->stob_id)->count());
    }

    /** Gudang eceran: tidak digulung (satu satuan / retail). */
    public function test_roll_up_skips_a_retail_warehouse_document_entirely(): void
    {
        $retailWarehouseId = $this->resolveActiveRetailWarehouseId();

        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0, pcsStock: 42, ratio: 12, warehouseId: $retailWarehouseId);
        $stob = $this->makeDocument(isDraft: false);
        $stob->warehouse_id = $retailWarehouseId;
        $stob->save();
        $this->addLines($stob, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(30, (int) $lines[$pcs->unit_id]->sobl_counted_qty, 'pcs harus tetap apa adanya, tidak digulung');
        $this->assertNull($lines[$dos->unit_id]->sobl_counted_qty, 'DOS tidak boleh ikut terisi otomatis di gudang eceran');
    }

    /** Dokumen tanpa gudang (warehouse_id null) tetap digulung. */
    public function test_roll_up_still_applies_when_document_has_no_warehouse_pinned(): void
    {
        [$supplies, $dos, $pcs] = $this->makeFixtureWithLadder(dosStock: 0);
        $stob = $this->makeDocument(isDraft: false);
        $this->assertNull($stob->warehouse_id);
        $this->addLines($stob, $supplies, [$pcs->unit_id => 30, $dos->unit_id => null]);

        (new BahanOpnameLifecycle())->rollUpUnits($stob);

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->keyBy('unit_id');
        $this->assertSame(2, (int) $lines[$dos->unit_id]->sobl_counted_qty);
        $this->assertSame(6, (int) $lines[$pcs->unit_id]->sobl_counted_qty);
    }
}
