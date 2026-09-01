-- =============================================================================
-- PEGASUS — Dedup product_stocks + unique (warehouse_id, product_variant_id, unit_id)
-- Aman dijalankan ulang (idempotent).
-- Jalankan SETELAH backup DB.
-- =============================================================================

SET NAMES utf8mb4;

-- 1) Gabung qty/safety/alert ke satu baris per grup (prioritas: status=1, stok tertinggi, ps_id terkecil)
UPDATE product_stocks ps
INNER JOIN (
    SELECT
        warehouse_id,
        product_variant_id,
        unit_id,
        SUBSTRING_INDEX(
            GROUP_CONCAT(ps_id ORDER BY status DESC, ps_stock DESC, ps_id ASC),
            ',', 1
        ) AS keep_id,
        SUM(ps_stock) AS total_stock,
        MAX(ps_safety_stock) AS max_safety,
        MAX(ps_alert_stock) AS max_alert
    FROM product_stocks
    WHERE warehouse_id IS NOT NULL
    GROUP BY warehouse_id, product_variant_id, unit_id
    HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
   AND ps.ps_id = d.keep_id
SET ps.ps_stock = d.total_stock,
    ps.ps_safety_stock = d.max_safety,
    ps.ps_alert_stock = d.max_alert,
    ps.status = 1,
    ps.updated_at = NOW();

-- 2) Nonaktifkan duplikat (lepaskan dari unique key)
UPDATE product_stocks ps
INNER JOIN (
    SELECT
        warehouse_id,
        product_variant_id,
        unit_id,
        SUBSTRING_INDEX(
            GROUP_CONCAT(ps_id ORDER BY status DESC, ps_stock DESC, ps_id ASC),
            ',', 1
        ) AS keep_id
    FROM product_stocks
    WHERE warehouse_id IS NOT NULL
    GROUP BY warehouse_id, product_variant_id, unit_id
    HAVING COUNT(*) > 1
) d ON d.warehouse_id = ps.warehouse_id
   AND d.product_variant_id = ps.product_variant_id
   AND d.unit_id = ps.unit_id
   AND ps.ps_id <> d.keep_id
SET ps.status = 0,
    ps.warehouse_id = NULL,
    ps.ps_stock = 0,
    ps.updated_at = NOW();

-- 3) Unique index (hanya jika tidak ada duplikat aktif)
SET @ps_dup := (
    SELECT COUNT(*) FROM (
        SELECT warehouse_id, product_variant_id, unit_id, COUNT(*) AS c
        FROM product_stocks
        WHERE warehouse_id IS NOT NULL
        GROUP BY warehouse_id, product_variant_id, unit_id
        HAVING c > 1
    ) x
);

SET @idx_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'product_stocks'
      AND INDEX_NAME = 'product_stocks_warehouse_variant_unit_unique'
);

SET @sql_uq := IF(
    @ps_dup = 0 AND @idx_exists = 0,
    'ALTER TABLE `product_stocks`
       ADD UNIQUE INDEX `product_stocks_warehouse_variant_unit_unique`
         (`warehouse_id`, `product_variant_id`, `unit_id`)',
    'SELECT ''skip unique index (duplikat atau sudah ada)'' AS info'
);

PREPARE stmt_uq FROM @sql_uq;
EXECUTE stmt_uq;
DEALLOCATE PREPARE stmt_uq;

SELECT
    @ps_dup AS remaining_duplicate_groups,
    @idx_exists AS unique_index_already_present;
