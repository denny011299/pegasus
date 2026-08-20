-- Migration: 2026_08_20_090000_widen_pmo_reference_id_columns
-- Idempotent (MODIFY COLUMN aman diulang). Alternatif: php artisan migrate
--
-- BLOCKING begitu Sinkronisasi PMO dipakai: units.ref_unit_id dan
-- products.ref_product_id menyimpan id PMO apa adanya, dan id PMO nyatanya
-- 16 digit (mis. unit_id 9506012026014615) -- jauh melewati batas INT
-- (~2,1 miliar). Sinkronisasi Satuan crash "Out of range value for column
-- 'ref_unit_id'" tanpa kolom ini dilebarkan. Jalankan SEBELUM Sinkronisasi
-- PMO dipakai di server ini.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'units') = 1,
  "ALTER TABLE `units` MODIFY `ref_unit_id` BIGINT UNSIGNED NULL COMMENT 'unit_id pada sistem PMO'",
  'SELECT ''skip units.ref_unit_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products') = 1,
  "ALTER TABLE `products` MODIFY `ref_product_id` BIGINT UNSIGNED NULL COMMENT 'ref_product_id pada sistem PMO'",
  'SELECT ''skip products.ref_product_id'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_20_090000_widen_pmo_reference_id_columns',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_20_090000_widen_pmo_reference_id_columns');

SELECT '2026_08_20_090000_widen_pmo_reference_id_columns OK' AS result;
