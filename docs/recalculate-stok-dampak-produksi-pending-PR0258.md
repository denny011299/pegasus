# Recalculate Stok Bahan — Dampak Bug Produksi Pending

**DB:** `mypegasus`  
**Sumber dampak:** `PR0258` (status pending) — log *Pengurangan bahan untuk produksi*  
**Waktu potongan:** ~2026-08-01 14:37  
**Rumus:** `estimasi_sebelum_dampak = stok_sekarang + qty_dampak_orphan`  

> Catatan: jika setelah 14:37 ada mutasi lain (opname ACC, produksi lain, pembelian), angka "sebelum dampak" = stok seolah orphan cut dibatalkan **dari posisi stok saat ini**, bukan snapshot persis detik sebelum bug.

| # | ID | Bahan | Unit | Qty dampak (kepotong orphan) | Stok sekarang | Estimasi sebelum dampak | Opname SB0069 (sys/real) |
|---|----|-------|------|------------------------------|---------------|-------------------------|--------------------------|
| 1 | 78 | BOTOL 100 ML | pcs | **42** | 3390 | **3432** | 3390 pcs / 3390 pcs |
| 2 | 72 | BOTOL MINYAK REM 50 ML | pcs | **3168** | 19008 | **22176** | 19008 pcs / 19008 pcs |
| 3 | 4 | BOTOL PET 1000 ML | pcs | **2076** | 15764 | **17840** | 15764 pcs / 15764 pcs |
| 4 | 5 | BOTOL PET 1500 ML | pcs | **4224** | 10959 | **15183** | 10959 pcs / 10959 pcs |
| 5 | 2 | BOTOL PET 400 ML | pcs | **716** | 27766 | **28482** | 27766 pcs / 27766 pcs |
| 6 | 34 | DOS AIR AKI HIKARI 20 X 400 ML | DOS | **18** | 5995 | **6013** | 5995 DOS / 5995 DOS |
| 7 | 27 | DOS AIR AKI PEGASUS 12 X 1000 ML | DOS | **58** | 594 | **652** | 594 DOS / 594 DOS |
| 8 | 26 | DOS AIR AKI PEGASUS 12 X 1500 ML | DOS | **102** | 4919 | **5021** | 4919 DOS / 4919 DOS |
| 9 | 95 | DOS AIR ZUUR HIKARI 12 X 1000 ML | DOS | **115** | 7295 | **7410** | 7295 DOS / 7295 DOS |
| 10 | 94 | DOS AIR ZUUR HIKARI 12 X 1500 ML | DOS | **250** | 2639 | **2889** | 2639 DOS / 2639 DOS |
| 11 | 35 | DOS AIR ZUUR HIKARI 20 X 400 ML | DOS | **16** | 3110 | **3126** | 3110 DOS / 3110 DOS |
| 12 | 69 | DOS HIKARI SHIELD 24 X 50 GR | DOS | **3** | 1477 | **1480** | 1477 DOS / 1477 DOS |
| 13 | 36 | DOS MINYAK REM HIKARI 48 X 50 ML | DOS | **66** | 1834 | **1900** | 1834 DOS / 1834 DOS |
| 14 | 10 | DOS RADIATOR COOLANT HIKARI 4 X 5 LTR | DOS | **246** | 2694 | **2940** | 2694 DOS / 2694 DOS |
| 15 | 11 | DOS RADIATOR COOLANT PEGASUS 12 X 1 LITER | DOS | **88** | 3218 | **3306** | 3218 DOS / 3218 DOS |
| 16 | 90 | DOS SILICONE / TYRE / SHAMPOO 30 X 400 ML | DOS | **1** | 2453 | **2454** | 2453 DOS / 2453 DOS |
| 17 | 12 | JERIGEN 1 LITER | pcs | **1056** | 25788 | **26844** | 12788 pcs / 12788 pcs |
| 18 | 54 | STICKER AIR AKI HIKARI  (EX PPN ) | pcs | **360** | 122530 | **122890** | 122530 pcs / 122530 pcs |
| 19 | 52 | STICKER AIR AKI PEGASUS  ( EX PPN ) | pcs | **1920** | 163562 | **165482** | 163562 pcs / 163562 pcs |
| 20 | 55 | STICKER AIR ZUUR HIKARI (EX PPN) | pcs | **4700** | 106962 | **111662** | 106962 pcs / 106962 pcs |
| 21 | 57 | STICKER MINYAK REM HIKARI 50 ML | pcs | **3168** | 61508 | **64676** | 61508 pcs / 61508 pcs |
| 22 | 49 | STICKER RADIATOR COOLANT HIKARI 5 LTR SET ( EX PPN ) | pcs | **985** | 40236 | **41221** | 40236 pcs / 40236 pcs |
| 23 | 50 | STICKER RADIATOR COOLANT PEGASUS 1 LTR SET (EX PPN) | pcs | **1056** | 49973 | **51029** | 49973 pcs / 49973 pcs |
| 24 | 147 | STICKER SILICONE OIL | pcs | **30** | 4290 | **4320** | 4290 pcs / 4290 pcs |
| 25 | 67 | STICKER SILICONE PEGASUS | pcs | **48** | 9796 | **9844** | 9796 pcs / 9796 pcs |
| 26 | 22 | SUMPEL DATAR JERIGEN 1 LITER | pcs | **1056** | 33653 | **34709** | 20653 pcs / 20653 pcs |
| 27 | 42 | SUMPEL DATAR JERIGEN 5 LTR | pcs | **985** | 11506 | **12491** | 11506 pcs / 11506 pcs |
| 28 | 73 | SUMPEL MINYAK REM 50 ML/1 LITER | pcs | **3168** | 17832 | **21000** | 17832 pcs / 17832 pcs |
| 29 | 6 | Tutup Biru | pcs | **2316** | 81019 | **83335** | 81019 pcs / 81019 pcs |
| 30 | 79 | TUTUP BIRU BOTOL 100 ML | pcs | **42** | 17882 | **17924** | 17882 pcs / 17882 pcs |
| 31 | 23 | TUTUP JERIGEN 1 LTR HIJAU | pcs | **1056** | 26165 | **27221** | 13165 pcs / 13165 pcs |
| 32 | 38 | TUTUP JERIGEN HIJAU 5 LITER | pcs | **985** | 4992 | **5977** | 4992 pcs / 4992 pcs |
| 33 | 7 | Tutup Merah | pcs | **4700** | 66904 | **71604** | 66904 pcs / 66904 pcs |
| 34 | 75 | TUTUP MERAH BOTOL 50 ML | pcs | **3168** | 16412 | **19580** | 16412 pcs / 16412 pcs |
| 35 | 13 | TUTUP TUMPUL BIRU | pcs | **2280** | 65032 | **67312** | 55032 pcs / 55032 pcs |
| 36 | 14 | TUTUP TUMPUL MERAH | pcs | **320** | 95718 | **96038** | 95718 pcs / 95718 pcs |

## Ringkasan

- Jumlah item terdampak: **36**
- Total qty unit tercatat (sum semua satuan, bukan satu UOM): **44588**
- Produksi: `PR0258` masih pending; log orphan masih ada di DB local `mypegasus`
- Di server (saat clear locks) log bisa sudah dihapus — stok tetap di angka setelah dampak; rumus yang sama: stok_sekarang + qty dari laporan audit

## Cara baca

1. **Qty dampak** = jumlah yang kepotong karena ACC gagal tapi status pending.
2. **Stok sekarang** = `supplies_stocks` saat audit.
3. **Estimasi sebelum dampak** = seolah potongan orphan tidak pernah terjadi (stok dikembalikan secara hitungan).
4. **Jangan auto-restore** ke DB tanpa keputusan bisnis — opname SB0069/SB0070 sudah mengunci angka setelah potong.

