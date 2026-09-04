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
     * Gulung REKURSIF hasil hitung tiap produk ke tangga satuannya (App\Support\UnitRollUp::
     * collapseProduct()) -- isi satuan kecil, satuan besar yang belum disentuh ikut naik otomatis
     * (mis. 1 DOS = 12 pcs, isi 30 pcs -> tersimpan 2 DOS + 6 pcs).
     *
     * >>> GANTI KEPUTUSAN 2026-09-03 (GitHub #132) <<<
     * Keputusan PM 2026-08-27 tadinya "gulung di SETIAP simpan" (insert MAUPUN update, draft
     * ataupun langsung menunggu). Itu ternyata SALAH pada dokumen banyak-baris: tiap simpan
     * antara (staf menghitung produk lain lalu simpan lagi, atau sekadar menyimpan draft)
     * langsung menggulung angka mentah yang baru saja diketik dan MENGOSONGKAN kembali kolom
     * satuan kecilnya -- staf yang belum selesai menghitung melihat isiannya "hilang"/berubah
     * jadi satuan lain di tengah jalan (kasus nyata: SP0110, RCHK5LH 4 pcs berulang kali tergulung
     * jadi angka DOS yang jauh berbeda karena banyak simpan-antara sebelum diajukan).
     *
     * Sekarang: TIDAK PERNAH dipanggil dari update (StockController::updateStockOpname()) --
     * angka yang diketik staf dibiarkan APA ADANYA selama dokumen masih bisa diedit (draft
     * maupun koreksi sebelum ACC/tolak). Hanya dipanggil SATU KALI, pas dokumen baru benar-benar
     * "terbit" (bukan draft lagi): StockController::insertStockOpname() saat dibuat LANGSUNG
     * non-draft (satu-satunya jalur UI hari ini), dan StockController::submitStockOpname() saat
     * draft diajukan (.btn-ajukan, jalur yang belum dipasang di UI tapi sudah disiapkan).
     * Guard is_draft di bawah membuat panggilan dari insert saat draft (kalau UI draft-nya nanti
     * dipasang) jadi no-op dengan aman, konsisten dengan aturan ini.
     *
     * TIDAK PERNAH mengisi satuan yang lebih kecil dari satuan terkecil yang benar-benar diisi --
     * lihat UnitRollUp::collapse() untuk alasan lengkap (setara GitHub #78: jangan mengarang data
     * yang tidak pernah diperiksa staf).
     */
    public function rollUpUnits($sto): void
    {
        if (! $sto || $sto->is_old_version || $sto->is_draft) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)->groupBy('product_variant_id');
        // Gudang DOKUMEN, bukan gudang aktif sesi yang kebetulan menyimpan -- lihat
        // UnitRollUp::collapseProduct() untuk alasannya.
        $warehouseId = $sto->warehouse_id ?: null;

        // Keputusan user 2026-09-02: skema multi-gudang membatasi gudang ECERAN untuk cuma
        // menghitung/menyimpan retail_unit varian itu sendiri (lihat ProductVariant::
        // getProductVariant() dan StockOpnameRetailWarehouseUnitVisibilityTest) -- menggulung ke
        // satuan atas di sana bertabrakan langsung dengan aturan itu, karena satuan atas memang
        // SENGAJA tidak pernah ada/tersimpan di gudang eceran. Gudang utama tetap digulung seperti
        // biasa (catatan lama tetap berlaku di sana).
        if (self::isRetailWarehouse($warehouseId)) {
            return;
        }

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
     * Deteksi peluang gulung PENUH tanpa MENULIS apa pun -- gerbang konfirmasi dipanggil dari
     * StockController::insertStockOpname()/submitStockOpname() SEBELUM dokumen benar-benar
     * terbit (keputusan user 2026-09-04, bug report "93 Dos, 104 Piece": staf mengoreksi cuma
     * Dos, Piece dibiarkan 104 apa adanya -- itu benar per aturan GH #78, tapi user ingin
     * kesempatan menggulung PENUH ditawarkan secara eksplisit persis di titik dokumen terbit,
     * bukan otomatis).
     *
     * Membandingkan dua hasil gulung untuk tiap produk di dokumen:
     *   - "baseline": UnitRollUp::collapseProduct() -- gulung PARSIAL yang aman, cuma satuan
     *     yang staf isi sendiri yang ikut bergerak (ini yang akan ditulis kalau staf pilih
     *     "Batal" pada popup).
     *   - "full": UnitRollUp::collapseProductFull() -- gulung PENUH, termasuk melipat satuan
     *     yang TIDAK disentuh staf (mis. Piece) ke satuan yang disentuh (mis. Dos).
     * Kalau keduanya beda untuk satuan manapun, produk itu punya "peluang gulung" dan masuk
     * daftar yang ditampilkan di popup konfirmasi.
     *
     * Gudang ECERAN dikecualikan sama seperti rollUpUnits() -- alasannya sama persis.
     *
     * @return array<int, array{product_variant_id: int, product_name: string}>
     */
    public function detectRollupOpportunities($sto): array
    {
        if (! $sto || $sto->is_old_version) {
            return [];
        }

        $warehouseId = $sto->warehouse_id ?: null;
        if (self::isRetailWarehouse($warehouseId)) {
            return [];
        }

        $lines = StockOpnameLine::getLines($sto->sto_id)->groupBy('product_variant_id');
        if ($lines->isEmpty()) {
            return [];
        }

        $variants = ProductVariant::whereIn('product_variant_id', $lines->keys()->filter()->unique()->all())
            ->get()->keyBy('product_variant_id');
        $products = Product::whereIn('product_id', $variants->pluck('product_id')->filter()->unique()->all())
            ->get()->keyBy('product_id');

        $opportunities = [];

        foreach ($lines as $productVariantId => $group) {
            if (! $productVariantId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sol_counted_qty])->all();
            $existing = UnitRollUp::existingProductStockByUnit((int) $productVariantId, $warehouseId);

            $baselineByUnit = collect(UnitRollUp::collapseProduct((int) $productVariantId, $qtyByUnit, $warehouseId))
                ->pluck('qty', 'unit_id')->all();
            $fullByUnit = collect(UnitRollUp::collapseProductFull((int) $productVariantId, $qtyByUnit, $warehouseId))
                ->pluck('qty', 'unit_id')->all();

            $unitIds = array_unique(array_merge(array_keys($baselineByUnit), array_keys($fullByUnit)));
            $changed = false;
            foreach ($unitIds as $unitId) {
                $before = $baselineByUnit[$unitId] ?? $qtyByUnit[$unitId] ?? $existing[$unitId] ?? 0;
                $after = $fullByUnit[$unitId] ?? $before;
                if ((int) $before !== (int) $after) {
                    $changed = true;
                    break;
                }
            }

            if (! $changed) {
                continue;
            }

            $variant = $variants->get($productVariantId);
            $product = $variant ? $products->get($variant->product_id) : null;
            $name = trim(($product->product_name ?? 'produk#'.$productVariantId).' '.($variant->product_variant_name ?? ''));

            $opportunities[] = [
                'product_variant_id' => (int) $productVariantId,
                'product_name' => $name,
            ];
        }

        return $opportunities;
    }

    /**
     * Gulung PENUH: sama seperti rollUpUnits(), tapi lewat UnitRollUp::collapseProductFull() --
     * satuan yang TIDAK disentuh staf ikut dilipat ke satuan yang disentuh, bukan dibiarkan NULL.
     *
     * >>> HANYA dipanggil setelah staf mengonfirmasi lewat popup ("Lanjut") yang menampilkan hasil
     * detectRollupOpportunities() -- lihat StockController::insertStockOpname()/
     * submitStockOpname() untuk gerbangnya. TIDAK PERNAH otomatis, TIDAK PERNAH dari draft.
     * Jangan panggil ini dari update/simpan-antara mana pun -- alasannya sama persis dengan
     * rollUpUnits() (GitHub #132).
     */
    public function rollUpUnitsFull($sto): void
    {
        if (! $sto || $sto->is_old_version || $sto->is_draft) {
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
            $collapsed = UnitRollUp::collapseProductFull((int) $productVariantId, $qtyByUnit, $warehouseId);
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
