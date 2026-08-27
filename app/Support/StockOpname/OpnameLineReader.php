<?php

namespace App\Support\StockOpname;

use App\Models\ProductStock;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameLine;

/**
 * Satu-satunya pintu baca dokumen Stock Opname untuk TAMPILAN (PDF, halaman detail, daftar).
 * Dua sumber data di belakangnya, satu bentuk keluaran -- jadi template-nya cuma satu.
 *
 *   is_old_version = true  -> stock_opname_details (tiga longtext siap-cetak, token "-")
 *   is_old_version = false -> stock_opname_lines   (angka per satuan, counted NULL = tak dihitung)
 *
 * Dokumen lama TIDAK dimigrasikan dan tidak pernah ditulis ulang: cabang legacy meniru persis
 * perilaku hari ini, jadi cetakan dokumen lama tetap sama sampai kapan pun.
 *
 * DUA ATURAN YANG DIPEGANG DI SINI, bukan di blade:
 *
 * 1. SELISIH SELALU DITURUNKAN (counted - sistem), tidak pernah dibaca dari kolom tersimpan --
 *    juga untuk dokumen lama. Ini menutup bug SP0071 (baris MRHK1LM: sistem 3 DOS, real 8 DOS,
 *    selisih 5 DOS, tapi warnanya ikut flag yang bilang "tidak pernah dihitung") secara struktural:
 *    angka yang tercetak dan warna yang menyertainya berasal dari sumber yang sama persis.
 *
 * 2. HIGHLIGHT ikut ditentukan di sini ('yellow'|'green'|null). Blade tinggal mencetak. Dulu
 *    aturannya hidup di dua blade terpisah (Opname + OpnameBahan) dan sempat berbeda dari
 *    angkanya sendiri.
 *
 * Stok sistem untuk dokumen versi baru:
 *   belum diputuskan  -> dibaca LIVE tiap kali dibaca, TIDAK PERNAH disimpan (beda dari
 *                        refreshLiveSystemQty() lama yang justru menulis balik ke DB tiap PDF
 *                        di-download -- itu yang membuat selisih SP0071 bergeser diam-diam).
 *   sudah diputuskan  -> dari sol_system_qty_final yang dibekukan OpnameLifecycle saat keputusan.
 */
class OpnameLineReader
{
    public const STATUS_MENUNGGU = 1;

    /**
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function read($sto)
    {
        if (! $sto) {
            return collect();
        }

        return $sto->is_old_version ? $this->readLegacy($sto) : $this->readCurrent($sto);
    }

    /**
     * Bentuk payload halaman detail/edit (CreateStockOpname.js renderMode2()) untuk dokumen versi
     * BARU, dalam struktur yang SAMA PERSIS dengan yang sudah lama dipakai DetailStockOpname().
     *
     * Adaptor ini disengaja: berkat ini, pindah tulis ke stock_opname_lines TIDAK menuntut satu
     * baris pun perubahan JS. Frontend-nya bisa dimodernkan belakangan sebagai langkah terpisah,
     * bukan sebagai syarat cutover.
     *
     * real_qty null diteruskan apa adanya -- itu yang membuat satuan tak terhitung tetap terkirim
     * balik sebagai "tidak dihitung" saat dokumen disimpan ulang, bukan diam-diam jadi angka.
     *
     * @return array<int, array<string, mixed>>
     */
    public function legacyItems($sto): array
    {
        return $this->read($sto)->map(function (array $row) {
            $units = [];
            foreach ($row['units'] as $u) {
                $units[] = [
                    'unit_id' => $u['unit_id'],
                    'unit_short_name' => $u['unit'],
                    'system_qty' => $u['system'],
                    'real_qty' => $u['counted'],
                    'selisih_qty' => $u['selisih'],
                    'live_qty' => $u['live'],
                ];
            }

            return [
                'product_id' => $row['product_id'],
                'product_variant_id' => $row['product_variant_id'],
                'product_variant_sku' => $row['sku'],
                'pr_name' => $row['product_name'],
                'product_variant_name' => $row['variant_name'],
                'stod_notes' => $row['notes'] === '-' ? null : $row['notes'],
                'units' => $units,
                'stod_system' => $row['system_text'],
                'stod_real' => $row['real_text'],
                'stod_selisih' => $row['selisih_text'],
            ];
        })->values()->all();
    }

    /** Dokumen baru: satu baris per satuan di DB, digabung jadi satu baris per varian untuk tampil. */
    private function readCurrent($sto)
    {
        $lines = StockOpnameLine::getLines($sto->sto_id);
        if ($lines->isEmpty()) {
            return collect();
        }

        $pending = (int) $sto->status === self::STATUS_MENUNGGU;
        // Stok live selalu diambil: dipakai sebagai stok sistem saat dokumen masih menunggu, dan
        // sebagai petunjuk placeholder di halaman input untuk satuan yang belum dihitung. Dipin ke
        // gudang dokumen ini (bukan gudang aktif sesi pembaca) -- lihat liveStockMap().
        $live = $this->liveStockMap($lines, $sto->warehouse_id ?: null);

        return $lines
            ->groupBy('product_variant_id')
            ->map(function ($group) use ($pending, $live) {
                $first = $group->first();
                $units = [];

                foreach ($group as $line) {
                    $liveQty = (int) ($live->get($line->product_variant_id.'-'.$line->unit_id) ?? 0);
                    $system = $pending ? $liveQty : (int) ($line->sol_system_qty_final ?? 0);

                    $units[] = [
                        'unit' => $line->sol_unit_short_name ?? ('unit#'.$line->unit_id),
                        'unit_id' => $line->unit_id,
                        'system' => $system,
                        'live' => $liveQty,
                        'counted' => $line->sol_counted_qty === null ? null : (int) $line->sol_counted_qty,
                        'selisih' => $line->selisih($system),
                    ];
                }

                return $this->viewRow(
                    $first->sol_variant_sku,
                    $first->sol_product_name,
                    $first->sol_variant_name,
                    $first->sol_notes,
                    $units,
                    null,
                    $first->product_id,
                    $first->product_variant_id
                );
            })
            ->values();
    }

    /**
     * Dokumen lama: urai tiga string tersimpan. Satuan dicocokkan per NAMA, bukan per posisi --
     * urutan satuan kolom Sistem dan Real bisa berbeda (SP0071 baris SHPWW5L: sistem
     * "0 pcs, 0 DOS", real "0 DOS, 0 pcs"), dan mengurangkan per posisi akan mengarang selisih.
     *
     * Satuan yang tidak pernah dihitung (token "-") tetap ditampilkan "cocok dengan sistem"
     * (real = sistem, selisih 0) seperti perilaku sekarang -- tanda hubung telanjang terbaca
     * sebagai data hilang di atas kertas.
     */
    private function readLegacy($sto)
    {
        $details = StockOpnameDetail::getDetail(['sto_id' => $sto->sto_id]);

        return collect($details)->map(function ($item) {
            $systemMap = LegacyQtyString::parse($item->stod_system ?? '');
            $realMap = LegacyQtyString::parse($item->stod_real ?? '');

            $units = [];
            foreach ($realMap as $unit => $counted) {
                $system = (int) ($systemMap[$unit] ?? 0);
                $uncounted = $counted === null;

                $units[] = [
                    'unit' => $unit,
                    'unit_id' => null, // dokumen lama menyimpan nama satuan, bukan id-nya
                    'system' => $system,
                    'live' => $system,
                    // Dokumen lama tidak menyimpan "belum dihitung" per satuan secara andal, jadi
                    // di tampilan satuan tak terhitung disamakan dengan sistem (selisih 0).
                    'counted' => $uncounted ? $system : (int) $counted,
                    'selisih' => $uncounted ? 0 : ((int) $counted - $system),
                ];
            }

            return $this->viewRow(
                $item->product_variant_sku ?? null,
                $item->pr_name ?? null,
                $item->product_variant_name ?? null,
                $item->stod_notes ?? null,
                $units,
                // Satu-satunya sinyal "pernah diisi" yang dipunya dokumen lama.
                ! empty($item->stod_touched),
                $item->product_id ?? null,
                $item->product_variant_id ?? null
            );
        })->values();
    }

    /**
     * Bentuk baris siap-cetak. $legacyTouched hanya diisi cabang legacy; untuk dokumen baru
     * "pernah dihitung" adalah fakta yang bisa dibaca langsung dari datanya (counted !== null),
     * tidak perlu flag apa pun.
     */
    private function viewRow(?string $sku, ?string $product, ?string $variant, ?string $notes, array $units, ?bool $legacyTouched = null, $productId = null, $variantId = null): array
    {
        $counted = $legacyTouched ?? collect($units)->contains(fn ($u) => $u['counted'] !== null);
        $hasSelisih = collect($units)->contains(fn ($u) => (int) ($u['selisih'] ?? 0) !== 0);

        return [
            'product_id' => $productId,
            'product_variant_id' => $variantId,
            'sku' => $sku ?: '-',
            'product_name' => $product ?: '-',
            'variant_name' => $variant ?: '-',
            'notes' => $notes ?: '-',

            // Alias bernama-lama, supaya Backoffice/PDF/Opname.blade.php tetap SATU template untuk
            // kedua versi tanpa harus mengubah setiap ekspresinya. Cabang legacy sengaja TIDAK
            // dialihkan ke pembaca ini di jalur PDF -- dokumen lama tetap lewat jalur aslinya
            // supaya cetakannya benar-benar tidak bergeser sedikit pun.
            'product_variant_sku' => $sku ?: '-',
            'pr_name' => $product ?: '-',
            'product_variant_name' => $variant ?: '-',
            'stod_notes' => $notes ?: '-',
            'stod_system' => $this->text($units, 'system'),
            'stod_real' => $this->text($units, 'counted'),
            'stod_selisih' => $this->text($units, 'selisih'),
            'units' => $units,
            'system_text' => $this->text($units, 'system'),
            'real_text' => $this->text($units, 'counted'),
            'selisih_text' => $this->text($units, 'selisih'),
            'counted' => $counted,
            'has_selisih' => $hasSelisih,
            // Kuning kalau angkanya memang berselisih -- tidak pernah digantung pada flag apa pun,
            // supaya warna tidak bisa bertentangan dengan angka di baris yang sama (SP0071).
            // Hijau tetap butuh bukti "pernah dihitung": selisih 0 terlihat sama persis antara
            // baris yang dihitung dan yang dibiarkan kosong, itu memang tidak bisa disimpulkan.
            'highlight' => $hasSelisih ? 'yellow' : ($counted ? 'green' : null),
        ];
    }

    /** Rakit kolom siap-cetak: "8 DOS, 0 pcs". NULL (tidak dihitung) tercetak "-". */
    /**
     * GitHub #78 follow-up: satuan yang tidak dihitung TERCETAK "cocok dengan sistem" (stok
     * sistem / selisih 0), bukan tanda hubung telanjang -- di atas kertas "-" terbaca sebagai
     * data hilang, bukan "sengaja tidak dihitung". Ini murni kosmetik TAMPILAN: $units mentah
     * yang dipakai menentukan highlight/counted (viewRow()) dan real_qty yang dikirim balik ke
     * halaman edit (legacyItems()) tetap null apa adanya -- cuma string yang dicetak di sini
     * yang dihumanisasi. Untuk dokumen lama humanisasinya sudah terjadi lebih awal di
     * readLegacy() (di dalam $units itu sendiri), jadi di sini jadi no-op untuknya.
     */
    private function text(array $units, string $key): string
    {
        $parts = [];
        foreach ($units as $u) {
            $value = $u[$key];
            if ($value === null) {
                $value = $key === 'selisih' ? 0 : $u['system'];
            }
            $parts[] = $value.' '.$u['unit'];
        }

        return implode(', ', $parts);
    }

    /**
     * @return \Illuminate\Support\Collection<string, int> keyed "variantId-unitId"
     *
     * \$warehouseId pins the read to the document's OWN warehouse instead of ProductStock's
     * default "ambient session active warehouse" global scope -- whoever is reading this document
     * (viewing the PDF, opening the detail page) may have a *different* warehouse active in their
     * own session (fix/unit-conversion-coverage TODO, 2026-08-24; same class of bug as
     * StockOpnameDetail::getDetail(), fixed 2026-08-28 ahead of this merge).
     */
    private function liveStockMap($lines, ?int $warehouseId = null)
    {
        return ($warehouseId !== null
                ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : ProductStock::query())
            ->where('status', 1)
            ->whereIn('product_variant_id', $lines->pluck('product_variant_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->product_variant_id.'-'.$s->unit_id => (int) $s->ps_stock]);
    }
}
