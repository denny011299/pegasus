# Laporan: Stok & Opname yang Tidak Boleh Diganggu

**DB:** `mypegasus`  
**Tanggal audit:** 2026-08-01  
**Konteks:** Produksi pending `PR0258` sudah memotong bahan (ACC gagal orphan). Opname bahan sore mengunci angka setelah potongan. **Jangan restore** potongan pending ini.

---

## 1. Produksi pending bermasalah

| Field | Nilai |
|-------|-------|
| Kode | `PR0258` |
| Status | **Pending (1)** |
| Dibuat | 2026-08-01 08:09:29 |
| Updated | 2026-08-01 08:09:29 |
| Desc | - |
| Jumlah log_stocks aktif | 36 |

---

## 2. Stock Opname yang tidak boleh diganggu

### 2.1 Opname Bahan Mentah (hari ini)

| Kode | ID | Waktu | Status | Acc by | Relasi ke PR0258 |
|------|----|-------|--------|--------|------------------|
| `SB0068` | 68 | 2026-08-01 08:55:28 | 2 | 15 | Sebelum potongan orphan |
| `SB0069` | 69 | 2026-08-01 15:44:23 | 2 | 9 | **Setelah potongan — mengunci angka** |
| `SB0070` | 70 | 2026-08-01 15:49:33 | 2 | 9 | **Setelah potongan — mengunci angka** |

**Penting:** `SB0069` & `SB0070` sudah `system == real` untuk item yang kepotong. Restore akan membuat stok sistem naik → **opname jadi salah**.

### 2.2 Opname Produk (hari ini)

Tidak ada opname produk bertanggal 2026-08-01.

Opname produk terakhir (referensi, jangan diubah sembarangan):

| Kode | Tanggal | Status | Created |
|------|---------|--------|---------|
| `SP0056` | 2026-07-30 | 2 | 2026-07-30 10:40:46 |
| `SP0055` | 2026-07-29 | 2 | 2026-07-29 14:50:28 |
| `SP0054` | 2026-07-28 | 2 | 2026-07-28 16:05:47 |
| `SP0053` | 2026-07-27 | 2 | 2026-07-27 14:36:59 |
| `SP0052` | 2026-07-27 | 2 | 2026-07-27 08:25:55 |

PR0258 orphan **tidak** menambah stok produk (tidak ada log Hasil Produksi), jadi risiko utama ada di **bahan mentah**.

---

## 3. Bahan mentah yang kepotong (jangan di-restore)

| ID | Nama bahan | Qty kepotong | Unit | Waktu potong | Stok sekarang | Opname SB0069 (sys/real/selisih) |
|----|------------|--------------|------|--------------|---------------|----------------------------------|
| 78 | BOTOL 100 ML | **42** | Piece | 2026-08-01 14:37:35 | 3390 | 3390 pcs / 3390 pcs / 0 pcs |
| 72 | BOTOL MINYAK REM 50 ML | **3168** | Piece | 2026-08-01 14:37:35 | 19008 | 19008 pcs / 19008 pcs / 0 pcs |
| 4 | BOTOL PET 1000 ML | **2076** | Piece | 2026-08-01 14:37:35 | 15764 | 15764 pcs / 15764 pcs / 0 pcs |
| 5 | BOTOL PET 1500 ML | **4224** | Piece | 2026-08-01 14:37:34 | 10959 | 10959 pcs / 10959 pcs / 0 pcs |
| 2 | BOTOL PET 400 ML | **716** | Piece | 2026-08-01 14:37:35 | 27766 | 27766 pcs / 27766 pcs / 0 pcs |
| 34 | DOS AIR AKI HIKARI 20 X 400 ML | **18** | DOS | 2026-08-01 14:37:36 | 5995 | 5995 DOS / 5995 DOS / 0 DOS |
| 27 | DOS AIR AKI PEGASUS 12 X 1000 ML | **58** | DOS | 2026-08-01 14:37:36 | 594 | 594 DOS / 594 DOS / 0 DOS |
| 26 | DOS AIR AKI PEGASUS 12 X 1500 ML | **102** | DOS | 2026-08-01 14:37:35 | 4919 | 4919 DOS / 4919 DOS / 0 DOS |
| 95 | DOS AIR ZUUR HIKARI 12 X 1000 ML | **115** | DOS | 2026-08-01 14:37:35 | 7295 | 7295 DOS / 7295 DOS / 0 DOS |
| 94 | DOS AIR ZUUR HIKARI 12 X 1500 ML | **250** | DOS | 2026-08-01 14:37:35 | 2639 | 2639 DOS / 2639 DOS / 0 DOS |
| 35 | DOS AIR ZUUR HIKARI 20 X 400 ML | **16** | DOS | 2026-08-01 14:37:35 | 3110 | 3110 DOS / 3110 DOS / 0 DOS |
| 69 | DOS HIKARI SHIELD 24 X 50 GR | **3** | DOS | 2026-08-01 14:37:35 | 1477 | 1477 DOS / 1477 DOS / 0 DOS |
| 36 | DOS MINYAK REM HIKARI 48 X 50 ML | **66** | DOS | 2026-08-01 14:37:35 | 1834 | 1834 DOS / 1834 DOS / 0 DOS |
| 10 | DOS RADIATOR COOLANT HIKARI 4 X 5 LTR | **246** | DOS | 2026-08-01 14:37:35 | 2694 | 2694 DOS / 2694 DOS / 0 DOS |
| 11 | DOS RADIATOR COOLANT PEGASUS 12 X 1 LITER | **88** | DOS | 2026-08-01 14:37:36 | 3218 | 3218 DOS / 3218 DOS / 0 DOS |
| 90 | DOS SILICONE / TYRE / SHAMPOO 30 X 400 ML | **1** | DOS | 2026-08-01 14:37:35 | 2453 | 2453 DOS / 2453 DOS / 0 DOS |
| 12 | JERIGEN 1 LITER | **1056** | Piece | 2026-08-01 14:37:36 | 25788 | 12788 pcs / 12788 pcs / 0 pcs |
| 54 | STICKER AIR AKI HIKARI  (EX PPN ) | **360** | Piece | 2026-08-01 14:37:36 | 122530 | 122530 pcs / 122530 pcs / 0 pcs |
| 52 | STICKER AIR AKI PEGASUS  ( EX PPN ) | **1920** | Piece | 2026-08-01 14:37:35 | 163562 | 163562 pcs / 163562 pcs / 0 pcs |
| 55 | STICKER AIR ZUUR HIKARI (EX PPN) | **4700** | Piece | 2026-08-01 14:37:35 | 106962 | 106962 pcs / 106962 pcs / 0 pcs |
| 57 | STICKER MINYAK REM HIKARI 50 ML | **3168** | Piece | 2026-08-01 14:37:35 | 61508 | 61508 pcs / 61508 pcs / 0 pcs |
| 49 | STICKER RADIATOR COOLANT HIKARI 5 LTR SET ( EX PPN ) | **985** | Piece | 2026-08-01 14:37:35 | 40236 | 40236 pcs / 40236 pcs / 0 pcs |
| 50 | STICKER RADIATOR COOLANT PEGASUS 1 LTR SET (EX PPN) | **1056** | Piece | 2026-08-01 14:37:36 | 49973 | 49973 pcs / 49973 pcs / 0 pcs |
| 147 | STICKER SILICONE OIL | **30** | Piece | 2026-08-01 14:37:35 | 4290 | 4290 pcs / 4290 pcs / 0 pcs |
| 67 | STICKER SILICONE PEGASUS | **48** | Piece | 2026-08-01 14:37:35 | 9796 | 9796 pcs / 9796 pcs / 0 pcs |
| 22 | SUMPEL DATAR JERIGEN 1 LITER | **1056** | Piece | 2026-08-01 14:37:36 | 33653 | 20653 pcs / 20653 pcs / 0 pcs |
| 42 | SUMPEL DATAR JERIGEN 5 LTR | **985** | Piece | 2026-08-01 14:37:35 | 11506 | 11506 pcs / 11506 pcs / 0 pcs |
| 73 | SUMPEL MINYAK REM 50 ML/1 LITER | **3168** | Piece | 2026-08-01 14:37:35 | 17832 | 17832 pcs / 17832 pcs / 0 pcs |
| 6 | Tutup Biru | **2316** | Piece | 2026-08-01 14:37:35 | 81019 | 81019 pcs / 81019 pcs / 0 pcs |
| 79 | TUTUP BIRU BOTOL 100 ML | **42** | Piece | 2026-08-01 14:37:35 | 17882 | 17882 pcs / 17882 pcs / 0 pcs |
| 23 | TUTUP JERIGEN 1 LTR HIJAU | **1056** | Piece | 2026-08-01 14:37:36 | 26165 | 13165 pcs / 13165 pcs / 0 pcs |
| 38 | TUTUP JERIGEN HIJAU 5 LITER | **985** | Piece | 2026-08-01 14:37:35 | 4992 | 4992 pcs / 4992 pcs / 0 pcs |
| 7 | Tutup Merah | **4700** | Piece | 2026-08-01 14:37:34 | 66904 | 66904 pcs / 66904 pcs / 0 pcs |
| 75 | TUTUP MERAH BOTOL 50 ML | **3168** | Piece | 2026-08-01 14:37:35 | 16412 | 16412 pcs / 16412 pcs / 0 pcs |
| 13 | TUTUP TUMPUL BIRU | **2280** | Piece | 2026-08-01 14:37:35 | 65032 | 55032 pcs / 55032 pcs / 0 pcs |
| 14 | TUTUP TUMPUL MERAH | **320** | Piece | 2026-08-01 14:37:35 | 95718 | 95718 pcs / 95718 pcs / 0 pcs |

---

## 4. History stok (log_stocks) terkait — jangan dihapus/diubah sembarangan

### 4.1 Log orphan PR0258 (penyebab potongan)

| Type | Cat | Notes | Jumlah baris | Sum qty |
|------|-----|-------|--------------|---------|
| Bahan (2) | Keluar (2) | Pengurangan bahan untuk produksi | 36 | 44588 |

Rentang log: 2026-08-01 **14:37:34 – 14:37:36**.

### 4.2 History yang muncul di UI

- **Stok Bahan Mentah** (`/stockSupplies`) → klik item di tabel atas → filter/cari kode `PR0258`.
- **Stok Produk** (`/stockProduct`) → untuk PR0258 orphan **tidak ada** log hasil produk.

---

## 5. Produk

PR0258 **tidak** menambah stok produk (0 log Hasil Produksi).

Item di dokumen produksi (belum jadi stok karena ACC gagal):

| Produk | Variant | Qty | Unit |
|--------|---------|-----|------|
| AIR ZUUR HIKARI | 12 x 1500 ml | 250 | DOS |
| RADIATOR COOLANT HIKARI 4 X 5 LITER | Hijau | 246 | DOS |
| MINYAK REM HIKARI MERAH | 48 X 50 ML | 66 | DOS |
| AIR AKI PEGASUS | 12 x 1500 ml | 102 | DOS |
| AIR ZUUR HIKARI | 12 x 1000 ml | 115 | DOS |
| SILICONE OIL PEGASUS | 30 x 400 ML | 1 | DOS |
| SILICONE PEGASUS | 14 X 100 ML | 3 | DOS |
| AIR ZUUR HIKARI | 20 x 400ml | 16 | DOS |
| RADIATOR COOLANT PEGASUS 12 X 1 L | Hijau | 88 | DOS |
| AIR AKI PEGASUS | 12 x 1000 ml | 58 | DOS |
| AIR AKI HIKARI | 20 x 400ml | 18 | DOS |
| SILICONE PEGASUS | 30X400ML | 6 | Piece |
| RADIATOR COOLANT HIKARI 4 X 5 LITER | Hijau | 1 | Piece |

---

## 6. Opname bahan yang berubah (selisih ≠ 0) — terpisah dari PR0258

Ini **bukan** item potongan PR0258, tapi tetap jangan diganggu tanpa alasan:

| Opname | Bahan | System | Real | Selisih |
|--------|-------|--------|------|---------|
| `SB0068` | STICKER HIKARI TYRE | 0 pcs | 10000 pcs | 10000 pcs |
| `SB0069` | MINYAK REM PRESTON 1 LTR ( MERAH ) PC | 64 pcs | 0 pcs | -64 pcs |
| `SB0069` | MINYAK REM PRESTON 12 X 1 LTR ( MERAH ) | 64 DOS | 69 DOS | 5 DOS |
| `SB0069` | MINYAK REM PRESTON 24 X 300 ML MERAH | 36 DOS | 39 DOS | 3 DOS |
| `SB0070` | MINYAK REM PRESTON 300 ML MERAH PC | 72 pcs | 0 pcs | -72 pcs |

---

## 7. Kalau tetap ingin kembalikan stok pending — apa yang terganggu?

1. **`supplies_stocks`** naik sejumlah qty di tabel §3.
2. **Opname `SB0069` / `SB0070`** jadi tidak mencerminkan stok sistem (system opname lama < stok baru).
3. **History stok bahan** — log `PR0258` Pengurangan bahan akan dihapus (oleh artisan restore); kalau `--with-history` muncul log pengembalian baru.
4. **Laporan pengelolaan bahan / selisih opname** bisa membingungkan audit hari itu.
5. **Produk** relatif aman untuk kasus PR0258 (tidak ada penambahan produk orphan).

### Keputusan operasional (disepakati)

- **Jangan restore** potongan pending yang sudah dilock opname.
- **Healer ACC otomatis: OFF.**
- Tetap dipakai: **pre-check + DB transaction** agar ACC baru tidak potong setengah jalan.
- Restore manual hanya jika bisnis secara eksplisit mengizinkan (artisan `production:restore-pending-stock`).

