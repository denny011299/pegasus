-- =============================================================================
-- PEGASUS - warehouses.sidebar_menus (whitelist menu sidebar per gudang)
-- Alternatif: php artisan migrate
--   (migration: 2026_07_24_170000_add_sidebar_menus_to_warehouses_table)
-- NULL / [] = semua menu diizinkan (backward compatible)
-- =============================================================================

SET @col_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'warehouses'
    AND COLUMN_NAME = 'sidebar_menus'
);

SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `warehouses`
     ADD COLUMN `sidebar_menus` JSON NULL DEFAULT NULL AFTER `warehouse_address`',
  'SELECT ''kolom warehouses.sidebar_menus sudah ada'' AS info'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_170000_add_sidebar_menus_to_warehouses_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_24_170000_add_sidebar_menus_to_warehouses_table'
);

SELECT
  (SELECT COUNT(*) FROM warehouses WHERE sidebar_menus IS NULL) AS allow_all_rows,
  (SELECT COUNT(*) FROM warehouses WHERE sidebar_menus IS NOT NULL) AS whitelist_rows;
