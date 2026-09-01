<?php

namespace Tests\Workflow;

use App\Models\StockOpnameBahan;
use App\Models\StockOpnameDetailBahan;
use App\Models\Supplies;
use App\Support\StockOpname\BahanOpnameLineReader;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Kembaran persis Tests\Workflow\StockOpnameLegacyReaderTest, untuk Stock Opname BAHAN.
 * Dokumen lama TIDAK dimigrasikan; stock_opname_detail_bahans tidak disentuh sama sekali,
 * BahanOpnameLineReader membacanya lewat cabang legacy.
 */
class StockOpnameBahanLegacyReaderTest extends TestCase
{
    use ActingAsStaff;

    private function makeLegacyDocument(string $system, string $real, string $selisih, int $touched): StockOpnameBahan
    {
        $supplies = new Supplies();
        $supplies->supplies_name = 'MINYAK REM HIKARI MERAH (Bahan Legacy Test)';
        $supplies->supplies_unit = json_encode([]);
        $supplies->supplies_default_unit = (int) DB::table('units')->where('status', 1)->value('unit_id');
        $supplies->status = 1;
        $supplies->save();

        $stob = new StockOpnameBahan();
        $stob->stob_date = now()->toDateString();
        $stob->stob_code = 'LB'.substr((string) microtime(true), -4);
        $stob->staff_id = (int) DB::table('staffs')->where('status', 1)->value('staff_id');
        $stob->status = 3; // Ditolak
        $stob->is_draft = 0;
        $stob->save();

        $this->assertTrue((bool) $stob->refresh()->is_old_version);

        $d = new StockOpnameDetailBahan();
        $d->stob_id = $stob->stob_id;
        $d->supplies_id = $supplies->supplies_id;
        $d->stobd_system = $system;
        $d->stobd_real = $real;
        $d->stobd_selisih = $selisih;
        $d->stobd_touched = $touched;
        $d->status = 1;
        $d->save();

        return $stob;
    }

    public function test_legacy_row_with_selisih_reads_its_numbers_and_turns_yellow(): void
    {
        $stob = $this->makeLegacyDocument('3 DOS, 0 pcs', '8 DOS, 0 pcs', '5 DOS, 0 pcs', 0);

        $row = (new BahanOpnameLineReader())->read($stob)->first();

        $this->assertSame('3 DOS, 0 pcs', $row['system_text']);
        $this->assertSame('8 DOS, 0 pcs', $row['real_text']);
        $this->assertSame('5 DOS, 0 pcs', $row['selisih_text']);
        $this->assertSame('yellow', $row['highlight']);
    }

    public function test_legacy_row_matches_units_by_name_not_position(): void
    {
        $stob = $this->makeLegacyDocument('7 pcs, 2 DOS', '2 DOS, 7 pcs', '0 DOS, 0 pcs', 1);

        $row = (new BahanOpnameLineReader())->read($stob)->first();

        $this->assertSame('0 DOS, 0 pcs', $row['selisih_text']);
        $this->assertFalse($row['has_selisih']);
        $this->assertSame('green', $row['highlight']);
    }

    public function test_legacy_uncounted_unit_still_reads_as_matching_system(): void
    {
        $stob = $this->makeLegacyDocument('12 DOS, 5 pcs', '- DOS, - pcs', '- DOS, - pcs', 0);

        $row = (new BahanOpnameLineReader())->read($stob)->first();

        $this->assertSame('12 DOS, 5 pcs', $row['real_text']);
        $this->assertSame('0 DOS, 0 pcs', $row['selisih_text']);
        $this->assertNull($row['highlight']);
    }

    public function test_legacy_document_never_touches_the_new_bahan_lines_table(): void
    {
        $stob = $this->makeLegacyDocument('3 DOS, 0 pcs', '8 DOS, 0 pcs', '5 DOS, 0 pcs', 0);

        $this->assertSame(0, DB::table('stock_opname_bahan_lines')->where('stob_id', $stob->stob_id)->count());
        $this->assertCount(1, (new BahanOpnameLineReader())->read($stob));
    }
}
