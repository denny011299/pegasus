---
name: pegasus-pending-stock
description: >-
  Antrian Mutasi Stok contract, lazy flush, hooks, and bug checklist for
  okejob-pegasus. Use when implementing or debugging PendingStockOperation,
  OpenOpnameGuard, SoftBlock, opname close apply, ST/Produksi queue, or stale
  pending after opname date rolls.
---

# Pending Stock Queue (Antrian Mutasi Stok)

## Product rules

- Scope = gudang yang opname **open hari ini** (`status=1` + `sto_date`/`stob_date` = today).
- Queue: Produksi ACC, ST Kirim (asal), ST Terima (tujuan), **ACC Pembelian (bahan)**.
- Soft-block: SO ACC, retur, product issues, safety→stock; retur/tolak-PO-setelah-ACC (mutasi stok).
- ACC Pembelian saat opname bahan open → enqueue (`purchase_order_acc`), PO tetap status menunggu, stok belum naik; apply setelah opname close / lazy flush.
- No new ST status. Ship queued → ST stays `1`. Accept queued → ST stays `2`.
- Apply order: ship → accept → production_acc via existing paths (roll-up untouched).
- After opname ACC/tolak: apply only if `!OpenOpnameGuard::isBlocked(WH, domain)`.
- **Lazy flush (no cron):** `flushStaleIfUnblocked` — if not blocked today but pending rows exist, apply. Call from SoftBlock, ST ship/accept/approve, Produksi ACC, list Antrian.

## Bug checklist

- [ ] Migration `pending_stock_operations` creates table; statuses 0/1/2/3
- [ ] Guard WH-scoped + date today; supplies vs product domains separate
- [ ] Enqueue uses `lockForUpdate` on open opname; idempotent pending source
- [ ] ST/Produksi: hasPending → `-1`; open → enqueue + info; else normal
- [ ] Opname ACC/tolak → flush only when fully unblocked
- [ ] Lazy flush: yesterday open opname (status 1, date yesterday) → pending applies
- [ ] Soft-block returns `-1` / message; SoftBlock calls lazy flush first
- [ ] Edit ST blocked if status≠1 or ship PSO pending
- [ ] UI list server-side DT + skeleton; no apply buttons; permission `Antrian Mutasi Stok|view`
- [ ] Tests: guard scope, queue+apply, soft-block, **lazy stale flush**, smoke page

## Commands

```bash
php artisan migrate --path=database/migrations/2026_09_11_000100_create_pending_stock_operations_table.php --force
php artisan migrate --env=testing --path=database/migrations/2026_09_11_000100_create_pending_stock_operations_table.php --force
php vendor/bin/phpunit --filter PendingStockQueueWorkflowTest
php vendor/bin/phpunit --filter pendingStockOperation
```

## Anti-confusion

- Not `ProductionPendingStockRestorer`
- Not Firebase / Redis queue / cron (unless user explicitly asks)
