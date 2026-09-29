# Migrasi satuan IPM → PMO (per step)

**Mapping:** `7 DOS → 126 Dus` · `9 Piece → 127 Pcs`

## Urutan full reset (dari dump DEV4)

1. Restore DB dari `u906028329_dev (4).sql`
2. **Unit dulu:** file ini `ALL_IN_ONE_phpmyadmin.sql` → cek SELECT → `COMMIT;`
3. **SKU:** `../sync-sku-duplicates/ALL_IN_ONE_phpmyadmin.sql` (keep IPM + pasang ref, auto-COMMIT)

## phpMyAdmin (disarankan)

Pakai **`ALL_IN_ONE_phpmyadmin.sql`** — satu kali paste/Go. TEMP + transaksi tidak putus.  
Checksum gagal → script **stop** (error guard). Akhir: cek SELECT → `COMMIT;` atau `ROLLBACK;` (tidak auto-commit).

Backup DB dulu. Hanya di **DEV**, bukan production.

## Urutan jalan (file per-step / debug)

| # | File | Mutasi data? |
|---|------|--------------|
| 0 | `00_setup_session.sql` | Tidak (set session) |
| 1 | `01_dry_run.sql` | Temp table saja + SELECT |
| 2 | `02_begin_checksum.sql` | `START TRANSACTION` + snapshot |
| 3 | `03_product_stocks.sql` | **Ya** — stok produk |
| 4 | `04_supplies_stocks.sql` | **Ya** — stok bahan |
| 5 | `05_checksum_verify.sql` | Baca checksum — **STOP jika gagal** |
| 6 | `06_product_relations.sql` | **Ya** — relasi satuan |
| 7 | `07_remap_operational_fks.sql` | **Ya** — SO/ST/PO/dll |
| 8 | `08_deactivate_legacy_units.sql` | **Ya** — nonaktif unit lama |
| 9 | `09_verify_finalize.sql` | SELECT verifikasi + restore FK checks |

Lalu: `COMMIT;` kalau semua OK, atau `ROLLBACK;` kalau ragu.

---

## Rangkuman tiap file

### `00_setup_session.sql`
**Untuk apa:** Siapkan koneksi (charset + matikan cek FK/unique sementara).  
**Dilakukan:** `SET NAMES`, simpan & set `FOREIGN_KEY_CHECKS=0`, `UNIQUE_CHECKS=0`.  
**Tabel diubah:** — (tidak ada)

### `01_dry_run.sql`
**Untuk apa:** Buat peta remap + lihat dampak **tanpa** mengubah data bisnis.  
**Dilakukan:** Buat `tmp_unit_remap` (7→126, 9→127); SELECT unit target; hitung baris/qty terdampak; daftar konflik stok yang akan di-merge.  
**Tabel diubah:** — (hanya TEMP `tmp_unit_remap`)  
**Tabel dibaca:** `units`, `product_stocks`, `supplies_stocks`, `log_stocks`, `sales_order_details`, `stock_transfer_details`, `product_variants`, `product_relations`

### `02_begin_checksum.sql`
**Untuk apa:** Mulai transaksi aman + catat total stok sebelum ubah.  
**Dilakukan:** `START TRANSACTION`; buat `tmp_stock_checksum`; isi `qty_before` untuk product + supplies.  
**Tabel diubah:** — (TEMP `tmp_stock_checksum`)  
**Tabel dibaca:** `product_stocks`, `supplies_stocks`

### `03_product_stocks.sql`
**Untuk apa:** Alihkan stok produk ke satuan PMO tanpa kehilangan qty.  
**Dilakukan:**  
1. Merge qty (jumlah) kalau DOS+Dus / Piece+Pcs hidup di gudang+varian sama  
2. Hapus baris legacy yang sudah ter-merge  
3. Remap sisa `unit_id` from → to  
**Tabel diubah:** `product_stocks` (`unit_id`, `ps_stock`, `ps_safety_stock`, `ps_alert_stock`, `status`, `updated_at`)

### `04_supplies_stocks.sql`
**Untuk apa:** Sama seperti step 3, untuk stok bahan mentah.  
**Dilakukan:** Merge → delete pasangan → remap `unit_id`.  
**Tabel diubah:** `supplies_stocks` (`unit_id`, `ss_stock`, `status`, `updated_at`)

### `05_checksum_verify.sql`
**Untuk apa:** Pastikan total stok tidak hilang/bertambah.  
**Dilakukan:** Hitung `qty_after`; bandingkan dengan `qty_before`; tampilkan `STOCK CHECKSUM OK` atau **GAGAL**.  
**Tabel diubah:** TEMP `tmp_stock_checksum` (`qty_after`)  
**Kalau gagal:** jalankan `ROLLBACK;` — jangan lanjut step 6–8.

### `06_product_relations.sql`
**Untuk apa:** Relasi konversi satuan ikut unit PMO.  
**Dilakukan:** Remap `pr_unit_id_1` / `pr_unit_id_2`; hapus duplikat pair.  
**Tabel diubah:** `product_relations`

### `07_remap_operational_fks.sql`
**Untuk apa:** Semua dokumen operasional ikut unit PMO.  
**Dilakukan:** `UPDATE … SET unit_id = to` (dan kolom terkait).  
**Tabel diubah:**
| Tabel | Kolom |
|-------|--------|
| `log_stocks` | `unit_id` |
| `sales_order_details` | `unit_id` |
| `sales_delivery_orders_details` | `unit_id` |
| `stock_transfer_details` | `unit_id`, `received_unit_id` |
| `purchase_orders_details` | `unit_id` |
| `production_details` | `unit_id` |
| `product_issues_details` | `unit_id` |
| `customer_product_return_details` | `unit_id` |
| `customer_supply_return_details` | `unit_id` |
| `return_supplies_detail` | `unit_id` |
| `stock_opname_lines` | `unit_id` |
| `stock_opname_bahan_lines` | `unit_id` |
| `product_variants` | `unit_id`, `retail_unit`, `safety_unit_id` |
| `bom_details` | `unit_id` |

### `08_deactivate_legacy_units.sql`
**Untuk apa:** Matikan satuan IPM lama; pastikan Dus/Pcs PMO aktif.  
**Dilakukan:** `status=0` untuk unit 7 & 9 (+ junk IPMTEST); rename/aktifkan 126 & 127.  
**Tabel diubah:** `units`

### `09_verify_finalize.sql`
**Untuk apa:** Cek akhir + kembalikan setting session.  
**Dilakukan:** SELECT leftover stok (harus 0); cek status unit 7/9/126/127; tampilkan delta checksum; restore FK/unique checks.  
**Tabel diubah:** —  
**Setelah OK:** ketik `COMMIT;` manual. Kalau ragu: `ROLLBACK;`

---

## Ringkas: tabel yang kena UPDATE/DELETE

| Tabel | Step |
|-------|------|
| `product_stocks` | 03 |
| `supplies_stocks` | 04 |
| `product_relations` | 06 |
| `log_stocks` | 07 |
| `sales_order_details` | 07 |
| `sales_delivery_orders_details` | 07 |
| `stock_transfer_details` | 07 |
| `purchase_orders_details` | 07 |
| `production_details` | 07 |
| `product_issues_details` | 07 |
| `customer_product_return_details` | 07 |
| `customer_supply_return_details` | 07 |
| `return_supplies_detail` | 07 |
| `stock_opname_lines` | 07 |
| `stock_opname_bahan_lines` | 07 |
| `product_variants` | 07 |
| `bom_details` | 07 |
| `units` | 08 |
