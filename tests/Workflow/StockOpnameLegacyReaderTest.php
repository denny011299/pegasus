<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Support\StockOpname\OpnameLineReader;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Rancang ulang Stock Opname 2026-08-27, sisi DATA LAMA.
 *
 * Syarat mutlak dari PM: dokumen lama TIDAK dimigrasikan dan harus tetap tampil apa adanya.
 * stock_opname_details tidak disentuh sama sekali; OpnameLineReader membacanya lewat cabang
 * legacy. Test ini memakai bentuk baris NYATA dari SP0071 (PDF produksi 2026-08-27).
 *
 * Satu hal yang memang BERUBAH untuk dokumen lama, dan disengaja: selisih ikut diturunkan dari
 * kolom Sistem/Real yang tercetak, bukan dibaca dari stod_selisih. Diverifikasi terhadap 23 baris
 * SP0071 -- semuanya menghasilkan angka yang identik dengan yang tercetak, jadi angkanya tidak
 * bergeser; yang berubah hanya highlight-nya jadi ikut angka (dulu ikut stod_touched dan bisa
 * bertentangan -- itulah bug yang memicu rancang ulang ini).
 */
class StockOpnameLegacyReaderTest extends TestCase
{
    use ActingAsStaff;

    private function makeLegacyDocument(string $system, string $real, string $selisih, int $touched): array
    {
        $category = new Category();
        $category->category_name = 'Opname Legacy Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'MINYAK REM HIKARI MERAH';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([]);
        $product->unit_id = (int) DB::table('units')->where('status', 1)->value('unit_id');
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = '16 X 1 LITER';
        $variant->product_variant_sku = 'LEGACY-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'LG'.substr((string) microtime(true), -4);
        $sto->staff_id = (int) DB::table('staffs')->where('status', 1)->value('staff_id');
        $sto->category_id = -1;
        $sto->status = 3; // Ditolak, seperti SP0071
        $sto->is_draft = 0;
        $sto->save();

        // Tanpa is_old_version eksplisit -- default kolomnya yang harus menandai ini dokumen lama.
        $this->assertTrue((bool) $sto->refresh()->is_old_version);

        $d = new StockOpnameDetail();
        $d->sto_id = $sto->sto_id;
        $d->product_id = $product->product_id;
        $d->product_variant_id = $variant->product_variant_id;
        $d->stod_system = $system;
        $d->stod_real = $real;
        $d->stod_selisih = $selisih;
        $d->stod_touched = $touched;
        $d->status = 1;
        $d->save();

        return [$sto, $variant];
    }

    /** SP0071 baris MRHK1LM -- bug yang memicu seluruh rancang ulang ini. */
    public function test_legacy_row_with_selisih_reads_its_numbers_and_turns_yellow(): void
    {
        // stod_touched = 0 walau jelas ada selisih: dokumen pra-#78 yang stod_system-nya sempat
        // ditulis ulang refreshLiveSystemQty() setelah stok bergerak.
        [$sto] = $this->makeLegacyDocument('3 DOS, 0 pcs', '8 DOS, 0 pcs', '5 DOS, 0 pcs', 0);

        $row = (new OpnameLineReader())->read($sto)->first();

        $this->assertSame('3 DOS, 0 pcs', $row['system_text']);
        $this->assertSame('8 DOS, 0 pcs', $row['real_text']);
        $this->assertSame('5 DOS, 0 pcs', $row['selisih_text'], 'angka tercetak tidak boleh bergeser');
        $this->assertSame('yellow', $row['highlight'], 'warna harus ikut angka, bukan flag');
    }

    /**
     * SP0071 baris SHPWW5L: urutan satuan kolom Sistem terbalik dibanding kolom Real. Pengurangan
     * harus per NAMA satuan; per posisi akan mengarang selisih di baris yang sebenarnya cocok.
     */
    public function test_legacy_row_matches_units_by_name_not_position(): void
    {
        [$sto] = $this->makeLegacyDocument('7 pcs, 2 DOS', '2 DOS, 7 pcs', '0 DOS, 0 pcs', 1);

        $row = (new OpnameLineReader())->read($sto)->first();

        $this->assertSame('0 DOS, 0 pcs', $row['selisih_text']);
        $this->assertFalse($row['has_selisih']);
        $this->assertSame('green', $row['highlight']);
    }

    /** Satuan bertoken "-" tetap tampil "cocok dengan sistem", seperti perilaku sekarang. */
    public function test_legacy_uncounted_unit_still_reads_as_matching_system(): void
    {
        [$sto] = $this->makeLegacyDocument('12 DOS, 5 pcs', '- DOS, - pcs', '- DOS, - pcs', 0);

        $row = (new OpnameLineReader())->read($sto)->first();

        $this->assertSame('12 DOS, 5 pcs', $row['real_text'], 'tanda hubung telanjang terbaca sebagai data hilang di atas kertas');
        $this->assertSame('0 DOS, 0 pcs', $row['selisih_text']);
        $this->assertNull($row['highlight'], 'tidak pernah dihitung -> tanpa highlight');
    }

    /** Dokumen lama tidak boleh ikut membaca tabel baru, dan sebaliknya. */
    public function test_legacy_document_never_touches_the_new_lines_table(): void
    {
        [$sto] = $this->makeLegacyDocument('3 DOS, 0 pcs', '8 DOS, 0 pcs', '5 DOS, 0 pcs', 0);

        $this->assertSame(0, DB::table('stock_opname_lines')->where('sto_id', $sto->sto_id)->count());
        $this->assertCount(1, (new OpnameLineReader())->read($sto));
    }
}
