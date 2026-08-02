# ACC Produksi — Keamanan Mutasi Stok

Dokumen ini menjelaskan perbaikan di `ProductionController::accProduction` agar ACC gagal **tidak** meninggalkan stok terpotong sementara status masih pending.

**File utama:** `app/Http/Controllers/ProductionController.php`  
**Endpoint:** ACC produksi (status `productions.status = 1` → `2`)

---

## Masalah yang diperbaiki

### Gejala (dari client / DB lokal `mypegasus`)

1. ACC produksi kadang gagal dengan pesan *“Bahan baku tidak mencukupi …”*.
2. Status produksi **tetap Pending**.
3. Stok bahan (PET, Tutup Jerigen, dll.) **sudah terpotong**.
4. ACC ulang → daftar bahan kurang bisa **bertambah** (stok sudah turun dari ACC sebelumnya).

### Contoh bukti

- Kode: `PR0258`
- `productions.status = 1` (pending)
- `log_stocks` sudah berisi puluhan baris *“Pengurangan bahan untuk produksi”*
- Belum ada log hasil produk jadi

### Root cause

Alur lama:

1. Loop potong bahan **satu per satu** + tulis `log_stocks`.
2. Baru di akhir (atau di tengah) baru ketemu bahan kurang → return error.
3. **Tidak ada rollback** → stok & log sudah berubah, status tetap pending.
4. Ada `cekLog`: bahan yang sudah punya log **tidak dipotong lagi** saat retry, tapi stok fisik sudah turun → pre-check berikutnya menganggap stok “kurang” padahal potongannya milik ACC yang gagal.

---

## Perbaikan (alur baru)

Urutan di `accProduction`:

```
1. Validasi data & status == pending (1)
2. Agregasi kebutuhan BOM (semua item)
3. PRE-CHECK  → cek SEMUA bahan cukup (tanpa mutasi stok)
4. Jika kurang → return status -1 (belum ada potongan)
5. TRANSACTION:
     - potong bahan + log
     - tambah produk jadi + log
     - update status production → 2
   Jika gagal di tengah → DB::rollBack()
```

### Self-heal otomatis di ACC — DIMATIKAN

Alasan (audit 2026-08-01 / `mypegasus`):

- Ada opname bahan setelah potongan orphan `PR0258` (`SB0069`, `SB0070`).
- Item yang kepotong di pending: **selisih opname = 0** (`system == real == stok sekarang`).
- Artinya angka stok **sudah dikunci** di level setelah potongan salah.
- Auto-heal saat ACC akan menaikkan stok sistem lagi → **tidak sinkron** dengan opname.

Perbaikan orphan hanya manual (setelah review):

```bash
php artisan production:restore-pending-stock --dry-run
php artisan production:restore-pending-stock --with-history --staff-id=...
```

### Pre-check

Validasi total kebutuhan agregat vs stok tersedia (termasuk simulasi konversi unit) **tanpa** `save()` stok.

Jika ada yang kurang → JSON:

```json
{
  "status": -1,
  "header": "Gagal ACC",
  "message": "Bahan baku tidak mencukupi untuk : ..."
}
```

### 3) Transaction

Mutasi stok + update status dibungkus `DB::beginTransaction()` / `commit()` / `rollBack()`.

- Bahan kurang di fase eksekusi (jarang, race) → `rollBack()` lalu return `-1`.
- Exception lain → `rollBack()` lalu rethrow.

**Jangan** menambah `save()` stok / `insertLog` di luar blok transaction ini tanpa alasan kuat.

---

## Status produksi (ringkas)

| status | Arti (ACC flow) |
|--------|-----------------|
| 1 | Pending — belum ACC; **seharusnya belum ada** log potong bahan |
| 2 | Sudah di-ACC |
| lainnya | Ditolak / batal / dll. (lihat model `Production`) |

**Invariant yang harus dijaga:**

> Pending (`status = 1`) **tidak boleh** punya `log_stocks` potong bahan untuk `production_code`-nya.  
> Jika ada → itu state rusak; self-heal akan membersihkannya saat ACC berikutnya.

---

## Cara cek data mencurigakan (SQL)

```sql
-- Produksi pending yang sudah punya log potong bahan (anomali)
SELECT p.production_code, p.status, COUNT(ls.log_id) AS logs
FROM productions p
JOIN log_stocks ls ON ls.log_kode = p.production_code
WHERE p.status = 1
  AND ls.log_notes LIKE '%Pengurangan bahan%'
GROUP BY p.production_code, p.status;
```

Setelah deploy fix, ACC ulang pada kode tersebut akan mengembalikan stok lalu mencoba ACC bersih.

---

## Artisan: restore massal di server

Untuk membersihkan **semua** (atau satu) produksi pending yang sudah kepotong tanpa menunggu ACC UI:

```bash
# 1) Cek dulu (tidak ubah DB)
php artisan production:restore-pending-stock --dry-run

# 2a) Restore TANPA history (default) — stok kembali, log orphan dihapus
php artisan production:restore-pending-stock

# 2b) Restore + catat di riwayat Stok Produk / Bahan Mentah
php artisan production:restore-pending-stock --with-history --staff-id=9

# Satu kode saja
php artisan production:restore-pending-stock --code=PR0258 --with-history --staff-id=9
```

| Flag | Efek |
|------|------|
| `--dry-run` | Hanya list anomali |
| `--with-history` | Tulis log kompensasi: *Pengembalian stok (perbaikan ACC pending gagal) …* (ON). Tanpa flag = OFF |
| `--staff-id=` | Isi kolom staff di log (opsional) |
| `--code=` | Batasi ke satu `production_code` |

**File:** `app/Console/Commands/RestorePendingProductionStockCommand.php`  
**Logic:** `app/Support/ProductionPendingStockRestorer.php`

Self-heal di ACC UI **tidak** dipakai lagi (lihat alasan opname di atas).  
Command artisan tetap ada untuk restore **manual** setelah dicek.

Hapus lock log orphan **tanpa** restore stok (opname-safe):

```bash
php artisan production:clear-pending-material-locks --dry-run
php artisan production:clear-pending-material-locks
php artisan production:clear-pending-material-locks --code=PR0258
```

Setelah clear: history potongan palsu hilang, stok tetap. **Jangan ACC** produksi itu tanpa review (akan potong lagi) — tolak/batalkan saja.

---

## Catatan untuk developer

1. **Jangan hapus** pre-check / transaction tanpa pengganti yang setara.
2. Logika bisnis BOM / konversi unit **tidak diganti**; yang ditambah guard + atomicity.
3. Jangan restore orphan otomatis di ACC jika opname sudah mengunci angka setelah potongan.
4. FE sudah handle `status: -1` untuk pesan bahan kurang; biarkan response shape itu.
5. Restore orphan hanya via artisan setelah audit (opname vs potongan pending).
