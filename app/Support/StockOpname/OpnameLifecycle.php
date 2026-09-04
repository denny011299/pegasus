<?php

namespace App\Support\StockOpname;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StockOpnameLine;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\UnitRollUp;

/**
 * Titik tunggal penulisan SNAPSHOT dokumen Stock Opname versi baru (rancang ulang 2026-08-27).
 * Tidak ada tempat lain yang boleh mengisi kolom sol_*_name / sol_system_qty_final / sto_acc_name.
 *
 * Kebijakan siklus hidup (keputusan PM 2026-08-27):
 *
 *   draft              -> tidak ada snapshot sama sekali; halaman draft tampil dari data live.
 *   publish            -> snapshot IDENTITAS (nama produk/varian/SKU/satuan + penanggung jawab).
 *                         Stok sistem SENGAJA TIDAK dibekukan di sini.
 *   menunggu           -> tidak menulis apa pun; stok sistem dibaca live saat ditampilkan.
 *   disetujui/ditolak  -> snapshot STOK SISTEM (sol_system_qty_final) + nama pemutus + waktu.
 *
 * >>> PERBARUI 2026-09-03: jalur draft SUDAH hidup, bukan lagi "tidur" <<<
 * CreateStockOpname.blade.php sekarang memasang KETIGA tombol -- .btn-save-draft, .btn-ajukan,
 * DAN .btn-save -- jadi staf yang menghitung dokumen besar (banyak produk) bisa betul-betul
 * menyimpan sebagai draft berkali-kali sambil terus menghitung, baru menekan .btn-ajukan kalau
 * sudah selesai. Catatan lama di sini bilang draft "masih tidur" dan itulah yang membuat
 * rollUpUnits() dulu dipanggil dari SETIAP simpan (insert+update) tanpa peduli is_draft -- begitu
 * jalur draft hidup, itu jadi bug nyata (GitHub #132, kasus SP0110): tiap simpan-antara draft
 * langsung menggulung angka mentah yang belum selesai diketik staf. Sekarang rollUpUnits() cuma
 * jalan SATU KALI, pas dokumen benar-benar terbit -- lihat docblock method itu sendiri.
 *
 * publish() sendiri TETAP dipicu oleh KEADAAN ("dokumen ini sudah bukan draft dan identitasnya
 * belum dibekukan"), bukan oleh peristiwa tombol tertentu, dan aman dipanggil berkali-kali.
 * Panggil dari SEMUA pintu: insert, update, dan submit.
 */
class OpnameLifecycle
{
    /**
     * Bekukan identitas dokumen kalau (dan hanya kalau) dokumen sudah keluar dari draft.
     * Idempoten: baris yang snapshot-nya sudah ada TIDAK PERNAH ditulis ulang, jadi mengedit
     * dokumen yang sudah diajukan tidak diam-diam menyegarkan nama yang sudah beku.
     */
    public function publish($sto): void
    {
        if (! $sto || $sto->is_draft || $sto->is_old_version) {
            return;
        }

        if (empty($sto->sto_staff_name)) {
            $sto->sto_staff_name = optional(Staff::find($sto->staff_id))->staff_name;
            $sto->save();
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)
            ->filter(fn ($l) => $l->sol_product_name === null);

        if ($lines->isEmpty()) {
            return;
        }

        $variants = ProductVariant::whereIn('product_variant_id', $lines->pluck('product_variant_id')->filter()->unique()->all())
            ->get()->keyBy('product_variant_id');
        $products = Product::whereIn('product_id', $variants->pluck('product_id')->filter()->unique()->all())
            ->get()->keyBy('product_id');
        $units = Unit::whereIn('unit_id', $lines->pluck('unit_id')->filter()->unique()->all())
            ->get()->keyBy('unit_id');

        foreach ($lines as $line) {
            $variant = $variants->get($line->product_variant_id);
            $product = $variant ? $products->get($variant->product_id) : null;
            $unit = $units->get($line->unit_id);

            // Fallback "#id" dipakai kalau referensinya sudah hilang SEBELUM sempat dibekukan --
            // tetap terbaca manusia dan tetap bisa dilacak, jauh lebih baik daripada kolom kosong.
            $line->sol_product_name = $product->product_name ?? ('produk#'.$line->product_variant_id);
            $line->sol_variant_name = $variant->product_variant_name ?? null;
            $line->sol_variant_sku = $variant->product_variant_sku ?? null;
            $line->sol_unit_short_name = $unit->unit_short_name ?? ('unit#'.$line->unit_id);
            $line->sol_unit_name = $unit->unit_name ?? null;
            $line->save();
        }
    }

    /**
     * Bekukan stok sistem yang BERLAKU saat dokumen diputuskan.
     *
     * >>> WAJIB dipanggil SEBELUM stok live ditimpa oleh hasil hitung <<<
     * Kalau dipanggil sesudah, sol_system_qty_final akan berisi hasil hitung itu sendiri dan
     * selisih dokumen jadi 0 selamanya. Urutan benar di alur ACC:
     *     freezeSystemQty() -> tulis ps_stock -> stampDecision()
     *
     * Dipanggil untuk DITOLAK juga: dokumen yang sudah diputuskan harus berhenti bergerak,
     * walau tidak ada stok yang ditulis.
     *
     * sol_system_qty_final NULL berarti baris product_stocks-nya memang tidak ada saat diputuskan
     * (bukan "belum diisi") -- pembaca menampilkannya sebagai 0.
     */
    public function freezeSystemQty($sto): void
    {
        if (! $sto || $sto->is_old_version) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id);
        if ($lines->isEmpty()) {
            return;
        }

        // Dipin ke gudang dokumen ini, bukan gudang aktif sesi yang meng-ACC/menolak -- lihat
        // StockOpname\OpnameLineReader::liveStockMap()'s doc untuk alasan lengkap.
        $warehouseId = $sto->warehouse_id ?: null;
        $stocks = ($warehouseId !== null
                ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : ProductStock::query())
            ->where('status', 1)
            ->whereIn('product_variant_id', $lines->pluck('product_variant_id')->filter()->unique()->all())
            ->get()
            ->keyBy(fn ($s) => $s->product_variant_id.'-'.$s->unit_id);

        foreach ($lines as $line) {
            $stock = $stocks->get($line->product_variant_id.'-'.$line->unit_id);
            $line->sol_system_qty_final = $stock ? (int) $stock->ps_stock : null;
            $line->save();
        }
    }

    /**
     * Gulung + hangus hasil hitung per produk (UnitRollUp::collapse, tanpa lipat stok live).
     *
     * Policy 2026-09-05 (ganti #132 untuk timing; ganti fold-live untuk hangus):
     * - Dipanggil di SETIAP simpan (insert/update/submit), termasuk draft — angka di DB langsung
     *   kanonik setelah ketik+simpan.
     * - ≥1 satuan diisi → satuan lain pada item itu jadi 0 (hangus), bukan null / bukan stok live.
     * - 0 satuan diisi → tidak menulis apa pun (ACC tetap skip null).
     * - Gudang eceran: no-op (satu satuan).
     */
    public function rollUpUnits($sto): void
    {
        if (! $sto || $sto->is_old_version) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)->groupBy('product_variant_id');
        $warehouseId = $sto->warehouse_id ?: null;

        if (self::isRetailWarehouse($warehouseId)) {
            return;
        }

        foreach ($lines as $productVariantId => $group) {
            if (! $productVariantId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sol_counted_qty])->all();
            $touched = collect($qtyByUnit)->contains(fn ($q) => $q !== null);
            if (! $touched) {
                continue;
            }

            // Hangus: jangan lipat stok live unit kosong ke carry.
            $collapsed = UnitRollUp::collapseProduct((int) $productVariantId, $qtyByUnit, $warehouseId, false);
            $resultByUnit = [];
            foreach ($collapsed as $credit) {
                $resultByUnit[(int) $credit['unit_id']] = (int) $credit['qty'];
            }

            $first = $group->first();
            foreach ($group as $line) {
                $uid = (int) $line->unit_id;
                if (array_key_exists($uid, $resultByUnit)) {
                    $qty = $resultByUnit[$uid];
                } elseif ($qtyByUnit[$uid] !== null) {
                    // Diisi user tapi tidak ada ladder / tidak tersentuh collapse — pertahankan.
                    $qty = (int) $qtyByUnit[$uid];
                } else {
                    $qty = 0; // hangus
                }

                StockOpnameLine::upsertLine([
                    'sto_id' => $sto->sto_id,
                    'product_id' => $first->product_id,
                    'product_variant_id' => $productVariantId,
                    'unit_id' => $uid,
                    'sol_counted_qty' => $qty,
                    'sol_notes' => $first->sol_notes,
                ]);
            }
        }
    }

    /** Cap keputusan di header -- nama pemutus dibekukan supaya tidak ikut hilang kalau staf dihapus. */
    public function stampDecision($sto, ?int $accBy): void
    {
        if (! $sto || $sto->is_old_version) {
            return;
        }

        $sto->sto_acc_name = $accBy ? optional(Staff::find($accBy))->staff_name : null;
        $sto->sto_decided_at = now();
        $sto->save();
    }

    /**
     * true = $warehouseId ada dan tipenya BUKAN gudang utama (is_main_warehouse != 1), jadi
     * eceran. Tidak ada gudang (null/0) atau tipe yang tidak ketemu dianggap BUKAN eceran --
     * gulung tetap jalan seperti sebelum multi-gudang ada, sama seperti pola inline yang sama di
     * ProductVariant::getProductVariant().
     */
    private static function isRetailWarehouse(?int $warehouseId): bool
    {
        if (! $warehouseId) {
            return false;
        }

        $warehouse = Warehouse::query()
            ->with(['type' => fn ($q) => $q->select('id', 'is_main_warehouse')])
            ->find($warehouseId);

        return (bool) ($warehouse && $warehouse->type && (int) $warehouse->type->is_main_warehouse !== 1);
    }
}
