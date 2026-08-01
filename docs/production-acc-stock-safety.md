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
2. SELF-HEAL  → revertPendingProductionStockMutations(production_code)
3. Agregasi kebutuhan BOM (semua item)
4. PRE-CHECK  → cek SEMUA bahan cukup (tanpa mutasi stok)
5. Jika kurang → return status -1 (belum ada potongan)
6. TRANSACTION:
     - potong bahan + log
     - tambah produk jadi + log
     - update status production → 2
   Jika gagal di tengah → DB::rollBack()
```

### 1) Self-heal — `revertPendingProductionStockMutations`

Dipanggil **setiap** ACC pending, sebelum pre-check.

- Ambil semua `log_stocks` dengan `log_kode = production_code` (status aktif), urutan `log_id` DESC.
- Balikkan efek stok:
  - `log_type = 2` (bahan): category `2` = stok dikembalikan (+); category `1` = stok dikurangi (−) — untuk log konversi.
  - `log_type = 1` (produk): kebalikan dari penambahan hasil produksi.
- Hapus log tersebut.
- Jika tidak ada log orphan → no-op.

**Kenapa perlu:** membersihkan sisa ACC gagal lama, supaya pre-check membaca stok yang benar dan retry tidak “mengunci” produksi.

### 2) Pre-check

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

## Catatan untuk developer

1. **Jangan hapus** self-heal / pre-check / transaction tanpa pengganti yang setara.
2. Logika bisnis BOM / konversi unit **tidak diganti**; yang ditambah hanya guard + atomicity.
3. Self-heal menghapus log orphan — ini disengaja agar `cekLog` tidak menganggap bahan “sudah dipotong” untuk ACC yang belum sukses.
4. FE sudah handle `status: -1` untuk pesan bahan kurang; biarkan response shape itu.
5. Data historis yang sudah “rusak” (pending + stok kepotong) diperbaiki otomatis saat user ACC lagi — tidak wajib skrip manual, kecuali ingin audit/restore di luar UI.
