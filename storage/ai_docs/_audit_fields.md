# Audit Field Report

Sumber: migrations + model write paths (2026-10-04).  
**Peringatan:** banyak migration stub/incomplete; kolom `created_by` sering di-set di model tapi **tidak ada di migration**. Live DB bisa berbeda — verifikasi dengan `SHOW COLUMNS` sebelum mengandalkan klasifikasi duplikat.

## Ringkasan

| Field | Di migration | Di model (runtime) | Catatan |
|-------|--------------|--------------------|---------|
| `created_at` / `updated_at` | Hampir semua tabel bisnis (`timestamps()`) | Ya | OK untuk gap waktu |
| `created_by` | **Hampir tidak ada** | Banyak model menulis | Drift schema |
| `updated_by` | **Tidak ada** | **Tidak ada** | Tidak bisa lacak siapa edit terakhir |
| `source` (web/api/import) | **Tidak ada** (kecuali `cash_armadas.source_cgd_id`) | Tidak ada generik | Klasifikasi "system vs human" lemah |
| Audit log terpusat | Tidak ada | — | Partial: `log_stocks`, `dashboard_change_logs` |

## Kolom audit khusus (ada di migration)

| Table | Audit-related columns |
|-------|----------------------|
| `productions` | `production_created_by`, timestamps |
| `manage_stocks` | `ms_created_by`, timestamps |
| `cash_armadas` | `source_cgd_id` (FK ke cash_gudang_detail, bukan source channel), timestamps |
| `log_stocks` | timestamps; model juga pakai `staff_id`, `log_type`, `log_item_id`, `log_kode` (kolom ini **tidak lengkap di migration**) |
| `dashboard_change_logs` | model: `created_by`, `activity_type`, `duration_seconds` |

## Model yang menulis `created_by` (atau setara)

Category, Unit, Variant, Product, ProductVariant, ProductStock, ProductRelation, ProductIssues, Supplies, SuppliesVariant, SuppliesStock, Customer, Supplier, Staff, Bank, CashCategory, Cash, CashAdmin, CashGudang, CashArmada, CashSales, PettyCash, PurchaseOrder, Bom, BomDetail, StockOpname, StockOpnameBahan, ManageStock (`created_by` + `ms_created_by`), ProductionDetails, purchase_order_tt, DashboardChangeLog.

- **Productions:** `production_created_by` (bukan `created_by`)
- **SalesOrder:** `created_by` / `acc_by` hanya jika kolom ada (`Schema::hasColumn`)

## Tabel tanpa jejak creator di migration

Hampir semua tabel transaksi & master selain `productions` / `manage_stocks`. Detail, delivery, invoice, stock lines, dll. umumnya hanya timestamps di migration.

## Yang sebaiknya ditambahkan (JANGAN ditambahkan sekarang — laporan saja)

Untuk tool `record_history` + klasifikasi duplikat sesuai system prompt:

1. **`created_by`** (int, staff_id, nullable) — semua tabel transaksi header + master yang belum punya.
2. **`updated_by`** (int, staff_id, nullable) — sama, diisi setiap update.
3. **`source`** (varchar, e.g. `web` / `api` / `import` / `system`) — minimal di header transaksi (PO, SO, production, cash_*, stock_opname*, product_issues, manage_stocks).
4. **Samakan nama:** prefer `created_by` di `productions` (alias/migrate dari `production_created_by`) dan `manage_stocks` (dari `ms_created_by`).
5. **Approval trail:** `acc_by`, `acc_at` (dan optional `decline_by`) pada dokumen yang punya ACC/tolak.
6. **Optional:** tabel `audit_logs` (table, record_id, action, old/new JSON, staff_id, created_at) untuk perubahan field.
7. **Lengkapi migration `log_stocks`** agar sesuai model (staff_id, log_type, log_category, log_item_id, status, dll.) — atau generate schema dump resmi.

Tanpa (1)+(3), kesimpulan duplikat sering jatuh ke **Unclear**.
