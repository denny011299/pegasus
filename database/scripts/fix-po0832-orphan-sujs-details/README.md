# Fix PO0832 — orphan detail SUJS

Staging sempat nembak DB live → 4 detail SUJS nempel ke `po_id=832` sebelum header Mulia ada.

## Hapus apa

Soft-delete (`status=0`) **hanya** `pod_id` 1713–1716 (SKU SUJS).

## Tidak disentuh

Header PO0832, detail Mulia (1717), PDO0763, INV0763, stok, `log_stocks`.

## Urutan

1. `00_dry_run.sql` — harus tepat 4 baris target; Mulia tetap 1.
2. `01_soft_delete_sujs_details.sql` — `target_rows_must_be_4` = 4, `rows_updated` = 4.
3. `COMMIT;` atau `ROLLBACK;`
4. `02_verify_after.sql` — ketiga kolom harus `OK`.
5. `03_scan_orphan_and_mismatch.sql` — cari kasus serupa di PO lain (orphan / campur supplier).

Kalau count ≠ 4 → `ROLLBACK`, jangan commit.

### Baca hasil scan (03)

| Query | Aman kalau |
|---|---|
| 1 orphan detail | 0 baris |
| 2 delivery/invoice tanpa header | 0 baris (PDO/INV PO0832 tetap ada selama header ada — normal) |
| 3 variant supplier ≠ header | 0 baris aktif (atau review manual kalau ada) |
| 4 detail lebih tua dari header | 0, atau PO yang memang diedit lama |
