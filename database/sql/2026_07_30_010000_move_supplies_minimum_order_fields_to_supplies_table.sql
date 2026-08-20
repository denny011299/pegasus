-- Migration: 2026_07_30_010000_move_supplies_minimum_order_fields_to_supplies_table
-- Idempotent. Logic from lead_time_minimum_order.sql (supplies move/drop only).

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

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_010000_move_supplies_minimum_order_fields_to_supplies_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_30_010000_move_supplies_minimum_order_fields_to_supplies_table');

SELECT '2026_07_30_010000_move_supplies_minimum_order_fields_to_supplies_table OK' AS result;
