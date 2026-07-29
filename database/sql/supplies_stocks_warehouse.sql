-- =============================================================================
-- PEGASUS - supplies_stocks.warehouse_id (stok bahan mentah per gudang)
-- Jalankan SETELAH warehouse_types + warehouses sudah ada.
-- Aman dijalankan ulang (cek kolom / index).
-- Alternatif: php artisan migrate
--   (migration: 2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table)
-- =============================================================================

-- 1) Tambah kolom warehouse_id
SET @col_ss_wh_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'supplies_stocks'
    AND COLUMN_NAME = 'warehouse_id'
);

SET @sql_ss_wh := IF(
  @col_ss_wh_exists = 0,
  'ALTER TABLE `supplies_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD INDEX `supplies_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''kolom supplies_stocks.warehouse_id sudah ada'' AS info'
);

PREPARE stmt_ss_wh FROM @sql_ss_wh;
EXECUTE stmt_ss_wh;
DEALLOCATE PREPARE stmt_ss_wh;

-- 2) Cari gudang utama (tipe is_main_warehouse = 1), fallback gudang aktif pertama
SET @main_wh_id := (
  SELECT w.id
  FROM warehouses w
  INNER JOIN warehouse_types wt ON wt.id = w.warehouse_type_id
  WHERE w.status = 1
    AND wt.status = 1
    AND wt.is_main_warehouse = 1
  ORDER BY w.id
  LIMIT 1
);

SET @main_wh_id := IFNULL(
  @main_wh_id,
  (SELECT id FROM warehouses WHERE status = 1 ORDER BY id LIMIT 1)
);

-- 3) Backfill stok lama → gudang utama
UPDATE supplies_stocks
SET warehouse_id = @main_wh_id
WHERE warehouse_id IS NULL
  AND @main_wh_id IS NOT NULL;

-- 4) Seed stok 0 ke gudang aktif lain (kombinasi dari stok gudang utama)
INSERT INTO supplies_stocks (
  supplies_id,
  unit_id,
  warehouse_id,
  ss_stock,
  status,
  created_at,
  updated_at,
  created_by
)
SELECT
  ss.supplies_id,
  ss.unit_id,
  w.id AS warehouse_id,
  0 AS ss_stock,
  1 AS status,
  NOW() AS created_at,
  NOW() AS updated_at,
  NULL AS created_by
FROM warehouses w
CROSS JOIN (
  SELECT DISTINCT supplies_id, unit_id
  FROM supplies_stocks
  WHERE status = 1
    AND warehouse_id = @main_wh_id
) ss
WHERE w.status = 1
  AND w.id <> @main_wh_id
  AND @main_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM supplies_stocks x
    WHERE x.warehouse_id = w.id
      AND x.supplies_id = ss.supplies_id
      AND x.unit_id = ss.unit_id
      AND x.status = 1
  );

-- 5) Dedup sebelum unique: nonaktifkan duplikat
UPDATE supplies_stocks ss
INNER JOIN (
  SELECT warehouse_id, supplies_id, unit_id, MIN(ss_id) AS keep_id
  FROM supplies_stocks
  WHERE warehouse_id IS NOT NULL
  GROUP BY warehouse_id, supplies_id, unit_id
  HAVING COUNT(*) > 1
) d ON d.warehouse_id = ss.warehouse_id
   AND d.supplies_id = ss.supplies_id
   AND d.unit_id = ss.unit_id
SET ss.status = 0,
    ss.warehouse_id = NULL,
    ss.updated_at = NOW()
WHERE ss.ss_id <> d.keep_id;

-- 6) Unique index (warehouse + supplies + unit)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'supplies_stocks'
    AND INDEX_NAME = 'supplies_stocks_warehouse_supplies_unit_unique'
);

SET @sql_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `supplies_stocks`
     ADD UNIQUE INDEX `supplies_stocks_warehouse_supplies_unit_unique`
       (`warehouse_id`, `supplies_id`, `unit_id`)',
  'SELECT ''unique index sudah ada'' AS info'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- 7) Catat di tabel migrations
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_24_150000_add_warehouse_id_to_supplies_stocks_table'
);

SELECT
  @main_wh_id AS main_warehouse_id,
  (SELECT COUNT(*) FROM supplies_stocks WHERE status = 1) AS active_stock_rows,
  (SELECT COUNT(DISTINCT warehouse_id) FROM supplies_stocks WHERE status = 1 AND warehouse_id IS NOT NULL) AS warehouses_with_stock;
