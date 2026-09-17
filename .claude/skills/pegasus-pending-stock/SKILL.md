---
name: pegasus-pending-stock
description: >-
  Soft-block mutasi stok saat Stock Opname open (OpenOpnameGuard +
  PendingStockSoftBlock). Antrian Mutasi Stok / PendingStockOperation sudah
  dihapus — jangan enqueue lagi.
---

# Soft-block saat Stock Opname

## Product rules

- Scope = gudang yang opname **open hari ini** (`status=1` + `sto_date`/`stob_date` = today)
  **atau** ada **page lock** Input (`detail…/-1`) live di gudang+domain itu.
- Draft dan menunggu sama-sama block (`OpenOpnameGuard` tidak filter `is_draft`).
- Soft-block (`status: -1`, header `Stock Opname`) via `PendingStockSoftBlock`.
- UI: SweetAlert via `notifikasi()` / `showPgErrorModal` / `showSoErrorModal`.
- Setelah opname beres, user **ulang** aksi.

### Page lock Input (`OpnamePageLock`)

- Exclusive 1 orang per `(warehouse_id, domain)` saat buka `/-1`.
- Lihat detail existing tetap boleh; ACC/submit/tolak/insert oleh non-holder ditolak.
- Heartbeat 10s, TTL 35s; release via beacon.
- File: `app/Support/StockOpname/OpnamePageLock.php`, `public/Custom_js/Shared/opname-page-lock.js`.

### Matrix

| Opname | Soft-block |
|--------|------------|
| Produk | ST, Produksi, Produk Bermasalah, Pengiriman (ACC+edit setelah ACC), Pengembalian, Safety→stok |
| Bahan | ST (n/a produk), Produksi, Produk Bermasalah, ACC PO/retur PO, Pengembalian, |

Helper: `messageIfAnyDomainBlocked` / `messageIfAnyWarehouseAnyDomainBlocked` untuk PI + Pengembalian (block di opname produk **atau** bahan).

## File inti

| Layer | File |
|-------|------|
| Guard | `app/Support/StockOpname/OpenOpnameGuard.php` |
| Page lock | `app/Support/StockOpname/OpnamePageLock.php` |
| Soft-block | `app/Support/PendingStockSoftBlock.php` |
| Tests | `tests/Workflow/PendingStockQueueWorkflowTest.php`, `tests/Workflow/OpnamePageLockWorkflowTest.php` |

## Jangan

- Jangan buat ulang `PendingStockOperation` / halaman Antrian / cron flush
- Jangan return `queued: 1`

## Commands

```bash
php vendor/bin/phpunit --filter PendingStockQueueWorkflowTest
```
