-- log_stocks.warehouse_id — Idempotent + backfill Stock Transfer

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks' AND COLUMN_NAME = 'warehouse_id') = 0
  AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_stocks') = 1,
  'ALTER TABLE `log_stocks`
     ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL AFTER `staff_id`,
     ADD INDEX `log_stocks_warehouse_id_index` (`warehouse_id`)',
  'SELECT ''log_stocks.warehouse_id sudah ada'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE log_stocks l
INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
SET l.warehouse_id = st.from_warehouse_id
WHERE l.warehouse_id IS NULL
  AND l.log_kode LIKE 'ST%'
  AND (
    l.log_notes LIKE '%keluar gudang asal%'
    OR l.log_notes LIKE '%kembalikan stok%'
    OR l.log_notes LIKE '%bongkar%'
    OR l.log_notes LIKE '%hasil bongkar%'
    OR l.log_notes LIKE '%koreksi edit%'
  );

UPDATE log_stocks l
INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
SET l.warehouse_id = st.to_warehouse_id
WHERE l.warehouse_id IS NULL
  AND l.log_kode LIKE 'ST%'
  AND l.log_notes LIKE '%masuk gudang tujuan%';

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_000100_add_warehouse_id_to_log_stocks_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_000100_add_warehouse_id_to_log_stocks_table');
