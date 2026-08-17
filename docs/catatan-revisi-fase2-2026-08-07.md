# Catatan Revisi — Fase 2 (7 Agustus 2026)

**Branch:** `fase-2` / `fase2/main`  
**Commit sudah di-push:** `4721ce8` (+ snapshot docs `004c80d`)  
**Perubahan lokal belum commit:** produksi, stock transfer, stock opname (lihat §6)

---

## Ringkasan

Revisi fase-2 multi-gudang meliputi: penggabungan menu pengembalian, perbaikan label histori stok, perbaikan gudang tujuan & filter gudang aktif, UX stock transfer, perbaikan kamera bukti foto, dan fix stock opname (kolom `is_draft`).

---

## 1. Pengembalian Customer (unified CSR + CPR)

| Item | Keterangan |
|------|------------|
| Menu | Satu halaman **Pengembalian** menggantikan CSR/CPR terpisah |
| Kode dokumen | Prefix **PKR####** (`return_group`) |
| Gudang tujuan | Gudang utama → nama gudang utama; gudang eceran → gudang aktif (retail) |
| Filter list | Hanya tampil pengembalian yang baris detailnya match `warehouse_id` gudang aktif |
| Satuan eceran | Jika belum di-set, Swal inline setup (reuse endpoint ST) |
| Kamera bukti | Preview tidak hitam lagi; kamera start saat modal `shown`, `video.play()` dipaksa |

**Deploy server:** jalankan migration `2026_08_07_120000_add_return_group_to_customer_returns.php` atau SQL reset tabel pengembalian (schema unified PKR) jika DB server belum migrasi. Pastikan folder `public/customer_returns/` writable.

---

## 2. Histori Stok (label UI)

| Sebelum | Sesudah | Lokasi |
|---------|---------|--------|
| Saldo | **Sisa** | Histori stok produk & bahan mentah |
| Selesai | **Tutup** | Tombol tutup modal histori bahan mentah |

---

## 3. Produksi

| Item | Keterangan |
|------|------------|
| Gudang Tujuan (non-retail) | Menampilkan **gudang aktif** (bukan gudang utama pertama di DOM) |
| List produksi | DataTable difilter `destination_warehouse_id = active_warehouse_id` |
| Badge / helper | Konsisten dengan gudang utama aktif di footer-scripts |

*Status: sudah diimplementasi lokal, belum di-push.*

---

## 4. Stock Transfer

| Item | Keterangan |
|------|------------|
| Gudang asal = tujuan | Field tujuan merah (invalid Select2), Tambah/Simpan diblok |
| Satuan eceran belum di-set | Swal picker saat pilih produk / klik Tambah (bukan cuma toast) |
| Ubah QTY di tabel | **Debounce 350 ms** + **abort** request lama per baris — tidak tembak server per ketikan |
| Validasi stok | Maks. 2 request per siklus validasi: `getTransferSourceStock` + `checkTransferStock` |

*Status UX asal=tujuan + retail Swal: sudah diimplementasi lokal, belum di-push.*

---

## 5. Stock Opname Produk

| Item | Keterangan |
|------|------------|
| Error simpan | Penyebab: kolom **`is_draft`** belum ada di tabel `stock_opnames` |
| Perbaikan | Migration `2026_07_31_010000_add_is_draft_to_stock_opnames_table.php` |
| Toast error | Pesan gagal simpan pakai `toastr.error` (bukan success) |

**Deploy server (wajib):**

```bash
php artisan migrate --path=database/migrations/2026_07_31_010000_add_is_draft_to_stock_opnames_table.php
```

Atau SQL:

```sql
ALTER TABLE stock_opnames
  ADD COLUMN is_draft TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
```

**Stock Opname Bahan** (jika dipakai): migration `2026_07_31_020000_add_is_draft_to_stock_opname_bahans_table.php`.

---

## 6. Belum commit / cleanup

Perubahan lokal (git status):

- `app/Models/Production.php`
- `public/Custom_js/Backoffice/Production/Production.js`
- `resources/views/components/modals/production/add-production.blade.php`
- `public/Custom_js/Backoffice/Inventory/Stock_Transfer.js`
- `resources/views/Backoffice/Inventory/Stock_Transfer.blade.php`
- `app/Http/Controllers/StockController.php` — fix opname + **log debug sementara**
- `app/Http/Controllers/ProductController.php` — **log debug sementara**
- `public/Custom_js/Backoffice/Inventory/CreateStockOpname.js` — fix toast + **log debug sementara**
- `public/Custom_js/Backoffice/Inventory/Stock_Opname.js` — **log debug sementara**

Hapus blok `// #region agent log` dan file `debug-c0f888.log` sebelum merge ke production.

---

## 7. Checklist uji (QA)

### Pengembalian
- [ ] Buat pengembalian supply + produk → kode PKR muncul
- [ ] Ganti gudang aktif → list hanya pengembalian gudang tersebut
- [ ] Produk tanpa retail unit → Swal setup inline
- [ ] Ambil foto bukti → preview kamera tampil

### Produksi
- [ ] Gudang aktif bukan gudang utama → badge Gudang Tujuan = gudang aktif
- [ ] List hanya produksi dengan tujuan = gudang aktif

### Stock Transfer
- [ ] Asal = tujuan → field tujuan merah, tidak bisa simpan
- [ ] Produk ke gudang eceran tanpa retail unit → Swal muncul
- [ ] Ketik QTY cepat → Network tab: request lama status canceled, hanya validasi terakhir selesai

### Stock Opname
- [ ] Migration `is_draft` sudah jalan di environment target
- [ ] Buat opname baru / draft → simpan sukses, redirect ke list
- [ ] Draft hanya terlihat oleh pembuat (non super admin)

---

## 8. Backup & merge ke main

- Snapshot file: `docs/fase2-snapshots/4721ce8/`
- Verifier: `php docs/scripts/verify-fase2-inventory.php`
- SOP lengkap: `docs/fase2-merge-inventory.md`
- Dump DB lokal (gitignored): `storage/backups/pegasus-20260807-1543.sql`

---

*Dibuat: 7 Agustus 2026*
