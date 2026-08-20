-- Migration: 2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction
-- Idempotent (comment-only MODIFY).

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'sales_orders') = 1,
  'ALTER TABLE `sales_orders` MODIFY `status` TINYINT NOT NULL DEFAULT 1 COMMENT ''1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), 5 = Belum Terkirim (API, belum ada endpoint yang menghasilkan ini), 6 = Sudah Terkirim (API, lewat PATCH /shipments/{ref}/change-status dari status 4 — MEMOTONG STOK sungguhan), 7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed atau Sudah Terkirim)''',
  'SELECT ''skip sales_orders.status comment delivered deduction'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction');

SELECT '2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction OK' AS result;
