-- Migration: 2026_09_15_090000_widen_customers_customer_code_column
-- Idempotent (MODIFY COLUMN aman diulang). Alternatif: php artisan migrate
--
-- GitHub #171 (PMO): customers.customer_code (id universal Data Armada
-- External API) adalah VARCHAR(10), sedangkan oms_vehicle.kode milik PMO
-- varchar(64) dan tidak dibatasi 10 karakter di sisi PMO. Kode armada PMO
-- saat ini kebetulan muat di 10 karakter, tapi begitu ada kode lebih
-- panjang, POST/PUT /api/external/v1/armada akan menolaknya
-- (validation_failed) atau memotongnya diam-diam. Jalankan SEBELUM PMO
-- mengirim kode armada lebih dari 10 karakter.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers') = 1,
  "ALTER TABLE `customers` MODIFY `customer_code` VARCHAR(64) NULL",
  'SELECT ''skip customers.customer_code'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_15_090000_widen_customers_customer_code_column',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_09_15_090000_widen_customers_customer_code_column');

SELECT '2026_09_15_090000_widen_customers_customer_code_column OK' AS result;
