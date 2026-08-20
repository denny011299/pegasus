-- Migration: 2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'cancel_reason') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` ADD COLUMN `cancel_reason` TEXT NULL COMMENT ''Alasan pembatalan (PUT /shipments/{ref}/cancel, body.reason)'' AFTER `notes`',
  'SELECT ''skip sales_orders.cancel_reason'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), 5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), 6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), 7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed)''',
  'SELECT ''skip sales_orders.status comment 7'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders');

SELECT '2026_08_12_120000_add_cancel_reason_and_cancelled_status_to_sales_orders OK' AS result;
