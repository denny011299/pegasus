-- Migration: 2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders
-- Idempotent (comment-only MODIFY).

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), 5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), 6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status)''',
  'SELECT ''skip sales_orders.status comment 5/6'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders');

SELECT '2026_08_12_110000_add_not_delivered_and_delivered_statuses_to_sales_orders OK' AS result;
