-- Pemesanan minimum manual per gudang (product_stocks.ps_min_order). Idempotent.
-- Sebelumnya kolom ini hanya ada di database pribadi developer (lihat
-- database/okeh8644_pegasus.sql), tidak pernah dibuat lewat migration atau patch SQL manapun —
-- lihat migration 2026_08_09_130000_add_ps_min_order_to_product_stocks_table.php.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_min_order') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks') = 1,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_min_order` INT NULL DEFAULT NULL AFTER `ps_alert_stock`',
  'SELECT ''product_stocks.ps_min_order sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_09_130000_add_ps_min_order_to_product_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_09_130000_add_ps_min_order_to_product_stocks_table');
