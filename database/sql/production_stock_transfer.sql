-- Production output melalui Stock Transfer + traceability Product Issue.
-- Alternatif: php artisan migrate

SET @c1 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'production_details'
    AND COLUMN_NAME = 'destination_warehouse_id'
);
SET @s1 := IF(@c1 = 0,
  'ALTER TABLE `production_details` ADD COLUMN `destination_warehouse_id` BIGINT UNSIGNED NULL AFTER `unit_id`, ADD INDEX `production_details_destination_wh_idx` (`destination_warehouse_id`)',
  'SELECT ''production_details.destination_warehouse_id sudah ada'' AS info'
);
PREPARE q1 FROM @s1; EXECUTE q1; DEALLOCATE PREPARE q1;

SET @c2 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'source_type'
);
SET @s2 := IF(@c2 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `source_type` VARCHAR(30) NULL AFTER `accept_note`, ADD INDEX `stock_transfers_source_type_idx` (`source_type`)',
  'SELECT ''stock_transfers.source_type sudah ada'' AS info'
);
PREPARE q2 FROM @s2; EXECUTE q2; DEALLOCATE PREPARE q2;

SET @c3 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'source_id'
);
SET @s3 := IF(@c3 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `source_id` BIGINT UNSIGNED NULL AFTER `source_type`, ADD INDEX `stock_transfers_source_id_idx` (`source_id`)',
  'SELECT ''stock_transfers.source_id sudah ada'' AS info'
);
PREPARE q3 FROM @s3; EXECUTE q3; DEALLOCATE PREPARE q3;

SET @c4 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND COLUMN_NAME = 'disposition'
);
SET @s4 := IF(@c4 = 0,
  'ALTER TABLE `stock_transfers` ADD COLUMN `disposition` VARCHAR(30) NULL AFTER `source_id`',
  'SELECT ''stock_transfers.disposition sudah ada'' AS info'
);
PREPARE q4 FROM @s4; EXECUTE q4; DEALLOCATE PREPARE q4;

SET @c5 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_issues' AND COLUMN_NAME = 'source_type'
);
SET @s5 := IF(@c5 = 0,
  'ALTER TABLE `product_issues` ADD COLUMN `source_type` VARCHAR(30) NULL AFTER `pi_notes`, ADD INDEX `product_issues_source_type_idx` (`source_type`)',
  'SELECT ''product_issues.source_type sudah ada'' AS info'
);
PREPARE q5 FROM @s5; EXECUTE q5; DEALLOCATE PREPARE q5;

SET @c6 := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'product_issues' AND COLUMN_NAME = 'source_id'
);
SET @s6 := IF(@c6 = 0,
  'ALTER TABLE `product_issues` ADD COLUMN `source_id` BIGINT UNSIGNED NULL AFTER `source_type`, ADD INDEX `product_issues_source_id_idx` (`source_id`)',
  'SELECT ''product_issues.source_id sudah ada'' AS info'
);
PREPARE q6 FROM @s6; EXECUTE q6; DEALLOCATE PREPARE q6;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_30_030000_add_production_transfer_traceability',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS migration_batches)
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_07_30_030000_add_production_transfer_traceability'
);
