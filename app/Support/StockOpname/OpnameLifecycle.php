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
     * Saklar utama popup konfirmasi gulung (keputusan user 2026-09-06): false = popup TIDAK
     * PERNAH tampil, apa pun kondisinya -- detectRollupOpportunities()/
     * detectRollupOpportunitiesFromPayload() keluar duluan sebelum logika deteksi apa pun jalan,
     * jadi tidak ada satu pun cara lain (rollup_decision, gudang, isi form) untuk memaksanya
     * tetap muncul selama flag ini false. true = ikuti kondisi deteksi yang sudah dibangun
     * (lihat buildRollupOpportunity()/computeFullProjectionChanges()) -- perilaku hari ini.
     *
     * Ubah nilai ini secara langsung di kode (bukan .env/config) -- sengaja "constant var" biasa
     * per permintaan user, bukan config yang bisa di-override runtime.
     */
    public const ROLLUP_PROJECTION_ENABLED = true;

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
     * Deteksi peluang gulung PENUH tanpa MENULIS apa pun, dari dokumen yang SUDAH tersimpan
     * (draft yang sudah pernah disimpan, dibaca dari stock_opname_lines). Bandingkan dengan
     * detectRollupOpportunitiesFromPayload() di bawah untuk dokumen yang BELUM tersimpan sama
     * sekali -- keduanya berbagi buildRollupOpportunity(), cuma sumber $qtyByUnit-nya beda.
     *
     * Dipakai StockController::submitStockOpname() sebagai jaring pengaman kalau frontend
     * memanggilnya tanpa lebih dulu melewati /previewStockOpnameRollup (lihat CreateStockOpname.js)
     * -- untuk alur UI normal, deteksi yang sebenarnya sudah selesai lewat payload SEBELUM draft-nya
     * sendiri disimpan (keputusan user 2026-09-05: jangan ada tulisan DB apa pun sampai staf
     * benar-benar menjawab popup).
     *
     * Gudang ECERAN dikecualikan sama seperti rollUpUnits() -- alasannya sama persis.
     *
     * @return array<int, array{product_variant_id: int, product_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}>
     */
    public function detectRollupOpportunities($sto): array
    {
        if (! self::ROLLUP_PROJECTION_ENABLED) {
            return [];
        }

        if (! $sto || $sto->is_old_version) {
            return [];
        }

        $warehouseId = $sto->warehouse_id ?: null;
        if (self::isRetailWarehouse($warehouseId)) {
            return [];
        }

        $flatLines = StockOpnameLine::getLines($sto->sto_id);
        if ($flatLines->isEmpty()) {
            return [];
        }
        $lines = $flatLines->groupBy('product_variant_id');

        $variants = ProductVariant::whereIn('product_variant_id', $lines->keys()->filter()->unique()->all())
            ->get()->keyBy('product_variant_id');
        $products = Product::whereIn('product_id', $variants->pluck('product_id')->filter()->unique()->all())
            ->get()->keyBy('product_id');
        // $flatLines (BUKAN $lines yang sudah di-groupBy) -- groupBy() membungkus tiap grup jadi
        // Collection tersendiri, jadi pluck('unit_id') di atasnya cuma menghasilkan array kosong.
        $unitNames = Unit::whereIn('unit_id', $flatLines->pluck('unit_id')->filter()->unique()->all())
            ->pluck('unit_short_name', 'unit_id');

        $opportunities = [];
        foreach ($lines as $productVariantId => $group) {
            if (! $productVariantId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sol_counted_qty])->all();
            $opportunity = $this->buildRollupOpportunity((int) $productVariantId, $qtyByUnit, $warehouseId, $variants, $products, $unitNames);
            if ($opportunity !== null) {
                $opportunities[] = $opportunity;
            }
        }

        return $opportunities;
    }

    /**
     * Kembaran detectRollupOpportunities() yang bekerja LANGSUNG dari payload frontend (bentuk
     * item[] yang sama dengan StockOpnameLine::writeFromPayload()), tanpa dokumen apa pun perlu
     * ada di database sama sekali -- "data bayangan" (keputusan user 2026-09-05): staf mengklik
     * "Tambah Stok Opname"/"Ajukan", browser mengirim isi form apa adanya ke sini murni untuk
     * dicek, TIDAK ADA StockOpname/StockOpnameLine yang ditulis. Baru kalau staf klik "Lanjut"
     * pada popup, browser mengirim ulang payload yang SAMA ke endpoint create/submit sungguhan
     * (StockController::insertStockOpname()/submitStockOpname()) dengan rollup_decision='full'.
     *
     * Dipanggil dari StockController::previewStockOpnameRollup() (route baru, read-only, tidak
     * ada DB::transaction() sama sekali di sekitarnya).
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product_variant_id: int, product_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}>
     */
    public function detectRollupOpportunitiesFromPayload(array $items, ?int $warehouseId): array
    {
        if (! self::ROLLUP_PROJECTION_ENABLED) {
            return [];
        }

        if (self::isRetailWarehouse($warehouseId)) {
            return [];
        }

        $byVariant = [];
        foreach ($items as $item) {
            $productVariantId = (int) ($item['product_variant_id'] ?? 0);
            if (! $productVariantId) {
                continue;
            }
            foreach (($item['units'] ?? []) as $unit) {
                $unitId = (int) ($unit['unit_id'] ?? 0);
                if (! $unitId) {
                    continue;
                }
                // array_key_exists, bukan ?? -- null di sini BERMAKNA "tidak dihitung", sama
                // seperti konvensi StockOpnameLine::upsertLine().
                $byVariant[$productVariantId][$unitId] = array_key_exists('real_qty', $unit) && $unit['real_qty'] !== null
                    ? (int) $unit['real_qty']
                    : null;
            }
        }

        if ($byVariant === []) {
            return [];
        }

        $variantIds = array_keys($byVariant);
        $variants = ProductVariant::whereIn('product_variant_id', $variantIds)->get()->keyBy('product_variant_id');
        $products = Product::whereIn('product_id', $variants->pluck('product_id')->filter()->unique()->all())
            ->get()->keyBy('product_id');
        $allUnitIds = collect($byVariant)->flatMap(fn ($units) => array_keys($units))->unique()->all();
        $unitNames = Unit::whereIn('unit_id', $allUnitIds)->pluck('unit_short_name', 'unit_id');

        $opportunities = [];
        foreach ($byVariant as $productVariantId => $qtyByUnit) {
            $opportunity = $this->buildRollupOpportunity($productVariantId, $qtyByUnit, $warehouseId, $variants, $products, $unitNames);
            if ($opportunity !== null) {
                $opportunities[] = $opportunity;
            }
        }

        return $opportunities;
    }

    /**
     * Inti perbandingan gulung PARSIAL (aman) vs PENUH untuk SATU produk, dipakai
     * detectRollupOpportunities()/detectRollupOpportunitiesFromPayload() -- lihat docblock
     * keduanya untuk konteks. Kalau keduanya beda di satuan manapun, kembalikan array peluang
     * (null kalau tidak ada apa-apa yang berubah).
     *
     * >>> KEPUTUSAN 2026-09-05 (dipertegas ulang hari yang sama, membatalkan fix sehari
     * sebelumnya yang sempat mengecualikan produk tak tersentuh): produk yang SAMA SEKALI tidak
     * disentuh staf TETAP dievaluasi -- ini SENGAJA, bukan celah. Niatnya: pada titik dokumen
     * benar-benar terbit, seluruh dokumen diperlakukan seakan sudah final -- stok sistem ditumpuk
     * oleh input staf KALAU ADA, lalu diproyeksikan/digulung untuk SETIAP produk di dokumen ini
     * (bukan cuma yang staf ketik). Ini justru dipakai untuk menangkap data lama yang sudah tidak
     * kanonik di database (mis. 104 pcs padahal 1 DOS = 12 pcs) walau staf tidak mengetik apa pun
     * untuk produk itu. Konsekuensinya: kelengkapan hasil ini bergantung pada $qtyByUnit yang
     * dikirim pemanggil MEWAKILI SELURUH KATALOG dokumen (bukan cuma baris yang diisi) -- lihat
     * CreateStockOpname.js's .btn-save/.btn-ajukan (keduanya sekarang memakai
     * collectStockOpnameItems(false), TIDAK sparse, untuk pratinjau gulung) supaya popup
     * konsisten baik dokumen baru maupun draft yang dibuka ulang.
     *
     * $changes[] diurutkan BESAR -> KECIL (keputusan user 2026-09-05: DOS di kiri, pcs di kanan
     * pada popup) lewat UnitRollUp::multipliersFromBottom() -- bukan urutan alami hasil collapse()
     * yang kebalikannya (kecil ke besar, dari cara carry naik dibangun).
     *
     * @param  array<int, int|null>  $qtyByUnit
     * @param  \Illuminate\Support\Collection  $variants  keyBy('product_variant_id')
     * @param  \Illuminate\Support\Collection  $products  keyBy('product_id')
     * @param  \Illuminate\Support\Collection  $unitNames  unit_id => unit_short_name
     * @return array{product_variant_id: int, product_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}|null
     */
    private function buildRollupOpportunity(int $productVariantId, array $qtyByUnit, ?int $warehouseId, $variants, $products, $unitNames): ?array
    {
        $changes = self::computeFullProjectionChanges($productVariantId, $qtyByUnit, $warehouseId);
        if ($changes === []) {
            return null;
        }

        foreach ($changes as &$change) {
            $change['unit_short_name'] = $unitNames->get($change['unit_id']) ?? ('unit#'.$change['unit_id']);
        }
        unset($change);

        $multipliers = UnitRollUp::multipliersFromBottom(UnitRollUp::productChain($productVariantId));
        usort($changes, fn ($a, $b) => ($multipliers[$b['unit_id']] ?? 0) <=> ($multipliers[$a['unit_id']] ?? 0));

        $variant = $variants->get($productVariantId);
        $product = $variant ? $products->get($variant->product_id) : null;
        $name = trim(($product->product_name ?? 'produk#'.$productVariantId).' '.($variant->product_variant_name ?? ''));

        return [
            'product_variant_id' => $productVariantId,
            'product_name' => $name,
            'changes' => $changes,
        ];
    }

    /**
     * Inti perbandingan "apakah produk ini punya perubahan NYATA antara apa yang tersimpan/
     * diketik APA ADANYA dan proyeksi gulung PENUH" -- diekstrak dari buildRollupOpportunity()
     * (2026-09-05) supaya rollUpUnitsFull() bisa memakai perbandingan yang SAMA PERSIS sebagai
     * gerbang tulis.
     *
     * >>> GANTI 2026-09-06 (keputusan user: "setiap kali ada roll up yang terdeteksi, munculkan
     * popup konfirmasinya") <<< Sebelumnya "before" di sini adalah hasil UnitRollUp::
     * collapseProduct() (gulung PARSIAL yang aman, SELALU jalan otomatis tanpa konfirmasi lewat
     * OpnameLifecycle::rollUpUnits()) -- popup cuma tampil kalau collapseProductFull() memberi
     * hasil BERBEDA dari itu. Bug yang menutup ini: mengisi satuan TERKECIL sendirian (mis. 1000
     * pcs, Dos dibiarkan kosong, 1 DOS = 12 pcs) SUDAH digulung otomatis lewat collapseProduct()
     * TANPA popup sama sekali -- collapseProduct() dan collapseProductFull() kebetulan
     * menghasilkan angka yang SAMA PERSIS untuk kasus ini (keduanya mulai menggulung dari satuan
     * yang sama, satuan terkecil), jadi "before" (baseline) === "after" (full), tidak ada
     * perbedaan untuk ditawarkan -- padahal jelas ADA gulungan yang terjadi (1000 pcs jadi
     * beberapa Dos + sisa pcs).
     *
     * Sekarang: "before" adalah nilai APA ADANYA (yang diketik staf, atau stok sistem kalau
     * satuan itu tidak diketik sama sekali) -- BUKAN hasil gulung parsial. Konsekuensinya,
     * OpnameLifecycle::rollUpUnits() (gulung parsial otomatis) TIDAK LAGI dipanggil dari
     * StockController::insertStockOpname()/submitStockOpname() sama sekali -- SETIAP gulung,
     * termasuk yang dulu "otomatis aman", sekarang lewat gerbang popup yang sama (lihat kedua
     * method itu). rollUpUnits() sendiri TIDAK dihapus (masih dipakai BahanOpnameLifecycle's
     * kembarannya dan diuji langsung sebagai unit di tests/Workflow/StockOpnameV2LifecycleTest.php)
     * -- cuma tidak lagi dipanggil dari kedua titik itu.
     *
     * @param  array<int, int|null>  $qtyByUnit
     * @return array<int, array{unit_id: int, before: int, after: int}>  kosong = tidak ada
     *         perubahan sama sekali untuk produk ini, aman dilewati apa adanya
     */
    private static function computeFullProjectionChanges(int $productVariantId, array $qtyByUnit, ?int $warehouseId): array
    {
        $existing = UnitRollUp::existingProductStockByUnit($productVariantId, $warehouseId);
        $fullByUnit = collect(UnitRollUp::collapseProductFull($productVariantId, $qtyByUnit, $warehouseId))
            ->pluck('qty', 'unit_id')->all();

        $unitIds = array_unique(array_merge(array_keys($qtyByUnit), array_keys($fullByUnit)));
        $changes = [];
        foreach ($unitIds as $unitId) {
            $before = $qtyByUnit[$unitId] ?? $existing[$unitId] ?? 0;
            $after = $fullByUnit[$unitId] ?? $before;
            if ((int) $before !== (int) $after) {
                $changes[] = [
                    'unit_id' => (int) $unitId,
                    'before' => (int) $before,
                    'after' => (int) $after,
                ];
            }
        }

        return $changes;
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
     *
     * >>> BUG DITEMUKAN USER 2026-09-05 (hari yang sama .btn-ajukan diganti ke katalog penuh):
     * versi lama menulis untuk SETIAP produk yang "collapseProductFull() mengembalikan sesuatu",
     * tapi collapseProductFull() SELALU mengembalikan sesuatu begitu chain-nya tidak kosong --
     * termasuk kredit {qty: 0} untuk produk yang stoknya sendiri sudah 0 di semua satuan dan
     * TIDAK ADA yang perlu digulung sama sekali. Efeknya: PDF dipenuhi baris "0 DOS, 0 pcs"
     * ter-highlight HIJAU (seakan "dihitung dan cocok") padahal staf tidak pernah menghitungnya --
     * baris yang seharusnya tetap NULL ("tidak dihitung", highlight kosong) malah tertimpa
     * sol_counted_qty=0. Fix: gerbang tulisnya sekarang computeFullProjectionChanges() -- PERSIS
     * perbandingan yang sama dipakai buildRollupOpportunity() untuk menentukan apa yang tampil di
     * popup -- jadi "Lanjut" menulis TEPAT apa yang ditampilkan popup, tidak lebih tidak kurang.
     * Produk yang tidak punya perubahan nyata (before === after untuk semua satuan) dilewati apa
     * adanya, baris NULL-nya tetap NULL.
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
            $changes = self::computeFullProjectionChanges((int) $productVariantId, $qtyByUnit, $warehouseId);
            if ($changes === []) {
                continue;
            }

            $first = $group->first();

            foreach ($changes as $change) {
                StockOpnameLine::upsertLine([
                    'sto_id' => $sto->sto_id,
                    'product_id' => $first->product_id,
                    'product_variant_id' => $productVariantId,
                    'unit_id' => $change['unit_id'],
                    'sol_counted_qty' => $change['after'],
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
