-- Migration: 2026_08_11_120000_add_unique_index_to_customers_customer_code
-- Idempotent.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers' AND INDEX_NAME = 'customers_customer_code_unique') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers') = 1,
  'ALTER TABLE `customers` ADD UNIQUE INDEX `customers_customer_code_unique` (`customer_code`)',
  'SELECT ''skip customers.customer_code unique'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_11_120000_add_unique_index_to_customers_customer_code',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_11_120000_add_unique_index_to_customers_customer_code');

SELECT '2026_08_11_120000_add_unique_index_to_customers_customer_code OK' AS result;
