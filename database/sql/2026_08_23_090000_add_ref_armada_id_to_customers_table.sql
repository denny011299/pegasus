-- Migration: 2026_08_23_090000_add_ref_armada_id_to_customers_table
-- Idempotent (cek kolom dulu). Alternatif: php artisan migrate
--
-- Rujukan PMO untuk armada (kendaraan), disimpan sebagai baris customers --
-- sama pola dengan units.ref_unit_id/products.ref_product_id. BIGINT
-- UNSIGNED dari awal karena id PMO sudah terbukti 16 digit. Terpisah dari
-- customers.customer_code (id universal milik modul External API Armada,
-- sumbernya berbeda sama sekali).

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'ref_armada_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'customers') = 1,
  "ALTER TABLE `customers`
     ADD COLUMN `ref_armada_id` BIGINT UNSIGNED NULL COMMENT 'armada_id pada sistem PMO' AFTER `customer_code`,
     ADD UNIQUE INDEX `customers_ref_armada_id_unique` (`ref_armada_id`)",
  'SELECT ''skip customers.ref_armada_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_23_090000_add_ref_armada_id_to_customers_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_23_090000_add_ref_armada_id_to_customers_table');

SELECT '2026_08_23_090000_add_ref_armada_id_to_customers_table OK' AS result;
