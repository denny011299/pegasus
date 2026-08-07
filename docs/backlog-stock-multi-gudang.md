# Backlog â€” Stok Multi-Gudang

Daftar perbaikan terkait stok, histori, dan konversi satuan. Update status saat selesai.

> **Anti-hilang merge:** inventaris penuh + checklist â†’ [`docs/fase2-merge-inventory.md`](fase2-merge-inventory.md).  
> Verifier: `php docs/scripts/verify-fase2-inventory.php`

## Alur Stock Transfer (keputusan bisnis)

```
Pending  â†’  Kirim  â†’  Terima (Terkirim)
              â”‚            â”‚
              â”‚            â””â”€ stok MASUK gudang tujuan
              â”‚               â€¢ tujuan UTAMA  â†’ satuan kirim apa adanya (tanpa konversi)
              â”‚               â€¢ tujuan ECERAN â†’ konversi ke retail_unit
              â”‚
              â””â”€ cek stok ekuivalen (utama: boleh unpack ancestor)
                 lalu potong; packing/rapikan tetap OFF

Cancel Kirim (hanya status Kirim, belum Terima):
  â†’ kembalikan stok gudang asal dari log Kirim
  â†’ gudang tujuan tidak berubah

Setelah Terima: tidak ada Cancel Kirim.
```

| Arah | Kirim | Terima |
|------|-------|--------|
| Utama â†’ Utama | Satuan dipilih; unpack ancestor OK, packing OFF | Sama satuan |
| Utama â†’ Eceran | Boleh DOS/default | â†’ `retail_unit` |
| Eceran â†’ Utama | Hanya Piece | Tetap Piece |

**ST produksi** (`source_type=production`): hasil ACC masuk gudang asal; ST Pending ke eceran. Validator produksi (#8). Packing off di Kirim (#6).

**Gudang eceran:** hanya `retail_unit`. Cleanup: `php artisan stock:cleanup-retail-units [--dry-run]` (#7).

| # | Item | Status | Catatan |
|---|------|--------|---------|
| 1 | **Histori stok:** log legacy (`warehouse_id` NULL, non-ST) hanya di gudang utama asli (id terkecil, tipe gudang besar) â€” bukan semua gudang utama baru | âœ… Selesai | `LogStock::isLegacyMainWarehouse()` |
| 2 | **Formatter qty konversi ST:** hilangkan noise desimal di FE (`14.0004`, `1.1667`) â€” simpan presisi di DB, tampilkan rapi | â³ Pending | `Stock_Transfer.js` â†’ `formatTransferQty()` â€” root cause fisik Terimaâ†’utama (konversi ke default / pecahan) sudah diganti aturan #11 (terima satuan kirim apa adanya). |
| 3 | **Histori gudang eceran:** filter log + saldo hanya `retail_unit` (jangan tampil DOS/Jerigen sisa data lama) | âœ… Selesai | `LogStock::applyRetailProductUnitFilter()` |
| 4 | **Pengembalian produk â†’ gudang eceran:** wajib satuan eceran saja (bukan DOS) | âœ… Selesai | FE + `CustomerProductReturnController::validateDetails()` |
| 5 | **Produksi â†’ gudang eceran:** ST Pending saat ACC; Kirim+Terima manual (bukan auto) | âŒ Dibatalkan | Auto Kirim+Terima pernah dicoba lalu di-revert â€” alur Pendingâ†’Kirimâ†’Terima sengaja dipertahankan |
| 6 | **ST Kirim:** packing/rapikan OFF; gudang utama boleh unpack ancestor | âœ… Selesai | Semua Kirim: `allow_packing=false`. Utama: `allow_unpack=true` (cek + potong ekuivalen). Eceran: unpack OFF. |
| 7 | **Gudang eceran diam-diam "bongkar" stok satuan besar (DOS/Jerigen) sisa data lama saat ST keluar** â€” melanggar aturan "eceran cuma pegang retail_unit"; log bongkar hasilnya malah disembunyikan oleh filter histori eceran (#3) | âœ… Selesai | `ProductUnitStock::warehouseIsMain()` + hardening di `totalAvailable()`/`checkItems()`/`deductQty()`: gudang eceran (`is_main_warehouse=0`) tidak pernah bongkar/pack lagi, `allowPacking` dipaksa `false` dan availability cuma menghitung stok di `targetUnitId` langsung. Data lama dibersihkan lewat `php artisan stock:cleanup-retail-units` (lihat Detail #7). |
| 8 | **`updateStockTransfer` tidak mengoper flag produksi ke `validateTransferItems`** â€” beda dengan `shipStockTransfer`/`checkItems` di fungsi yang sama yang sudah benar mengoper `$isProductionTransfer` | âœ… Selesai | `StockTransferController::updateStockTransfer()` sekarang mengoper `$header->source_type === 'production'` ke `validateTransferItems()`, sama seperti `shipStockTransfer` |
| 9 | **Cancel Kirim** restore stok satuan yang dipotong | âœ… Selesai | Reverse net delta dari `log_stocks` Kirim (termasuk log unpack ancestor). |
| 10 | **Belum ada automated test untuk Stock Transfer** (create/ship/accept/reject/cancel-kirim, matrix utama/eceran) | âœ… Selesai | `tests/Workflow/StockTransferWorkflowTest.php` |
| 11 | **Matrix Terima real-case** â€” utamaâ†’utama & eceranâ†’utama: satuan kirim apa adanya; utamaâ†’eceran: konversi ke `retail_unit` | âœ… Selesai | `resolveTransferUnits` + `accStockTransfer` tanpa split ke default. |

## Detail #7 (selesai)

- **Masalah:** `deductQty`/`totalAvailable` tidak pernah cek tipe gudang â€” kalau gudang eceran masih punya baris `product_stocks` di satuan besar (sisa sebelum aturan retail-only berlaku), fungsi ini akan ikut membongkarnya (mode non-packing, `ensure()` rekursif) begitu ST keluar dari eceran butuh lebih dari stok retail_unit langsung.
- **Bukti data lama (read-only saat investigasi):**
  - Warehouse `11` (eceran) varian `12`: DOS(7)=100 **+** Piece(9, retail_unit)=217 â€” dua baris hidup bersamaan.
  - Warehouse `11` varian `14`: DOS(7)=15 + Piece(9)=608.
  - Warehouse `14` (eceran) varian `15`: **hanya** DOS(7)=12, Piece(9)=0 â€” retail_unit-nya malah kosong, seluruh stok masih di DOS.
  - Relasi: `product_relations` varian ini `2â†’7 (x12)`, `7â†’9 (x12)` â€” DOS adalah ancestor Piece.
- **Keputusan bisnis:** gudang eceran **diblokir total** dari auto-unpack stok lama (bukan auto-unpack + tampilkan histori bongkar) â€” konsisten dengan aturan "eceran cuma pegang retail_unit". Stok lama yang masih di satuan besar harus dirapikan (konversi/di-zero) via command cleanup, bukan dibongkar diam-diam saat ST jalan.
- **Fix runtime hardening** (`ProductUnitStock`):
  - `warehouseIsMain(int $warehouseId): ?bool` â€” helper baru + cache, baca `warehouse_types.is_main_warehouse`.
  - `totalAvailable()`: kalau gudang eceran, skip packing plan dan skip ancestor-unit bongkar â€” availability cuma dari stok yang sudah persis di `targetUnitId`.
  - `checkItems()`: `allow_packing` dipaksa `false` untuk gudang eceran meski caller minta `true`.
  - `deductQty()`: gudang eceran memaksa `$allowPacking=false`; kalau stok di satuan yang diminta tidak cukup, langsung gagal dengan pesan jelas ("gudang eceran tidak boleh membongkar satuan besar") â€” tidak lagi jatuh ke `$ensure()` rekursif.
- **Fix data lama:** Artisan command `php artisan stock:cleanup-retail-units [--dry-run]` (lihat `App\Support\RetailStockCleanup`) â€” untuk setiap gudang `is_main_warehouse=0`, baris `product_stocks` yang `unit_id != product_variants.retail_unit` **dan** `retail_unit` sudah diatur: convert ke `retail_unit` via `ProductUnitStock::convertQty()` kalau ada rantai konversi (tambah ke baris retail, nolkan baris asal), atau nolkan kalau tidak sechain. Kalau `retail_unit` **belum** diatur untuk varian itu, baris **dilewati** (tidak di-nol-kan) â€” kita tidak tahu satuan retail yang benar, jadi stok yang ada bisa saja valid; command cuma melaporkan supaya staf atur `retail_unit`-nya dulu lalu jalankan ulang. Log jelas: "Cleanup stok eceran â†’ satuan retail". Jalankan dengan `--dry-run` dulu untuk lihat dampaknya sebelum eksekusi nyata.
- **Dijalankan `--dry-run` terhadap data nyata saat implementasi (read-only, tidak diubah):** akan mengonversi varian #12 & #14 (gudang #11, DOSâ†’Piece) dan varian #15 (gudang #14, DOSâ†’Piece); melewati varian #3 (gudang #14, Piece qty 21) karena `retail_unit`-nya belum diatur.
- **Test:** `tests/Workflow/StockTransferWorkflowTest::test_retail_warehouse_deduct_refuses_to_unpack_leftover_non_retail_stock`.

## Detail #8 (selesai)

- **Fix diterapkan** (`updateStockTransfer`):
  ```php
  $isProductionTransfer = $header->source_type === 'production';
  $matrix = $this->validateTransferItems(
      $payload['from_warehouse_id'],
      $payload['to_warehouse_id'],
      $items,
      $isProductionTransfer
  );
  ```
  Flag yang sama juga dioper ke `checkItems()`/`applySourceAvailabilityMode()` di dalam transaksi update, jadi seluruh alur edit ST produksi Pending konsisten pakai aturan produksi (bukan cuma matrix awal).
- **Test:** `tests/Workflow/StockTransferWorkflowTest::test_updating_a_pending_production_transfer_validates_with_production_unit_rules` â€” reproduksi persis skenario di mana aturan normal vs produksi berbeda hasil (kirim dalam satuan yang tidak masuk rantai konversi default, tujuan gudang utama): aturan produksi menerima apa adanya, aturan normal menolak dengan "rantai konversi".

## Detail #9 (selesai)

- **Contoh (skenario asli yang jadi acuan fix):** gudang utama punya Dos=100, Piece=217 (retail_unit lain, tidak relevan di sini â€” ini contoh gudang utamaâ†’utama). Kirim ST 300 Piece â†’ `deductPackedQty` merepacking seluruh chain: hasil akhir Dos=93, Piece=1 (total tetap 1417 piece-equivalent, hanya representasinya beda).
- **Fix** (`StockTransferController::restoreSourceStock`): baca semua baris `log_stocks` dengan `log_kode` = kode ST ini + `warehouse_id` = gudang asal (ditulis saat Kirim), hitung **net delta per (varian, satuan)** (`log_category=1` â†’ `+`, `log_category=2` â†’ `-`), lalu restore dengan kebalikannya (`addQty`/`deductQty`) per satuan. Ini menangkap seluruh efek Kirim apapun bentuknya (packing/non-packing) â€” bukan cuma `addQty` ke satuan yang dikirim. Kalau tidak ada log Kirim sama sekali (data lama), fallback ke `addQty` satuan kirim seperti sebelumnya.
- **Fix pendukung wajib** (`ProductUnitStock::deductPackedQty`): sebelum fix ini, log yang ditulis saat packing memakai baseline "canonical repack tanpa deduksi" (`canonicalBefore`) untuk menghitung delta, bukan `plan['before']` vs `plan['after']` yang sebenarnya. Kalau komposisi sebelum Kirim **sudah** canonical (baseline = before, delta selalu 0), tidak ada log sama sekali untuk satuan selain satuan yang di-ship â€” padahal `ps_stock`-nya berubah (mis. contoh 5 Dos/2 Piece di test, Dos ikut turun tapi tidak pernah masuk log). Kalau **belum** canonical (contoh Dos=100/Piece=217 di atas), baseline `canonicalBefore` malah beda arah/besaran dari pergerakan fisik asli. Kedua kasus membuat net-delta-dari-log di atas jadi salah. **Sekarang:** log 1 baris per satuan yang delta-nya `plan['after'] - plan['before']` (asli) != 0 â€” jumlah log per satuan dijamin sama dengan pergerakan fisik `ps_stock`, sehingga net-delta reversal di atas akurat.
- **Test:** `tests/Workflow/StockTransferWorkflowTest::test_cancel_kirim_restores_the_exact_pre_ship_unit_composition_after_packing` â€” ship 22 Piece dari 5 Dos + 2 Piece (unpack 2 Dos di tengah jalan), Cancel Kirim harus balik ke persis 5 Dos + 2 Piece.

## Detail #11 (selesai â€” real-case matrix)

- **Utama â†’ Utama / Eceran â†’ Utama:** Terima = satuan kirim (`sentUnitId`), tanpa `defaultUnitId` / tanpa `splitWholeAndRemainder`.
- **Utama â†’ Eceran:** Terima konversi ke `retail_unit`.
- **Kirim:** `allow_packing=false`; gudang utama `allow_unpack=true` (bongkar ancestor bila stok satuan kirim kurang); eceran unpack OFF.
- **Test:** `test_receiving_into_a_main_warehouse_keeps_sent_unit_without_conversion`, `test_receiving_into_retail_converts_to_retail_unit`, `test_cancel_kirim_restores_sent_unit_stock_without_packing`, `test_ship_unpacks_ancestor_when_sent_unit_stock_is_short`.

## Detail #1 (selesai)

- **Masalah:** Gudang utama baru (mis. Gudang Pusat Surabaya) ikut menampilkan ~58rb log lama tanpa `warehouse_id`.
- **Fix:** Legacy non-ST hanya untuk gudang utama **pertama** (`warehouses.id` terkecil dengan `is_main_warehouse = 1`, status aktif).
- **Tetap:** Log dengan `warehouse_id` eksplisit + legacy ST (kode `ST%`) tetap difilter per gudang seperti sebelumnya.

## Detail #3 (selesai)

- **Masalah:** Histori gudang eceran menampilkan log & saldo DOS/Jerigen (sisa data lama / multi-satuan).
- **Fix:** Jika gudang aktif bukan gudang utama (`is_main_warehouse = 0`) dan `log_type = 1` (produk), filter `unit_id = product_variants.retail_unit`; saldo histori juga hanya dari baris stok satuan eceran.

## Detail #4 (selesai)

- **Masalah:** Pengembalian produk jadi ke gudang eceran bisa pakai DOS â†’ log tidak muncul di histori eceran (filter #3).
- **Fix:** Gudang eceran (`is_main_warehouse = 0`) wajib `unit_id = retail_unit` â€” FE filter dropdown satuan + validasi BE di `validateDetails()`.

## Detail #5 (dibatalkan â€” alur Pending disengaja)

- **Kebutuhan bisnis:** ACC produksi hanya buat ST **Pending** (status 1); stok hasil masuk gudang asal. User wajib **Kirim** lalu **Terima** manual agar stok masuk histori gudang eceran.
- **Catatan:** Auto Kirim+Terima via `completeProductionTransfer()` pernah ditambahkan lalu **di-revert** â€” jangan reintroduce tanpa persetujuan eksplisit.

## Detail #6 (selesai â€” update unpack)

- **Masalah (awal):** Kirim ST hasil produksi menulis banyak log packing/rapikan di gudang utama.
- **Fix packing:** semua Kirim `allow_packing=false` (tidak merapikan seluruh gudang).
- **Update unpack:** gudang utama source `allow_unpack=true` â€” cek + Kirim boleh bongkar ancestor (mis. Jerigenâ†’DOS, DOSâ†’Piece) agar available ekuivalen; eceran tetap unpack OFF. Packing tetap OFF.
- **FE/API:** `sourceSnapshot.available_qty`, `checkTransferStock`, dan `shipStockTransfer` memakai mode yang sama.

## Referensi kode

- Histori: `app/Models/LogStock.php`, endpoint `/getLog`
- Stok produk: `StockController::getStock()`, `Stock_Product.js`
- Konversi ST: `ProductUnitStock.php`, `StockTransferController.php`, `Stock_Transfer.js`
