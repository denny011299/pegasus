-- Kolom tambahan armada pada tabel customers (category, merk/model, tahun, lokasi)
-- Alternatif: php artisan migrate

-- 1) customer_category
SET @col1 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'customer_category'
);
SET @sql1 := IF(@col1 = 0,
  'ALTER TABLE `customers`
     ADD COLUMN `customer_category` VARCHAR(100) NULL DEFAULT NULL AFTER `customer_notes`',
  'SELECT ''customers.customer_category sudah ada'' AS info'
);
PREPARE s1 FROM @sql1; EXECUTE s1; DEALLOCATE PREPARE s1;

-- 2) customer_merk_model
SET @col2 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'customer_merk_model'
);
SET @sql2 := IF(@col2 = 0,
  'ALTER TABLE `customers`
     ADD COLUMN `customer_merk_model` VARCHAR(255) NULL DEFAULT NULL AFTER `customer_category`',
  'SELECT ''customers.customer_merk_model sudah ada'' AS info'
);
PREPARE s2 FROM @sql2; EXECUTE s2; DEALLOCATE PREPARE s2;

-- 3) customer_tahun_kendaraan
SET @col3 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'customer_tahun_kendaraan'
);
SET @sql3 := IF(@col3 = 0,
  'ALTER TABLE `customers`
     ADD COLUMN `customer_tahun_kendaraan` VARCHAR(20) NULL DEFAULT NULL AFTER `customer_merk_model`',
  'SELECT ''customers.customer_tahun_kendaraan sudah ada'' AS info'
);
PREPARE s3 FROM @sql3; EXECUTE s3; DEALLOCATE PREPARE s3;

-- 4) customer_lokasi
SET @col4 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'customer_lokasi'
);
SET @sql4 := IF(@col4 = 0,
  'ALTER TABLE `customers`
     ADD COLUMN `customer_lokasi` VARCHAR(255) NULL DEFAULT NULL AFTER `customer_tahun_kendaraan`',
  'SELECT ''customers.customer_lokasi sudah ada'' AS info'
);
PREPARE s4 FROM @sql4; EXECUTE s4; DEALLOCATE PREPARE s4;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_22_090000_add_armada_fields_to_customers_table', (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_22_090000_add_armada_fields_to_customers_table'
);
