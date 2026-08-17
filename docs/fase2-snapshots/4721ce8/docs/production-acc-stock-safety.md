# ACC Produksi — Keamanan Mutasi Stok (fase-2)

Dokumen perbaikan di `ProductionController::accProduction` agar ACC gagal **tidak** meninggalkan stok terpotong sementara status masih pending.

**File:** `app/Http/Controllers/ProductionController.php`  
**Branch konteks:** `fase-2` (produksi terintegrasi Stock Transfer)

---

## Perbedaan fase-2 vs alur lama

|                 | Alur lama                             | fase-2 (inventori-first)                                                                                                          |
| --------------- | ------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------- |
| Saat ACC sukses | potong bahan + **tambah stok produk** | potong bahan + **tambah stok di gudang produksi**; ST Pending **hanya** jika tujuan ≠ asal (eceran/lain). Tidak ada ST main→main. |
| ST Kirim        | n/a                                   | potong stok dari gudang asal                                                                                                      |
| ST Terima       | n/a                                   | tambah stok di gudang tujuan                                                                                                      |

Create produksi tetap **pending**, belum potong stok.

---

## Masalah yang diperbaiki

### Gejala (historis)

1. ACC gagal (_bahan baku tidak mencukupi_).
2. Status produksi **tetap Pending**.
3. Sebagian stok bahan **sudah terpotong** + ada `log_stocks`.
4. ACC ulang → daftar bahan kurang bisa **bertambah**.

### Root cause

Loop potong bahan satu per satu, lalu return error tanpa rollback (versi lama).

### Solusi sekarang

`DB::transaction` + pre-check bahan sebelum mutasi. Self-heal orphan log sudah dihapus (data historis sudah dibersihkan di server).

---

## Alur ACC sekarang

```
1. Validasi migrasi / status == pending (1)
2. Agregasi kebutuhan BOM
3. Validasi gudang utama + rencana Stock Transfer
4. PRE-CHECK  → cek SEMUA bahan cukup (tanpa mutasi)
5. Jika kurang → return status -1 (belum potong)
6. TRANSACTION:
     - lock production
     - potong bahan + log (+ log_saldo)
     - inventori hasil produksi ke gudang asal (+ log Hasil produksi)
     - create Stock Transfer (Pending) per gudang tujuan
     - update status production → 2
   Gagal → DB::rollBack()
7. ST Kirim → potong stok gudang asal
8. ST Terima → tambah stok gudang tujuan
```

### Pre-check

Simulasi konversi unit di memori. Kurang → JSON `status: -1` tanpa masuk transaction potong.

### Transaction

Bahan kurang di fase eksekusi → `RuntimeException` → rollback.

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

1. Jaga pre-check + transaction; jangan potong stok di luar transaction.
2. Logika BOM / konversi tidak diganti; yang ditambah guard + atomicity.
3. Setelah ACC sukses, stok produk mengikuti alur **Stock Transfer** (Pending → Kirim → Terkirim).
4. FE handle `status: -1` untuk pesan bahan kurang.
