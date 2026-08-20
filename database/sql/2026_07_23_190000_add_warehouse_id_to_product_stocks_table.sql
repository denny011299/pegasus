-- =============================================================================
-- PEGASUS - product_stocks.warehouse_id (stok per gudang)
-- Jalankan SETELAH warehouse_types + warehouses sudah ada.
-- Aman dijalankan ulang (cek kolom / index).
-- Alternatif: php artisan migrate
--   (migration: 2026_07_23_190000_add_warehouse_id_to_product_stocks_table)
-- =============================================================================

-- 1) Tambah kolom warehouse_id
SET @col_ps_wh_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_stocks'
    AND COLUMN_NAME = 'warehouse_id'
);

SET @sql_ps_wh := IF(
  @col_ps_wh_exists = 0,
  'ALTER TABLE `product_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `unit_id`,
     ADD INDEX `product_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''kolom product_stocks.warehouse_id sudah ada'' AS info'
);

PREPARE stmt_ps_wh FROM @sql_ps_wh;
EXECUTE stmt_ps_wh;
DEALLOCATE PREPARE stmt_ps_wh;

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
UPDATE product_stocks
SET warehouse_id = @main_wh_id
WHERE warehouse_id IS NULL
  AND @main_wh_id IS NOT NULL;

-- 4) Seed stok 0 ke gudang aktif lain (kombinasi dari stok gudang utama)
INSERT INTO product_stocks (
  product_variant_id,
  product_id,
  unit_id,
  warehouse_id,
  ps_stock,
  status,
  created_at,
  updated_at,
  created_by
)
SELECT
  ps.product_variant_id,
  ps.product_id,
  ps.unit_id,
  w.id AS warehouse_id,
  0 AS ps_stock,
  1 AS status,
  NOW() AS created_at,
  NOW() AS updated_at,
  NULL AS created_by
FROM warehouses w
CROSS JOIN (
  SELECT DISTINCT product_variant_id, product_id, unit_id
  FROM product_stocks
  WHERE status = 1
    AND warehouse_id = @main_wh_id
) ps
WHERE w.status = 1
  AND w.id <> @main_wh_id
  AND @main_wh_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1
    FROM product_stocks x
    WHERE x.warehouse_id = w.id
      AND x.product_variant_id = ps.product_variant_id
      AND x.unit_id = ps.unit_id
      AND x.status = 1
  );

-- 5) Dedup sebelum unique: lepaskan duplikat non-aktif dari unique key
UPDATE product_stocks ps
INNER JOIN (
  SELECT warehouse_id, product_variant_id, unit_id, MIN(ps_id) AS keep_id
  FROM product_stocks
  WHERE warehouse_id IS NOT NULL
  GROUP BY warehouse_id, product_variant_id, unit_id
  HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
SET ps.status = 0,
    ps.warehouse_id = NULL,
    ps.updated_at = NOW()
WHERE ps.ps_id <> d.keep_id
  AND (ps.status = 0 OR ps.ps_stock = 0 OR ps.ps_id > d.keep_id);

-- Prefer keep baris status=1 / stock tertinggi (ulang pass sederhana):
-- (opsional; aman jika sudah bersih)

-- 6) Unique index (warehouse + variant + unit)
SET @idx_exists := (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'product_stocks'
    AND INDEX_NAME = 'product_stocks_warehouse_variant_unit_unique'
);

SET @sql_idx := IF(
  @idx_exists = 0,
  'ALTER TABLE `product_stocks`
     ADD UNIQUE INDEX `product_stocks_warehouse_variant_unit_unique`
       (`warehouse_id`, `product_variant_id`, `unit_id`)',
  'SELECT ''unique index sudah ada'' AS info'
);

PREPARE stmt_idx FROM @sql_idx;
EXECUTE stmt_idx;
DEALLOCATE PREPARE stmt_idx;

-- 7) Catat di tabel migrations (supaya artisan migrate tidak conflict)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_23_190000_add_warehouse_id_to_product_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_23_190000_add_warehouse_id_to_product_stocks_table'
);

SELECT
  @main_wh_id AS main_warehouse_id,
  (SELECT COUNT(*) FROM product_stocks WHERE status = 1) AS active_stock_rows,
  (SELECT COUNT(DISTINCT warehouse_id) FROM product_stocks WHERE status = 1 AND warehouse_id IS NOT NULL) AS warehouses_with_stock;
