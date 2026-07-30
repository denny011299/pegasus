-- Safety Stock: kolom + permission
-- Alternatif: php artisan migrate

-- 1) product_variants
SET @col1 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_stock'
);
SET @sql1 := IF(@col1 = 0,
  'ALTER TABLE `product_variants`
     ADD COLUMN `safety_stock` INT NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
  'SELECT ''product_variants.safety_stock sudah ada'' AS info'
);
PREPARE s1 FROM @sql1; EXECUTE s1; DEALLOCATE PREPARE s1;

SET @col1_unit := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_unit_id'
);
SET @sql1_unit := IF(@col1_unit = 0,
  'ALTER TABLE `product_variants`
     ADD COLUMN `safety_unit_id` INT NULL DEFAULT NULL AFTER `safety_stock`',
  'SELECT ''product_variants.safety_unit_id sudah ada'' AS info'
);
PREPARE s1_unit FROM @sql1_unit; EXECUTE s1_unit; DEALLOCATE PREPARE s1_unit;

-- 2) product_stocks
SET @col2 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_stocks' AND COLUMN_NAME = 'ps_safety_stock'
);
SET @sql2 := IF(@col2 = 0,
  'ALTER TABLE `product_stocks`
     ADD COLUMN `ps_safety_stock` INT NOT NULL DEFAULT 0 AFTER `ps_stock`',
  'SELECT ''product_stocks.ps_safety_stock sudah ada'' AS info'
);
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;

-- 3) Permission role (Developer / Superadmin / Direksi)
UPDATE roles
SET role_access = JSON_ARRAY_APPEND(
  IFNULL(role_access, '[]'),
  '$',
  '{"name":"Safety Stock","akses":["view","edit","create","delete","others"]}'
)
WHERE status = 1
  AND (
    role_name LIKE '%Developer%'
    OR role_name = 'Direksi'
    OR role_name LIKE '%Superadmin%'
    OR role_name LIKE '%Super Admin%'
  )
  AND role_access NOT LIKE '%"name":"Safety Stock"%';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_24_140000_add_safety_stock_columns', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_24_140000_add_safety_stock_columns'
);
