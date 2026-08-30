<?php

namespace App\Support\StockOpname;

use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StockOpnameLine;
use App\Models\Unit;
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
 * >>> PERHATIAN alur UI SEKARANG <<<
 * CreateStockOpname.blade.php cuma memasang tombol .btn-save, yang memanggil insertData() TANPA
 * isDraft -- artinya dokumen dibuat LANGSUNG non-draft (is_draft = 0), tidak pernah lewat
 * /submitStockOpname. Handler .btn-save-draft / .btn-ajukan sudah ada di CreateStockOpname.js
 * tapi markup-nya belum dipasang, jadi jalur draft masih tidur.
 *
 * Karena itu publish() dipicu oleh KEADAAN ("dokumen ini sudah bukan draft dan identitasnya
 * belum dibekukan"), bukan oleh peristiwa tombol tertentu, dan aman dipanggil berkali-kali.
 * Panggil dari SEMUA pintu: insert, update, dan submit. Saat tombol draft nanti dipasang, tidak
 * ada satu baris pun di kelas ini yang perlu berubah.
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
     * Gulung REKURSIF hasil hitung tiap produk ke tangga satuannya (App\Support\UnitRollUp::
     * collapseProduct()) -- keputusan PM 2026-08-27: isi satuan kecil, satuan besar yang belum
     * disentuh ikut naik otomatis (mis. 1 DOS = 12 pcs, isi 30 pcs -> tersimpan 2 DOS + 6 pcs).
     *
     * Dipanggil dari SETIAP simpan (insert MAUPUN update) -- draft ataupun langsung menunggu,
     * bukan cuma saat diajukan/diputuskan. Aman dipanggil berkali-kali: collapse() idempoten,
     * menjalankan ulang pada dokumen yang sudah tergulung tidak mengubah apa pun lagi.
     *
     * TIDAK PERNAH mengisi satuan yang lebih kecil dari satuan terkecil yang benar-benar diisi --
     * lihat UnitRollUp::collapse() untuk alasan lengkap (setara GitHub #78: jangan mengarang data
     * yang tidak pernah diperiksa staf).
     */
    public function rollUpUnits($sto): void
    {
        if (! $sto || $sto->is_old_version) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)->groupBy('product_variant_id');
        // Gudang DOKUMEN, bukan gudang aktif sesi yang kebetulan menyimpan -- lihat
        // UnitRollUp::collapseProduct() untuk alasannya.
        $warehouseId = $sto->warehouse_id ?: null;

        foreach ($lines as $productVariantId => $group) {
            if (! $productVariantId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sol_counted_qty])->all();
            $collapsed = UnitRollUp::collapseProduct((int) $productVariantId, $qtyByUnit, $warehouseId);
            if ($collapsed === []) {
                continue;
            }

            $first = $group->first();

            foreach ($collapsed as $credit) {
                StockOpnameLine::upsertLine([
                    'sto_id' => $sto->sto_id,
                    'product_id' => $first->product_id,
                    'product_variant_id' => $productVariantId,
                    'unit_id' => $credit['unit_id'],
                    'sol_counted_qty' => $credit['qty'],
                    'sol_notes' => $first->sol_notes,
                ]);
            }
        }
    }

    /**
     * Sembuhkan LIVE ps_stock produk yang ikut di dokumen ini SEBELUM dokumen mulai dipercaya --
     * keputusan PM 2026-08-31: stok yang stuck under-rolled dari sebelum GitHub #87 (mis. 12 DOS +
     * 24 Piece padahal 1 DOS = 24 Piece, seharusnya 13 DOS + 0 Piece) tetap salah selamanya kalau
     * tidak ada transaksi stok-masuk lain yang kebetulan menyentuhnya (lihat docblock
     * AuditStuckUnitRollUpCommand). Membuat Stock Opname adalah persis momen staf akan MEMPERCAYAI
     * angka live itu, jadi sembuhkan di sini juga -- primitif yang sama (UnitRollUp::
     * collapseProduct()) yang dipakai command audit itu dan rollUpUnits() di atas.
     *
     * HANYA menyentuh satuan yang TIDAK dihitung di dokumen ini (sol_counted_qty === null).
     * Satuan yang sedang diisi staf akan ditimpa langsung oleh hasil hitungnya sendiri saat ACC
     * (lihat accStockOpnameV2()), jadi menyembuhkannya di sini cuma kerja ganda yang percuma --
     * dan tidak menyentuhnya menjaga efek method ini persis sebatas satuan yang memang diminta.
     *
     * Dipanggil dari insertStockOpname() (dokumen baru langsung menunggu) dan submitStockOpname()
     * (draft -> menunggu). TIDAK dipanggil selama masih draft (dijaga is_draft di bawah) --
     * idempoten, memanggilnya lagi pada produk yang sudah sembuh tidak mengubah apa pun lagi.
     *
     * Gudang DOKUMEN, bukan gudang aktif sesi (beda dari versi main, yang tidak punya konsep gudang
     * sama sekali) -- alasan sama persis dengan freezeSystemQty()/rollUpUnits() di atas: staf yang
     * menyimpan dokumen bisa punya gudang aktif yang berbeda dari gudang dokumennya, dan
     * menyembuhkan stok gudang yang salah jauh lebih buruk daripada tidak menyembuhkan sama sekali.
     */
    public function healUntouchedSystemStock($sto): void
    {
        if (! $sto || $sto->is_draft || $sto->is_old_version) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)->groupBy('product_variant_id');
        $warehouseId = $sto->warehouse_id ?: null;

        foreach ($lines as $productVariantId => $group) {
            if (! $productVariantId) {
                continue;
            }

            $touchedUnitIds = $group->filter(fn ($l) => $l->sol_counted_qty !== null)
                ->pluck('unit_id')->map(fn ($u) => (int) $u)->all();

            $stocks = ($warehouseId !== null
                    ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                    : ProductStock::query())
                ->where('product_variant_id', $productVariantId)
                ->where('status', 1)
                ->get();

            $qtyByUnit = $stocks->pluck('ps_stock', 'unit_id')->map(fn ($q) => (int) $q)->all();
            $collapsed = UnitRollUp::collapseProduct((int) $productVariantId, $qtyByUnit, $warehouseId);
            if ($collapsed === []) {
                continue;
            }

            $stocksByUnit = $stocks->keyBy('unit_id');

            foreach ($collapsed as $credit) {
                $unitId = (int) $credit['unit_id'];
                if (in_array($unitId, $touchedUnitIds, true)) {
                    continue;
                }

                $stock = $stocksByUnit->get($unitId);
                $before = $stock ? (int) $stock->ps_stock : 0;
                $after = (int) $credit['qty'];
                if (! $stock || $before === $after) {
                    continue;
                }

                $delta = $after - $before;
                $stock->ps_stock = $after;
                $stock->save();

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $sto->sto_code,
                    'log_type' => 1,
                    'log_category' => $delta > 0 ? 1 : 2,
                    'log_item_id' => $productVariantId,
                    'log_notes' => 'Konversi unit dari Stock Opname (perbaikan stok tergulung) '.LogStock::actorSuffix(),
                    'log_jumlah' => abs($delta),
                    'log_saldo' => $after,
                    'unit_id' => $unitId,
                    'warehouse_id' => $warehouseId,
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
}
