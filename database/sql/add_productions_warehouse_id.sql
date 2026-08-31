-- =============================================================================
-- productions.warehouse_id — gudang asal produksi (header)
-- Filter list produksi pakai kolom ini, BUKAN destination_warehouse_id di detail.
-- Default / backfill data lama = 1 (gudang utama Hikari).
-- Jalankan di phpMyAdmin / mysql CLI. Abaikan "Duplicate column name" jika sudah ada.
-- =============================================================================

SET NAMES utf8mb4;

ALTER TABLE `productions`
  ADD COLUMN `warehouse_id` BIGINT UNSIGNED NOT NULL DEFAULT 1
  AFTER `production_created_by`;

ALTER TABLE `productions`
  ADD INDEX `productions_warehouse_id_index` (`warehouse_id`);

-- Pastikan semua baris lama = gudang utama (biasanya ID 1)
SET @main_wh_id := (
  SELECT w.`id`
  FROM `warehouses` w
  INNER JOIN `warehouse_types` wt ON wt.`id` = w.`warehouse_type_id`
  WHERE w.`status` = 1 AND wt.`is_main_warehouse` = 1
  ORDER BY w.`id`
  LIMIT 1
);

SET @main_wh_id := IFNULL(@main_wh_id, 1);

UPDATE `productions`
SET `warehouse_id` = @main_wh_id, `updated_at` = NOW()
WHERE `warehouse_id` IS NULL OR `warehouse_id` = 0;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_09_01_000000_add_warehouse_id_to_productions_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (
    SELECT 1 FROM `migrations`
    WHERE `migration` = '2026_09_01_000000_add_warehouse_id_to_productions_table'
  );

SELECT
  @main_wh_id AS main_warehouse_id,
  COUNT(*) AS total_productions,
  SUM(CASE WHEN warehouse_id = @main_wh_id THEN 1 ELSE 0 END) AS productions_di_gudang_utama,
  'add_productions_warehouse_id OK' AS result
FROM `productions`;
