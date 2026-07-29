# Parked migrations

These files are **not executed**. Laravel's migrator globs `database/migrations/*_*.php`
non-recursively, so anything in this subfolder is ignored. They are kept only as history.

On 2026-07-26 the migration set was resynced against the live `pegasus` schema, because
migrations had drifted badly: most files were never recorded as run, several tables had been
renamed directly in the database, and some schema arrived via raw SQL dumps. Every file below
was superseded by that resync.

## Tables that no longer exist in the database

Dropped in the database at some point; no migration should recreate them.
Models still pointing at these tables are already broken against the live schema:

| File | Table | Model(s) still referencing it |
| --- | --- | --- |
| `2025_08_17_133135_create_stocks_table.php` | `stocks` | `Stock` |
| `2025_08_12_135951_create_stock_alerts_table.php` | `stock_alerts` | `StockAlert`, `StockAlertSupplies` |
| `2025_08_13_122415_create_pengaturans_table.php` | `pengaturans` | `Pengaturan` |
| `2025_08_12_135910_create_report_profits_table.php` | `report_profits` | `ReportProfit` (points at `profits`) |
| `2025_08_12_142312_create_report_losses_table.php` | `report_losses` | `ReportLoss` (points at `losses`) |
| `2025_08_15_152806_create_inward_outwards_table.php` | `inward_outwards` | `InwardOutward` |
| `2025_08_18_093725_create_purchase_order_receipts_table.php` | `purchase_order_receipts` | `PurchaseOrderReceipt` |

## Superseded by a renamed file

The table was renamed directly in the database; the migration kept the old name.

| File | Old name | Current table |
| --- | --- | --- |
| `2025_08_18_093629_create_purchase_order_deliveries_table.php` | `purchase_order_deliveries` | `purchase_delivery_orders` |
| `2025_10_04_134705_create_purchase_order_delivery_details_table.php` | `purchase_order_delivery_details` | `purchase_delivery_orders_details` |
| `2025_12_03_132830_create_sales_order_delivery_details_table.php` | `sales_order_delivery_details` | `sales_delivery_orders_details` |
| `2025_08_19_175925_create_staff_table.php` | `staff` | `staffs` |
| `2026_02_21_154007_create_return_supplies_details_table.php` | `return_supplies_details` | `return_supplies_detail` |

## Redundant

- `2025_08_12_173313_create_product_issues_table.php` — duplicate `Schema::create('product_issues')`;
  the surviving file is `2025_07_31_062158_create_product_issues_table.php`.
- `2026_04_25_123000_add_so_ref_number_to_sales_orders_table.php` — `so_ref_number` is now part of
  the regenerated `create_sales_orders_table`, so running this again would fail on a duplicate column.
