# Fix ACC PO → Eceran (staging)

Bug: ACC PO credit stok ke `active_warehouse` session (sering Gudang Eceran id=2), bukan Gudang Besar (id=1).

## Dump `u906028329_pegasus (3).sql` (29 Sep 2026)

| | |
|--|--|
| Main WH | **1** Gudang Besar |
| Eceran | **2** Gudang Eceran |
| `purchase_orders.warehouse_id` | **BELUM ada** |
| PO total | **832** (semua perlu backfill → 1) |
| ACC salah ke WH2 | **43** log / **19** PO (`PO0806`…`PO0831`) |
| Stok WH2 vs credit | **24** baris supplies, semua **EXACT** match |

## Jangan sentuh (client sudah betulkan)

- `supplies_id=248` SAM OIL GEAR 90 20 X 1 LITER
- `supplies_id=249` SAM OIL GEAR 140 20 X 1 LITER  
  (ACC `PO0829` di WH2; WH1 sudah diisi 10/15 manual 29 Sep — **jangan** move WH2→WH1 lagi)

Opsional: `03_zero_sam_eceran_residual.sql` — nolkan residual WH2 SAM 248/249 tanpa menambah WH1.

## Urutan

1. `00_dry_run.sql` — cek angka (match harus EXACT)
2. `01_alter_backfill_po_warehouse.sql` — kolom + backfill (atau `php artisan migrate --path=...2026_09_29_100000...`)
3. `02_move_pembelian_eceran_to_main.sql` — pindah stok + rewrite `log_stocks` (excl SAM)
4. `03_zero_sam_eceran_residual.sql` — opsional, bersihkan sisa SAM di Eceran

Commit manual setelah review tiap langkah.
