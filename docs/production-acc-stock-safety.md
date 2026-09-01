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

`DB::transaction` + pre-check bahan sebelum mutasi menutup akar masalah untuk ACC BARU (PR
#74) — exception apa pun di tengah ACC sekarang rollback bersih, tidak ada lagi potongan
setengah jalan lewat jalur ini.

**Update GitHub #83 (2026-08-29):** itu tidak menutup kasus lama/langka lainnya — produksi
yang SUDAH kepentok dalam keadaan orphan dari sebelum PR #74 (atau proses PHP yang mati total,
di luar jangkauan `try/catch`), retry ACC-nya tetap memotong bahan untuk kedua kalinya karena
tidak ada apa pun yang membersihkan orphan itu duluan. Self-heal kini **dihidupkan kembali di
ACC, tapi dijaga**: `ProductionPendingStockRestorer::findLockingOpnameCode()` dicek dulu — kalau
ADA Stock Opname (bahan/produk) yang sudah **disetujui** untuk item+gudang yang sama SEJAK
orphan log itu ditulis, auto-restore **dibatalkan** (balas `status: -4`, minta peninjauan
manual) persis karena alasan insiden PR0258 di
`docs/laporan-opname-vs-pending-production-2026-08-01.md`: opname begitu sudah menghitung fisik
dengan angka yang terpotong itu sebagai kenyataan, jadi mengembalikan stok sistem akan
menyuntik stok yang secara fisik tidak ada. Kalau tidak ada opname yang mengunci, self-heal
jalan otomatis (dengan log kompensasi) sebelum ACC lanjut seperti biasa.

---

## Alur ACC sekarang

```
1. Validasi migrasi / status == pending (1)
2. SELF-HEAL (GitHub #83): kalau ada log_stocks aktif untuk production_code ini —
     - ada Opname yang mengunci (disetujui sejak log ditulis)? → status -4, stop, minta review
     - tidak ada → revert orphan (ProductionPendingStockRestorer, +log kompensasi), lanjut
3. Agregasi kebutuhan BOM
4. Validasi gudang utama + rencana Stock Transfer
5. PRE-CHECK  → cek SEMUA bahan cukup (tanpa mutasi)
6. Jika kurang → return status -1 (belum potong)
7. TRANSACTION:
     - lock production
     - potong bahan + log (+ log_saldo)
     - inventori hasil produksi ke gudang asal (+ log Hasil produksi)
     - create Stock Transfer (Pending) per gudang tujuan
     - update status production → 2
   Gagal → DB::rollBack()
8. ST Kirim → potong stok gudang asal
9. ST Terima → tambah stok gudang tujuan
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
4. FE handle `status: -1` untuk pesan bahan kurang, `status: -4` untuk orphan yang dikunci opname
   (butuh review manual — FE tidak punya aksi khusus, cukup tampilkan `header`/`message` seperti
   status error lainnya).
5. `findLockingOpnameCode()` sengaja tidak menyaring per `unit_id` — item+gudang+waktu saja.
   Kalau ragu, blokir; jangan longgarkan tanpa alasan kuat, ini pagar terhadap kasus PR0258.
