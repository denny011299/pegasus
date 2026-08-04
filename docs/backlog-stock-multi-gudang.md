# Backlog — Stok Multi-Gudang

Daftar perbaikan terkait stok, histori, dan konversi satuan. Update status saat selesai.

| # | Item | Status | Catatan |
|---|------|--------|---------|
| 1 | **Histori stok:** log legacy (`warehouse_id` NULL, non-ST) hanya di gudang utama asli (id terkecil, tipe gudang besar) — bukan semua gudang utama baru | ✅ Selesai | `LogStock::isLegacyMainWarehouse()` |
| 2 | **Formatter qty konversi ST:** hilangkan noise desimal di FE (`14.0004`, `1.1667`) — simpan presisi di DB, tampilkan rapi | ⏳ Pending | `Stock_Transfer.js` → `formatTransferQty()` |
| 3 | **Histori gudang eceran:** filter log + saldo hanya `retail_unit` (jangan tampil DOS/Jerigen sisa data lama) | ✅ Selesai | `LogStock::applyRetailProductUnitFilter()` |
| 4 | **Pengembalian produk → gudang eceran:** wajib satuan eceran saja (bukan DOS) | ✅ Selesai | FE + `CustomerProductReturnController::validateDetails()` |
| 5 | **Produksi → gudang eceran:** ST Pending saat ACC; Kirim+Terima manual (bukan auto) | ❌ Dibatalkan | Auto Kirim+Terima pernah dicoba lalu di-revert — alur Pending→Kirim→Terima sengaja dipertahankan |

## Detail #1 (selesai)

- **Masalah:** Gudang utama baru (mis. Gudang Pusat Surabaya) ikut menampilkan ~58rb log lama tanpa `warehouse_id`.
- **Fix:** Legacy non-ST hanya untuk gudang utama **pertama** (`warehouses.id` terkecil dengan `is_main_warehouse = 1`, status aktif).
- **Tetap:** Log dengan `warehouse_id` eksplisit + legacy ST (kode `ST%`) tetap difilter per gudang seperti sebelumnya.

## Detail #3 (selesai)

- **Masalah:** Histori gudang eceran menampilkan log & saldo DOS/Jerigen (sisa data lama / multi-satuan).
- **Fix:** Jika gudang aktif bukan gudang utama (`is_main_warehouse = 0`) dan `log_type = 1` (produk), filter `unit_id = product_variants.retail_unit`; saldo histori juga hanya dari baris stok satuan eceran.

## Detail #4 (selesai)

- **Masalah:** Pengembalian produk jadi ke gudang eceran bisa pakai DOS → log tidak muncul di histori eceran (filter #3).
- **Fix:** Gudang eceran (`is_main_warehouse = 0`) wajib `unit_id = retail_unit` — FE filter dropdown satuan + validasi BE di `validateDetails()`.

## Detail #5 (dibatalkan — alur Pending disengaja)

- **Kebutuhan bisnis:** ACC produksi hanya buat ST **Pending** (status 1); stok hasil masuk gudang asal. User wajib **Kirim** lalu **Terima** manual agar stok masuk histori gudang eceran.
- **Catatan:** Auto Kirim+Terima via `completeProductionTransfer()` pernah ditambahkan lalu **di-revert** — jangan reintroduce tanpa persetujuan eksplisit.

## Referensi kode

- Histori: `app/Models/LogStock.php`, endpoint `/getLog`
- Stok produk: `StockController::getStock()`, `Stock_Product.js`
- Konversi ST: `ProductUnitStock.php`, `StockTransferController.php`, `Stock_Transfer.js`
