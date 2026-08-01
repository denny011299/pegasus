# ACC Produksi — Keamanan Mutasi Stok (fase-2)

Dokumen perbaikan di `ProductionController::accProduction` agar ACC gagal **tidak** meninggalkan stok terpotong sementara status masih pending.

**File:** `app/Http/Controllers/ProductionController.php`  
**Branch konteks:** `fase-2` (produksi terintegrasi Stock Transfer)

---

## Perbedaan fase-2 vs alur lama

| | Alur lama | fase-2 |
|--|-----------|--------|
| Saat ACC sukses | potong bahan + **tambah stok produk** | potong bahan + **buat Stock Transfer Pending** ke gudang tujuan |
| Stok produk di gudang | langsung naik | naik saat ST di-ACC terima (setelah kirim) |

Create produksi tetap **pending**, belum potong stok.

---

## Masalah yang diperbaiki

### Gejala

1. ACC gagal (*bahan baku tidak mencukupi*).
2. Status produksi **tetap Pending**.
3. Sebagian stok bahan **sudah terpotong** + ada `log_stocks`.
4. ACC ulang → daftar bahan kurang bisa **bertambah** (stok sudah turun).

### Root cause

Loop potong bahan satu per satu, lalu baru return error tanpa rollback (versi lama).  
Retry + `cekLog` membuat pre-check seolah stok “kurang” padahal potongan milik ACC yang gagal.

Di fase-2 sudah ada `DB::transaction` + throw saat bahan kurang → rollback.  
Tetap ditambah **self-heal** + **pre-check** agar:

- data rusak historis bisa bersih saat ACC ulang,
- gagal stok kurang terjadi **sebelum** mutasi (fail cepat).

---

## Alur ACC sekarang

```
1. Validasi migrasi / status == pending (1)
2. SELF-HEAL  → revertPendingProductionStockMutations(production_code)
3. Agregasi kebutuhan BOM
4. Validasi gudang utama + rencana Stock Transfer
5. PRE-CHECK  → cek SEMUA bahan cukup (tanpa mutasi)
6. Jika kurang → return status -1 (belum potong)
7. TRANSACTION:
     - lock production
     - potong bahan + log
     - create Stock Transfer (Pending) per gudang tujuan
     - update status production → 2
   Gagal → DB::rollBack()
```

### Self-heal — `revertPendingProductionStockMutations`

- Ambil `log_stocks` `log_kode = production_code`, urutan `log_id` DESC.
- Balikkan stok bahan/produk sesuai `log_type` / `log_category`.
- Hapus log.
- Tidak ada log → no-op (tidak redundan).

### Pre-check

Simulasi konversi unit di memori. Kurang → JSON `status: -1` tanpa masuk transaction potong.

### Transaction

Sudah ada di fase-2. Bahan kurang di fase eksekusi → `RuntimeException` → rollback.

---

## Invariant

> Pending (`status = 1`) **tidak boleh** punya `log_stocks` potong bahan untuk `production_code`-nya.

```sql
SELECT p.production_code, p.status, COUNT(ls.log_id) AS logs
FROM productions p
JOIN log_stocks ls ON ls.log_kode = p.production_code
WHERE p.status = 1
  AND ls.log_notes LIKE '%Pengurangan bahan%'
GROUP BY p.production_code, p.status;
```

---

## Catatan developer

1. Jangan hapus self-heal / pre-check / transaction tanpa pengganti setara.
2. Logika BOM / konversi tidak diganti; yang ditambah guard + atomicity + heal.
3. Setelah ACC sukses, stok produk mengikuti alur **Stock Transfer** (Pending → Kirim → Terkirim).
4. FE handle `status: -1` untuk pesan bahan kurang.
