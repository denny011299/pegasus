# SOP — Inventaris Fitur fase-2 (anti-hilang saat merge)

**Tujuan:** catalog semua kerja fase multi-gudang / ST / produksi / modal SOP, supaya sebelum & sesudah merge ke `main` (atau rebase) agent/manusia bisa **cek cepat** mana yang hilang.

**Branch sumber kebenaran:** `fase-2` (= tip yang sudah di-push ke `fase2/main`)  
**Snapshot tip saat dokumen ini ditulis:** `a5103d2`  
**Dokumen terkait:**
- `docs/backlog-stock-multi-gudang.md` — keputusan bisnis ST + backlog #1–#11
- `docs/production-acc-stock-safety.md` — ACC produksi + ST Pending
- `docs/production-pallet-shortcut.md` — shortcut Pallet di form produksi
- `.cursor/rules/modal-structure.mdc` — **jangan** resolve conflict ke monolith modal
- `.cursor/rules/modal-footer-actions.mdc` — urutan footer Batal → aksi

**Cek otomatis:** jalankan `php docs/scripts/verify-fase2-inventory.php` dari root repo (exit 0 = OK).

**Cadangan file fisik:** `docs/fase2-snapshots/<sha>/` (salinan path kritis + 49 modal).  
Refresh: `php docs/scripts/snapshot-fase2-files.php`  
Zip penuh tip (lokal, gitignored): `php docs/scripts/snapshot-fase2-files.php --with-zip`  
Panduan restore: `docs/fase2-snapshots/README.md`

---

## 0. Cara pakai (wajib sebelum merge)

1. Catat tip `fase-2` / `fase2/main` **sebelum** merge:
   ```bash
   git rev-parse fase-2 fase2/main
   git log --oneline -5 fase-2
   ```
2. Jalankan verifier:
   ```bash
   php docs/scripts/verify-fase2-inventory.php
   ```
3. Kalau merge conflict di `modal-popup.blade.php` / `modals/**`: ikuti `modal-structure.mdc` — **keep structure include**, jangan ambil monolith.
4. Setelah merge, ulang langkah 2. Fail = ada yang hilang → restore dari tip pre-merge (`git show <sha>:path`).
5. Jalankan smoke test kritis:
   ```bash
   php artisan test --filter=StockTransferWorkflowTest
   php artisan test --filter=ProductUnitStock
   ```

---

## 1. Ringkasan fitur (apa yang dibangun)

| Area | Fitur | Status |
|------|--------|--------|
| Master | Gudang + Tipe Gudang CRUD | ✅ |
| Master | Active warehouse (navbar) | ✅ |
| Master | Staff ↔ warehouse assign | ✅ |
| Stok | Multi-unit per gudang (`ProductUnitStock`) | ✅ |
| Stok | Packing / unpack (utama only); eceran **tidak** unpack | ✅ |
| Stok | `retail_unit` + cleanup artisan | ✅ |
| ST | Pending → Kirim → Terima; Cancel Kirim; Tolak | ✅ |
| ST | Matrix utama↔utama / utama→eceran / eceran→utama | ✅ |
| ST | Inline setup satuan eceran (Swal + dropdown row) | ✅ |
| ST | ST dari produksi (`source_type=production`) | ✅ |
| ST | FE: modal loading spinner, header hijau confirm / biru edit | ✅ |
| Produksi | ACC aman (transaction); hasil → gudang asal + ST Pending bila perlu | ✅ |
| Produksi | Shortcut Pallet (`qty_per_pallet`) | ✅ |
| Pengiriman | Pengembalian bahan (CSR) + produk jadi (CPR) | ✅ |
| Pengiriman | SO / pengiriman dengan warehouse + satuan eceran | ✅ |
| UI | 49 modal terpisah + `pg-modal` SOP (form/confirm/danger) | ✅ |
| UI | Tombol ACC = `fe-check-circle`; Tolak = solid red gradient | ✅ |
| API | External API warehouse / warehouse type (fase terkait) | ✅ |
| Test | `StockTransferWorkflowTest` + packing/split unit tests | ✅ |

**Jangan reintroduce tanpa izin:** auto Kirim+Terima ST produksi (`completeProductionTransfer`) — sengaja di-revert (backlog #5).

---

## 2. File kritis (harus ada setelah merge)

### 2.1 Backend core

| Path | Kenapa |
|------|--------|
| `app/Support/ProductUnitStock.php` | packing/unpack, eceran no-unpack, convert |
| `app/Support/RetailStockCleanup.php` | cleanup stok eceran non-retail |
| `app/Http/Controllers/StockTransferController.php` | ship/acc/reject/cancel + retail setup |
| `app/Http/Controllers/WarehouseController.php` | CRUD + `setActiveWarehouse` |
| `app/Http/Controllers/CustomerProductReturnController.php` | CPR + validasi eceran |
| `app/Http/Controllers/CustomerSupplyReturnController.php` | CSR |
| `app/Models/Warehouse.php` | |
| `app/Models/WarehouseType.php` | `is_main_warehouse` |
| `app/Models/StockTransfer.php` | |
| `app/Models/StockTransferDetail.php` | `received_unit_id` dsb |
| `app/Models/StaffWarehouse.php` | assign staff |
| `app/Models/CustomerProductReturn.php` (+ Detail) | |
| `app/Models/CustomerSupplyReturn.php` (+ Detail) | |

### 2.2 Marker string (cari di file — kalau hilang = regresi)

| File | Marker / simbol wajib |
|------|------------------------|
| `StockTransferController.php` | `shipStockTransfer`, `accStockTransfer`, `restoreSourceStock`, `resolveTransferUnits`, `getTransferRetailUnitSetup`, `saveTransferRetailUnit`, `allow_packing` |
| `ProductUnitStock.php` | `warehouseIsMain`, `deductQty`, `deductPackedQty`, `convertQty` |
| `ProductionController.php` | `accProduction`, `source_type` / ST Pending (lihat juga docs production-acc) |
| `Stock_Transfer.js` | `promptRetailUnitForTransfer`, `saveRetailUnitForTransfer`, `setTransferModalLoading`, `setTransferModalMode`, `loadTransferDetailForEdit` |
| `Production.js` | `setProductionModalMode`, `fe-check-circle`, `__PALLET__` (pallet) |
| `pg-modal-styles.blade.php` | `.pg-btn-accept`, `.pg-btn-decline`, `.pg-modal--confirm`, `.is-loading` |
| `modal-popup.blade.php` | hanya `@include('components.modals...` — **bukan** 4000+ baris HTML modal inline |

### 2.3 Frontend halaman

| Path |
|------|
| `public/Custom_js/Backoffice/Inventory/Stock_Transfer.js` |
| `public/Custom_js/Backoffice/Warehouse/Warehouse.js` |
| `public/Custom_js/Backoffice/Warehouse/WarehouseType.js` |
| `public/Custom_js/Backoffice/Production/Production.js` |
| `public/Custom_js/Backoffice/Customers/Sales_Order.js` |
| `public/Custom_js/Backoffice/Customers/Customer_Supply_Return.js` |
| `public/Custom_js/Backoffice/Customers/Customer_Product_Return.js` |
| `public/Custom_js/Backoffice/Reports/ReportStockTransfer.js` |
| `resources/views/Backoffice/Inventory/Stock_Transfer.blade.php` |
| `resources/views/Backoffice/Warehouse/Warehouse.blade.php` |
| `resources/views/Backoffice/Warehouse/WarehouseType.blade.php` |
| `resources/views/layout/partials/pg-modal-styles.blade.php` |

### 2.4 Modal pecahan (49 file) — folder wajib hidup

`resources/views/components/modals/` harus berisi **≥ 45** `.blade.php`.

**ST (wajib 4):**
- `stock-transfer/add-stock-transfer.blade.php` — form + detail pending + loading
- `stock-transfer/accept-stock-transfer.blade.php` — Terima Transfer
- `stock-transfer/view-stock-transfer.blade.php`
- `stock-transfer/reject-production-transfer.blade.php`

**New-theme / ACC terkait:**
- `production/add-production.blade.php`
- `sales-order/add-sales-order.blade.php`
- `warehouse/add-warehouse.blade.php`
- `warehouse-type/add-warehouse-type.blade.php`
- `stock-product/add-stock-product.blade.php`
- `stock-product/modal-safety-stock.blade.php`
- `stock-supplies/add-stock-supplies.blade.php`
- `shared/modal-konfirmasi.blade.php`, `modal-photo.blade.php`, `modal-delete.blade.php`, `modal-danger.blade.php`

`modal-popup.blade.php` ≈ **200 baris** (include only). Kalau tiba-tiba > 1000 baris → monolith kembali = **FAIL**.

### 2.5 Routes (cuplikan wajib di `routes/web.php`)

- `/stockTransfer`, ship/acc/reject/cancel endpoints ST
- `/getTransferRetailUnitSetup`, `/saveTransferRetailUnit`
- `/setActiveWarehouse` (dan alias `/set-active-warehouse`)
- `/customerSupplyReturns*`, `/customerProductReturns*` (CRUD + accept/decline)
- Gudang / Tipe Gudang CRUD routes

### 2.6 Migrations / SQL / Artisan

| Path / command |
|----------------|
| Migrations warehouse, `retail_unit`, stock transfer remap status, `received_unit_id`, `qty_per_pallet`, SO warehouse_id |
| `database/sql/stock_transfers.sql`, `warehouse_schema.sql`, `*_warehouse*.sql` |
| `php artisan stock:cleanup-retail-units [--dry-run]` |

### 2.7 Tests

| Path | Isi penting |
|------|-------------|
| `tests/Workflow/StockTransferWorkflowTest.php` | 6 case: production update rules, cancel kirim, ship no-unpack, receive retail, receive main keep unit, retail deduct no unpack |
| `tests/Unit/ProductUnitStockPackingTest.php` | |
| `tests/Unit/ProductUnitStockSplitTest.php` | |
| `tests/DatabaseTransaction/ProductionApprovalAtomicityTest.php` | |
| `tests/Workflow/Production*` / related | ACC / cancel / unit conversion |

### 2.8 Cursor rules

| Path |
|------|
| `.cursor/rules/modal-structure.mdc` |
| `.cursor/rules/modal-footer-actions.mdc` |
| `.cursor/rules/fase2-merge-inventory.mdc` |

---

## 3. Aturan bisnis yang tidak boleh “ter-merge hilang”

Salin dari backlog — verifikasi lewat test + code review:

1. **Kirim ST:** `allow_packing=false`; stok di satuan kirim harus cukup (utama **tidak** auto-unpack DOS).
2. **Terima utama:** satuan = satuan kirim (tanpa konversi ke default).
3. **Terima eceran:** konversi ke `retail_unit`.
4. **Eceran:** tidak pernah unpack satuan besar; cleanup via artisan.
5. **Cancel Kirim:** hanya status Kirim; restore dari log Kirim.
6. **ACC produksi:** transaction-safe; ST Pending manual Kirim/Terima (bukan auto complete).
7. **Setup retail inline** di ST FE — jangan paksa user ke menu Produk.
8. **Modal** tetap pecahan 49 file.

---

## 4. Checklist manual UI (setelah merge)

- [ ] Navbar: ganti active warehouse → reload OK
- [ ] Master Gudang / Tipe Gudang: list + tambah
- [ ] ST Pending: klik eye → **modal muncul segera** + spinner; footer aksi disabled saat loading
- [ ] ST Pending view: header **hijau**; klik Edit Data → header **biru**
- [ ] ST ke eceran, produk tanpa `retail_unit` → popup / dropdown pilih satuan eceran
- [ ] ST Terima: tombol hijau + `fe-check-circle`
- [ ] Produksi Konfirmasi: header hijau ACC, Terima Produksi pakai `check-circle`
- [ ] Tolak footer: solid merah (bukan outline glass)
- [ ] CSR/CPR accept/decline masih jalan
- [ ] `modal-popup.blade.php` masih tipis (include-only)

---

## 5. Restore cepat kalau ada yang hilang

### Dari folder snapshot (disarankan)

```powershell
# satu file
Copy-Item -Force docs/fase2-snapshots/a5103d2/app/Support/ProductUnitStock.php app/Support/ProductUnitStock.php

# seluruh modal
Copy-Item -Recurse -Force docs/fase2-snapshots/a5103d2/resources/views/components/modals/* resources/views/components/modals/
Copy-Item -Force docs/fase2-snapshots/a5103d2/resources/views/components/modal-popup.blade.php resources/views/components/modal-popup.blade.php
```

### Dari git tip

```bash
# contoh: restore file dari tip fase-2 sebelum merge rusak
git show a5103d2:public/Custom_js/Backoffice/Inventory/Stock_Transfer.js > public/Custom_js/Backoffice/Inventory/Stock_Transfer.js

# restore seluruh folder modals
git checkout a5103d2 -- resources/views/components/modals resources/views/components/modal-popup.blade.php

# bandingkan tip
git diff a5103d2 -- app/Support/ProductUnitStock.php app/Http/Controllers/StockTransferController.php
```

Ganti `a5103d2` dengan tip terbaru di `docs/fase2-snapshots/` / `fase2/main` saat merge benar-benar dilakukan.

---

## 6. Update dokumen ini

Setiap fitur fase-2 baru yang **merge-sensitive**:
1. Tambah baris di tabel §1 dan path di §2.
2. Tambah marker di `docs/scripts/verify-fase2-inventory.php`.
3. Refresh clone: `php docs/scripts/snapshot-fase2-files.php`.
4. Commit bersama fiturnya: `docs: update fase2 merge inventory + snapshot`.
