-- Peringatan stok per gudang (product_stocks.ps_alert_stock). Idempotent.

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_alert_stock') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks') = 1,
  'ALTER TABLE `product_stocks` ADD COLUMN `ps_alert_stock` INT NOT NULL DEFAULT 0 AFTER `ps_safety_stock`',
  'SELECT ''product_stocks.ps_alert_stock sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_221000_add_ps_alert_stock_to_product_stocks_table');
