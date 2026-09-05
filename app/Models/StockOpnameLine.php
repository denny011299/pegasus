<?php

namespace App\Models;

use App\Support\StockOpname\LegacyQtyString;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail Stock Opname Produk VERSI BARU -- satu baris per satuan, angka betulan.
 * Dokumen lama tetap di stock_opname_details dan tidak pernah disentuh; pemisahnya
 * stock_opnames.is_old_version (lihat migration 2026_08_27_010000).
 *
 * Aturan yang dipegang model ini:
 *  - sol_counted_qty NULL = satuan tidak dihitung. Bukan 0, bukan token "-", bukan flag terpisah.
 *  - Selisih TIDAK PERNAH disimpan, selalu diturunkan (lihat selisih()).
 *  - Kolom snapshot diisi oleh App\Support\StockOpname\OpnameLifecycle, bukan di sini.
 */
class StockOpnameLine extends Model
{
    protected $table = "stock_opname_lines";
    protected $primaryKey = "sol_id";
    public $timestamps = true;
    public $incrementing = true;

    /**
     * Baris aktif satu dokumen, urut stabil (sesuai urutan input, bukan urutan katalog live).
     */
    public static function getLines($stoId)
    {
        return self::where('status', 1)
            ->where('sto_id', $stoId)
            ->orderBy('sol_id', 'asc')
            ->get();
    }

    /**
     * Simpan/perbarui satu baris berdasarkan identitas alaminya (sto_id + varian + satuan).
     * Dipakai baik saat simpan draft maupun saat simpan dokumen non-draft.
     *
     * Sengaja upsert, bukan insert: unique index stock_opname_lines_line_unique menjadikan baris
     * ganda mustahil, jadi bug alur lama (updateStockOpname() cuma memperbarui header sementara
     * JS tidak pernah mengirim stod_id, sehingga tiap kali simpan SEMUA baris disisipkan ulang)
     * tidak bisa terulang di sini.
     */
    public static function upsertLine(array $data)
    {
        $t = self::where('sto_id', $data['sto_id'])
            ->where('product_variant_id', $data['product_variant_id'])
            ->where('unit_id', $data['unit_id'])
            ->first() ?? new self();

        $t->sto_id = $data['sto_id'];
        $t->product_id = $data['product_id'] ?? null;
        $t->product_variant_id = $data['product_variant_id'];
        $t->unit_id = $data['unit_id'];
        // array_key_exists, bukan ?? -- null di sini BERMAKNA ("tidak dihitung"), bukan "tidak dikirim".
        $t->sol_counted_qty = array_key_exists('sol_counted_qty', $data)
            ? ($data['sol_counted_qty'] === null ? null : (int) $data['sol_counted_qty'])
            : $t->sol_counted_qty;
        if (array_key_exists('sol_use_system_stock', $data)) {
            $t->sol_use_system_stock = (bool) $data['sol_use_system_stock'];
        }
        $t->sol_notes = $data['sol_notes'] ?? null;
        $t->status = 1;
        $t->save();

        return $t->sol_id;
    }

    /**
     * Tulis baris dari payload frontend (CreateStockOpname.js). Bentuk item-nya:
     *   ['product_id', 'product_variant_id', 'stod_notes', 'units' => [
     *       ['unit_id' => .., 'system_qty' => .., 'real_qty' => null|int], ...
     *   ]]
     *
     * units[] ini SUDAH dikirim frontend sejak dulu -- alur lama membuangnya begitu saja saat
     * insert lalu memintanya lagi dari DOM browser saat ACC. Di sini justru itu yang disimpan,
     * jadi pindah ke skema baru TIDAK butuh perubahan JS sama sekali.
     *
     * real_qty null = satuan tidak dihitung, disimpan apa adanya sebagai NULL.
     *
     * SENGAJA upsert-only, tidak pernah menonaktifkan baris yang absen dari payload: halaman
     * input memakai filter pencarian, jadi $(".row-stock") saat simpan bisa berisi SEBAGIAN
     * produk saja. Menghapus yang absen akan membuang hasil hitung yang tidak sedang tampil.
     */
    public static function writeFromPayload($stoId, array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['product_variant_id'])) {
                continue;
            }

            $units = ! empty($item['units']) ? $item['units'] : self::unitsFromLegacyPayload($item);
            if ($units === []) {
                continue;
            }

            foreach ($units as $unit) {
                if (empty($unit['unit_id'])) {
                    continue;
                }

                $useSystem = ! empty($unit['use_system_stock']);
                self::upsertLine([
                    'sto_id' => $stoId,
                    'product_id' => $item['product_id'] ?? null,
                    'product_variant_id' => $item['product_variant_id'],
                    'unit_id' => $unit['unit_id'],
                    'sol_counted_qty' => array_key_exists('real_qty', $unit) ? $unit['real_qty'] : null,
                    'sol_use_system_stock' => $useSystem,
                    'sol_notes' => $item['stod_notes'] ?? null,
                ]);
            }
        }
    }

    /**
     * Cadangan kalau payload TIDAK membawa units[] sama sekali: pulihkan satuannya dari string
     * gaya lama (stod_system/stod_real, "12 DOS, - pcs"), cocokkan unit_id lewat nama pendeknya.
     *
     * Ada supaya kegagalannya tidak pernah SENYAP. Tanpa ini, payload bentuk lama (mis. JS lama
     * yang masih ter-cache di browser seorang staf setelah deploy) akan membuat dokumen yang
     * tersimpan rapi tapi KOSONG -- terlihat baik-baik saja sampai ada yang mencoba mencetaknya.
     * Lebih baik memulihkan apa yang bisa dipulihkan daripada diam-diam kehilangan hasil hitung.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function unitsFromLegacyPayload(array $item): array
    {
        $system = LegacyQtyString::parse($item['stod_system'] ?? '');
        $real = LegacyQtyString::parse($item['stod_real'] ?? '');
        if ($system === [] && $real === []) {
            return [];
        }

        $names = array_keys($system + $real);
        $unitIds = Unit::whereIn('unit_short_name', $names)->pluck('unit_id', 'unit_short_name');

        $units = [];
        foreach ($names as $name) {
            if (! isset($unitIds[$name])) {
                continue; // satuan tidak dikenali -- tidak bisa dipetakan, lewati
            }

            $units[] = [
                'unit_id' => $unitIds[$name],
                'system_qty' => $system[$name] ?? 0,
                // null di sini BERMAKNA: token "-" = satuan tidak dihitung.
                'real_qty' => array_key_exists($name, $real) ? $real[$name] : null,
            ];
        }

        return $units;
    }

    /** Soft delete sesuai konvensi repo (status = 0), tidak pernah hard delete. */
    public static function deleteLines($stoId)
    {
        self::where('sto_id', $stoId)->update(['status' => 0]);
    }

    /** Satuan ini benar-benar dihitung staf? */
    public function dihitung(): bool
    {
        return $this->sol_counted_qty !== null;
    }

    /**
     * Selisih baris ini terhadap stok sistem yang BERLAKU -- turunan, tidak pernah disimpan.
     * $systemQty diisi pemanggil: stok live untuk dokumen yang belum diputuskan, atau
     * sol_system_qty_final untuk dokumen yang sudah beku. NULL kalau satuan tidak dihitung.
     */
    public function selisih(?int $systemQty): ?int
    {
        if ($this->sol_counted_qty === null) {
            return null;
        }

        return (int) $this->sol_counted_qty - (int) $systemQty;
    }
}
