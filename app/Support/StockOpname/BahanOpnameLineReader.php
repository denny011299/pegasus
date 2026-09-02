<?php

namespace App\Support\StockOpname;

use App\Models\StockOpnameBahanLine;
use App\Models\StockOpnameDetailBahan;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;

/**
 * Kembaran persis App\Support\StockOpname\OpnameLineReader, untuk Stock Opname BAHAN (Supplies).
 * Satu-satunya pintu baca dokumen Bahan untuk tampilan (PDF, halaman detail, daftar/laporan).
 * Aturan intinya IDENTIK dengan OpnameLineReader -- lihat kelas itu untuk penjelasan lengkap:
 * selisih selalu diturunkan (tidak pernah dibaca dari kolom tersimpan), highlight diputuskan di
 * sini (bukan di blade), dan satuan tak dihitung dihumanisasi saat dicetak (GitHub #78 follow-up).
 *
 * Beda dari OpnameLineReader:
 *  - Identitasnya supplies_id (bukan product_id + product_variant_id) -- Supplies tidak punya
 *    varian/SKU.
 *  - legacyItems() untuk Bahan bentuknya beda dari Produk: CreateStockOpnameSupplies.js
 *    (renderMode2()) mengurai ULANG stobd_real/stobd_system menjadi sp_units di sisi KLIEN,
 *    berbekal `item.units` (katalog satuan LENGKAP milik bahan itu, dari Supplies::
 *    getSuppliesBulk()) dan `item.stock` (stok live, cuma untuk placeholder). Jadi adaptor di
 *    sini cukup menyediakan kedua daftar itu plus string stobd_*, TIDAK perlu merakit sp_units
 *    sendiri seperti Produk merakit 'units' server-side.
 */
class BahanOpnameLineReader
{
    public const STATUS_MENUNGGU = 1;

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    public function read($stob)
    {
        if (! $stob) {
            return collect();
        }

        return $stob->is_old_version ? $this->readLegacy($stob) : $this->readCurrent($stob);
    }

    private function readCurrent($stob)
    {
        $lines = StockOpnameBahanLine::getLines($stob->stob_id);
        if ($lines->isEmpty()) {
            return collect();
        }

        $pending = (int) $stob->status === self::STATUS_MENUNGGU;
        // GitHub #115: kembaran OpnameLineReader::readCurrent() -- draft tidak boleh membaca stok
        // sistem sama sekali, cuma angka yang benar-benar sudah diinput staf sendiri.
        $isDraft = (bool) $stob->is_draft;
        // Dipin ke gudang dokumen ini, bukan gudang aktif sesi pembaca -- lihat liveStockMap().
        $live = $isDraft ? collect() : $this->liveStockMap($lines, $stob->warehouse_id ?: null);

        // Bug dilaporkan user (2026-09-02): draft TIDAK PERNAH lewat publish() (lihat
        // BahanOpnameLifecycle::publish()'s own guard), jadi sobl_unit_short_name masih NULL --
        // dulu jatuh ke fallback "unit#12" di bawah. legacyItems() mencetak fallback itu ke dalam
        // stobd_real/stobd_system, lalu CreateStockOpnameSupplies.js MEM-PARSE ULANG string itu
        // (seedSavedValuesFromItems()/renderMode2()) dan mencocokkan nama satuan ke katalog asli
        // ("pcs", bukan "unit#12") -- cocoknya gagal, jadi nilai yang barusan diinput staf tidak
        // pernah muncul lagi saat draft dibuka ulang. Pakai nama satuan KATALOG (live, sama seperti
        // yang dipakai legacyItems() membangun daftar 'units') sebagai fallback SEBELUM literal
        // "unit#N" -- itu cadangan terakhir kalau unit-nya sendiri sudah terhapus dari database.
        $unitNames = Unit::whereIn('unit_id', $lines->pluck('unit_id')->filter()->unique()->all())
            ->pluck('unit_short_name', 'unit_id');

        return $lines
            ->groupBy('supplies_id')
            ->map(function ($group) use ($pending, $live, $isDraft, $unitNames) {
                $first = $group->first();
                $units = [];

                foreach ($group as $line) {
                    if ($isDraft) {
                        $liveQty = null;
                        $system = null;
                    } else {
                        $liveQty = (int) ($live->get($line->supplies_id.'-'.$line->unit_id) ?? 0);
                        $system = $pending ? $liveQty : (int) ($line->sobl_system_qty_final ?? 0);
                    }

                    $units[] = [
                        'unit' => $line->sobl_unit_short_name ?? $unitNames->get($line->unit_id) ?? ('unit#'.$line->unit_id),
                        'unit_id' => $line->unit_id,
                        'system' => $system,
                        'live' => $liveQty,
                        'counted' => $line->sobl_counted_qty === null ? null : (int) $line->sobl_counted_qty,
                        'selisih' => $isDraft ? null : $line->selisih($system),
                    ];
                }

                return $this->viewRow(
                    $first->sobl_supplies_name,
                    $first->sobl_notes,
                    $units,
                    null,
                    $first->supplies_id
                );
            })
            ->values();
    }

    /**
     * Sama seperti readLegacy() di OpnameLineReader: cocokkan satuan per NAMA, bukan per posisi
     * (kolom Sistem dan Real bisa berbeda urutan), dan satuan tak terhitung ("-" ) tampil "cocok
     * dengan sistem" seperti perilaku sekarang.
     */
    private function readLegacy($stob)
    {
        $details = StockOpnameDetailBahan::getDetail(['stob_id' => $stob->stob_id]);

        return collect($details)->map(function ($item) {
            $systemMap = LegacyQtyString::parse($item->stobd_system ?? '');
            $realMap = LegacyQtyString::parse($item->stobd_real ?? '');

            $units = [];
            foreach ($realMap as $unit => $counted) {
                $system = (int) ($systemMap[$unit] ?? 0);
                $uncounted = $counted === null;

                $units[] = [
                    'unit' => $unit,
                    'unit_id' => null,
                    'system' => $system,
                    'live' => $system,
                    'counted' => $uncounted ? $system : (int) $counted,
                    'selisih' => $uncounted ? 0 : ((int) $counted - $system),
                ];
            }

            return $this->viewRow(
                $item->supplies_name ?? null,
                $item->stobd_notes ?? null,
                $units,
                ! empty($item->stobd_touched),
                $item->supplies_id ?? null
            );
        })->values();
    }

    /**
     * Bentuk payload halaman detail/edit (CreateStockOpnameSupplies.js renderMode2()) untuk
     * dokumen versi BARU -- struktur SAMA PERSIS dengan yang sudah dipakai DetailStockOpnameBahan()
     * hari ini (item.stobd_system/real/selisih string + item.units katalog + item.stock live),
     * supaya pindah tulis ke stock_opname_bahan_lines TIDAK menuntut perubahan JS sama sekali.
     */
    public function legacyItems($stob): array
    {
        $rows = $this->read($stob);
        if ($rows->isEmpty()) {
            return [];
        }

        $suppliesIds = $rows->pluck('supplies_id')->filter()->unique()->all();
        $supplies = Supplies::whereIn('supplies_id', $suppliesIds)->get()->keyBy('supplies_id');

        $allUnitIds = [];
        foreach ($supplies as $s) {
            foreach (json_decode($s->supplies_unit ?? '[]', true) ?: [] as $uid) {
                $allUnitIds[(int) $uid] = true;
            }
        }
        $unitsMap = $allUnitIds !== []
            ? Unit::whereIn('unit_id', array_keys($allUnitIds))->get()->keyBy('unit_id')
            : collect();

        // GitHub #115: draft -- 'stock' di sini murni buat placeholder hint di halaman input,
        // yang justru harus KOSONG untuk draft (lihat readCurrent()). Jangan ambil sama sekali.
        // Dipin ke gudang dokumen ini -- lihat liveStockMap()'s doc.
        $warehouseId = $stob->warehouse_id ?: null;
        if ($stob->is_draft) {
            $liveStocks = collect();
        } else {
            $liveStocks = ($warehouseId !== null
                    ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                    : SuppliesStock::query())
                ->where('status', 1)
                ->whereIn('supplies_id', $suppliesIds)
                ->get()
                ->groupBy('supplies_id');
        }

        return $rows->map(function (array $row) use ($supplies, $unitsMap, $liveStocks) {
            $supply = $supplies->get($row['supplies_id']);
            $catalogUnitIds = $supply ? (json_decode($supply->supplies_unit ?? '[]', true) ?: []) : [];

            $units = collect($catalogUnitIds)
                ->map(fn ($uid) => $unitsMap->get((int) $uid))
                ->filter()
                ->map(fn ($u) => ['unit_id' => $u->unit_id, 'unit_short_name' => $u->unit_short_name, 'unit_name' => $u->unit_name])
                ->values()
                ->all();

            $stock = $liveStocks->get($row['supplies_id'], collect())
                ->map(fn ($s) => ['unit_id' => $s->unit_id, 'unit_short_name' => $unitsMap->get($s->unit_id)->unit_short_name ?? '-', 'ss_stock' => (int) $s->ss_stock])
                ->values()
                ->all();

            return [
                'supplies_id' => $row['supplies_id'],
                'supplies_name' => $row['supplies_name'],
                'stobd_notes' => $row['notes'] === '-' ? null : $row['notes'],
                'stobd_system' => $row['system_text'],
                'stobd_real' => $row['real_text'],
                'stobd_selisih' => $row['selisih_text'],
                'units' => $units,
                'stock' => $stock,
            ];
        })->values()->all();
    }

    private function viewRow(?string $supplyName, ?string $notes, array $units, ?bool $legacyTouched = null, $suppliesId = null): array
    {
        $counted = $legacyTouched ?? collect($units)->contains(fn ($u) => $u['counted'] !== null);
        $hasSelisih = collect($units)->contains(fn ($u) => (int) ($u['selisih'] ?? 0) !== 0);

        return [
            'supplies_id' => $suppliesId,
            'supplies_name' => $supplyName ?: '-',
            'notes' => $notes ?: '-',
            'units' => $units,
            'system_text' => $this->text($units, 'system'),
            'real_text' => $this->text($units, 'counted'),
            'selisih_text' => $this->text($units, 'selisih'),
            'counted' => $counted,
            'has_selisih' => $hasSelisih,
            'highlight' => $hasSelisih ? 'yellow' : ($counted ? 'green' : null),

            // Alias bernama-lama, supaya Backoffice/PDF/OpnameBahan.blade.php tetap SATU template
            // untuk kedua versi.
            'stobd_notes' => $notes ?: '-',
            'stobd_system' => $this->text($units, 'system'),
            'stobd_real' => $this->text($units, 'counted'),
            'stobd_selisih' => $this->text($units, 'selisih'),
        ];
    }

    /**
     * GitHub #78 follow-up (lihat OpnameLineReader::text()): satuan tak dihitung tercetak "cocok
     * dengan sistem", bukan tanda hubung telanjang. Murni kosmetik tampilan.
     */
    /**
     * GitHub #115: kembaran OpnameLineReader::text() -- draft tidak membawa stok sistem
     * ($u['system'] === null), jadi tidak ada fallback untuk dipegang: "-" apa adanya.
     */
    private function text(array $units, string $key): string
    {
        $parts = [];
        foreach ($units as $u) {
            $value = $u[$key];
            if ($value === null) {
                $value = ($key === 'selisih' && $u['system'] !== null) ? 0 : $u['system'];
            }
            $parts[] = ($value === null ? '-' : $value).' '.$u['unit'];
        }

        return implode(', ', $parts);
    }

    /**
     * @return \Illuminate\Support\Collection<string, int> keyed "suppliesId-unitId"
     *
     * \$warehouseId pins the read to the document's OWN warehouse -- see
     * OpnameLineReader::liveStockMap()'s doc for why.
     */
    private function liveStockMap($lines, ?int $warehouseId = null)
    {
        return ($warehouseId !== null
                ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : SuppliesStock::query())
            ->where('status', 1)
            ->whereIn('supplies_id', $lines->pluck('supplies_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->supplies_id.'-'.$s->unit_id => (int) $s->ss_stock]);
    }
}
