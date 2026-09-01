# Deploy Live Fase 2 — Pegasus

**Branch:** `fase-2` | **Update:** 2026-09-01

## Jalur praktis (1 path ke live)

### Opsi A — Import dump dev (disarankan kalau mau mirror dev)

1. **Backup** DB production dulu.
2. Import **`database/sql/pegasus_live_fase2_export_6.sql`** (phpMyAdmin / mysql CLI).
3. Jalankan **`database/sql/pegasus_live_deploy_bundle.sql`** — patch idempotent yang mungkin belum ada di dump.
4. *(Opsional)* **`database/sql/seed_external_api_from_snapshot.sql`** — seed PMO + endpoint External API.
5. Deploy kode PHP (lihat daftar di bawah).
6. `php artisan config:cache` + `php artisan route:cache` di server.

### Opsi B — Upgrade in-place (DB live lama, tanpa replace full dump)

1. **Backup** DB production.
2. **`database/sql/fase2_schema_all.sql`** — schema multi-gudang + stock transfer + produksi traceability + `productions.warehouse_id`.
3. **`database/sql/pegasus_live_deploy_bundle.sql`** — gap yang belum masuk fase2 (PMO ref, stock opname wh, approval ST, retail unit, external API DDL).
4. *(Opsional)* `seed_external_api_from_snapshot.sql`
5. Deploy kode PHP.

---

## Urutan file SQL (ringkas)

| # | File | Wajib? | Isi |
|---|------|--------|-----|
| 1 | `pegasus_live_fase2_export_6.sql` | Opsi A saja | Full dump dev (schema + data) |
| 1 | `fase2_schema_all.sql` | Opsi B saja | Schema fase2 gabungan |
| 2 | **`pegasus_live_deploy_bundle.sql`** | **Ya** | PMO ref, production wh, stock opname wh, ST approval, retail unit, external API DDL, **dedup product_stocks** |
| 3 | `seed_external_api_from_snapshot.sql` | Opsional | Data PMO app + API key + 33 endpoint |
| — | `add_warehouse_sidebar_gudang_menus.sql` | Opsional | Menu sidebar Gudang di halaman gudang |
| — | `post_upgrade_staging_fixes.sql` | Skip live* | CSR reset + retail unit (sudah di bundle) |
| — | `fix_live_warehouse_default.sql` | Skip jika fase2/export sudah jalan | Seed gudang + backfill wh |
| — | `after_dev_import_and_seed.sql` | Dev saja | CSR drop/recreate + traceability non-idempotent |

\*Live yang sudah punya data CSR jangan jalankan file yang `DROP TABLE customer_supply_returns`.

---

## File SQL per fitur (referensi)

| Fitur | File |
|-------|------|
| Multi-gudang (warehouse, product_stocks.wh, staff_warehouses) | `fase2_schema_all.sql` |
| Production `warehouse_id` header | `add_productions_warehouse_id.sql` *(sudah di fase2 + bundle)* |
| Production traceability (`destination_warehouse_id`, ST source_type) | `fase2_schema_all.sql` |
| Stock transfer approval QC/Ops | `2026_08_24_003700_add_approval_columns_to_stock_transfers_table.sql` *(di bundle)* |
| Stock opname per gudang + touched | `2026_08_20_180000_...` + `2026_08_14_010000_...` *(di bundle)* |
| PMO ref (`ref_payment_id`, `ref_product_id`, dll.) | `add_pmo_ref_columns.sql` *(di bundle)* |
| Retail unit Piece + stok eceran | `set_default_retail_unit_piece.sql` + `fix_retail_warehouse_stock_units.sql` *(di bundle)* |
| Dedup `product_stocks` + unique index | `fix_product_stocks_duplicates.sql` *(di bundle BLOK 9)* |
| External API | DDL di bundle + seed opsional |

---

## Kode PHP yang harus di-deploy (working tree fase-2)

| File | Alasan |
|------|--------|
| `app/Models/Production.php` | Filter/list pakai `warehouse_id` header |
| `app/Models/ProductStock.php` | Stok per gudang |
| `app/Models/Product.php` | Perubahan terkait stok/produk |
| `app/Models/ProductVariant.php` | retail_unit / multi-gudang |
| `app/Models/StockOpnameDetail.php` | Opname per gudang |
| `app/Support/ProductionPendingStockRestorer.php` | Restore stok pending produksi |
| `app/Http/Controllers/ProductController.php` | UI/API produk |
| `app/Http/Controllers/StockController.php` | Stok multi-gudang |
| `app/Http/Controllers/StockTransferController.php` | Transfer + traceability |
| `resources/views/layout/partials/footer-scripts.blade.php` | JS global |
| `resources/views/components/modal-popup.blade.php` | Modal |
| `resources/views/components/modals/shared/modal-photo.blade.php` | Modal foto |
| `database/migrations/2026_09_01_000000_add_warehouse_id_to_productions_table.php` | Referensi migration |

**Bisa skip deploy:** file `storage/tmp_*.php`, `tests/*`, seeder dev-only kecuali diminta.

---

## Export dump lokal (dev → export_6)

```powershell
cd "D:\Ruben Data\Kerja Git\OKEJOB\PEGASUS\PMI\pegasus"
php docs/scripts/export_local_db.php
# atau path custom:
php docs/scripts/export_local_db.php database/sql/pegasus_live_fase2_export_6.sql
```

Manual (jika script gagal):

```powershell
$env:MYSQL_PWD = "<password dari .env>"
& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysqldump.exe" `
  -h 127.0.0.1 -P 3306 -u root `
  --single-transaction --routines --triggers `
  --set-gtid-purged=OFF --default-character-set=utf8mb4 `
  --result-file="database\sql\pegasus_live_fase2_export_6.sql" `
  pegasus_production_local
```

---

## Cek setelah deploy

```sql
-- productions.warehouse_id ada
SHOW COLUMNS FROM productions LIKE 'warehouse_id';

-- ref_payment_id ada (Kas Sales)
SHOW COLUMNS FROM cash_sales LIKE 'ref_payment_id';

-- stock transfer traceability
SHOW COLUMNS FROM stock_transfers LIKE 'source_type';

-- stock opname per gudang
SHOW COLUMNS FROM stock_opnames LIKE 'warehouse_id';

-- product_stocks tidak ada duplikat + unique index
SELECT warehouse_id, product_variant_id, unit_id, COUNT(*) cnt
FROM product_stocks WHERE warehouse_id IS NOT NULL
GROUP BY warehouse_id, product_variant_id, unit_id HAVING cnt > 1;
SHOW INDEX FROM product_stocks WHERE Key_name = 'product_stocks_warehouse_variant_unit_unique';
```
