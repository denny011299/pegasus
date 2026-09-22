-- Kolom Sinkronisasi Pengiriman PMO (Spec B) di sales_orders. Aman diulang.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders' AND COLUMN_NAME = 'pmo_sync_note') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders`
     ADD COLUMN `pmo_sync_note` TEXT NULL AFTER `reject_reason`,
     ADD COLUMN `pmo_synced_at` TIMESTAMP NULL AFTER `pmo_sync_note`,
     ADD COLUMN `pmo_bukti_foto` TEXT NULL AFTER `pmo_synced_at`',
  'SELECT ''skip sales_orders pmo sync columns'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_23_090000_add_pmo_sync_columns_to_sales_orders_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_23_090000_add_pmo_sync_columns_to_sales_orders_table');
