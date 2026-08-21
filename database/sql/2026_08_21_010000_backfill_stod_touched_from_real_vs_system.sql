-- Migration: 2026_08_21_010000_backfill_stod_touched_from_real_vs_system
-- Idempotent (re-running only affects rows still stuck at touched=0). Alternatif: php artisan migrate
--
-- GitHub #53 follow-up: stod_touched/stobd_touched (2026_08_14_010000_add_touched_to_stock_opname_
-- ..._tables) were added with default(false) and never backfilled -- every row written before that
-- migration is stuck "untouched", so the PDF highlight (Backoffice/PDF/Opname.blade.php +
-- OpnameBahan.blade.php) silently shows NO color for those documents' rows, whether they had a
-- genuine selisih (should be yellow) or matched exactly (should be green).
--
-- Only the deterministic half is backfillable: if stod_real != stod_system, that can only exist
-- because a staff member typed it (the JS always defaults real = system when left blank), so it's
-- safe to mark touched=1. Rows where real == system stay untouched -- unrecoverably ambiguous
-- whether that was a typed match or a blank field, see the migration file for the full writeup.

SET @db := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_details' AND COLUMN_NAME = 'stod_touched') = 1,
  'UPDATE `stock_opname_details`
     SET `stod_touched` = 1
     WHERE `stod_touched` = 0 AND `stod_real` <> `stod_system`',
  'SELECT ''skip stock_opname_details backfill'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'stock_opname_detail_bahans' AND COLUMN_NAME = 'stobd_touched') = 1,
  'UPDATE `stock_opname_detail_bahans`
     SET `stobd_touched` = 1
     WHERE `stobd_touched` = 0 AND `stobd_real` <> `stobd_system`',
  'SELECT ''skip stock_opname_detail_bahans backfill'' AS info'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_21_010000_backfill_stod_touched_from_real_vs_system',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_21_010000_backfill_stod_touched_from_real_vs_system');

SELECT '2026_08_21_010000_backfill_stod_touched_from_real_vs_system OK' AS result;
