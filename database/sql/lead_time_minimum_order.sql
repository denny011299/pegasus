-- Lead time/safety bahan mentah dimiliki tabel supplies.
-- Aman dijalankan ulang dan aman untuk database dengan schema parsial.

SET @db := DATABASE();

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `supplies_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 0,
    'ALTER TABLE `supplies` ADD COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `lead_time_days`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'lead_time_days') = 1,
    'UPDATE `supplies` s
     INNER JOIN (
         SELECT `supplies_id`, MAX(COALESCE(`lead_time_days`, 0)) AS `lead_time_days`
         FROM `supplies_variants`
         GROUP BY `supplies_id`
     ) sv ON sv.`supplies_id` = s.`supplies_id`
     SET s.`lead_time_days` = GREATEST(COALESCE(s.`lead_time_days`, 0), sv.`lead_time_days`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1
    AND
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 1,
    'UPDATE `supplies` s
     INNER JOIN (
         SELECT `supplies_id`, MAX(COALESCE(`safety_stock`, 0)) AS `safety_stock`
         FROM `supplies_variants`
         GROUP BY `supplies_id`
     ) sv ON sv.`supplies_id` = s.`supplies_id`
     SET s.`safety_stock` = GREATEST(COALESCE(s.`safety_stock`, 0), sv.`safety_stock`)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1,
    'UPDATE `supplies` SET `lead_time_days` = GREATEST(COALESCE(`lead_time_days`, 0), 0)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'lead_time_days') = 1,
    'ALTER TABLE `supplies` MODIFY COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1,
    'UPDATE `supplies` SET `safety_stock` = GREATEST(COALESCE(`safety_stock`, 0), 0)',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies' AND COLUMN_NAME = 'safety_stock') = 1,
    'ALTER TABLE `supplies` MODIFY COLUMN `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'safety_stock') = 1,
    'ALTER TABLE `supplies_variants` DROP COLUMN `safety_stock`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'supplies_variants' AND COLUMN_NAME = 'lead_time_days') = 1,
    'ALTER TABLE `supplies_variants` DROP COLUMN `lead_time_days`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_stock') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `safety_stock` INT NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'safety_unit_id') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `safety_unit_id` INT NULL DEFAULT NULL AFTER `safety_stock`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_variants' AND COLUMN_NAME = 'lead_time_days') = 0,
    'ALTER TABLE `product_variants` ADD COLUMN `lead_time_days` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `product_variant_alert`',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
