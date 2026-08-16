-- =============================================================================
-- Kepala operasional gudang: flag di pivot staff_warehouses
-- Satu gudang = maksimal satu baris is_kepala_cabang = 1 (dijaga di aplikasi).
-- Aman diulang.
-- =============================================================================

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db
     AND TABLE_NAME = 'staff_warehouses'
     AND COLUMN_NAME = 'is_kepala_cabang') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'staff_warehouses') = 1,
  'ALTER TABLE `staff_warehouses`
     ADD COLUMN `is_kepala_cabang` TINYINT(1) NOT NULL DEFAULT 0 AFTER `warehouse_id`,
     ADD INDEX `staff_warehouses_warehouse_kepala_index` (`warehouse_id`, `is_kepala_cabang`)',
  'SELECT ''skip is_kepala_cabang'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
