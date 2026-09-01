-- ============================================================================
-- Resequence stock_opname_bahans.stob_id / stob_code to close gaps left by
-- deleted rows, cascading the id change into every table that stores stob_id
-- as a loose reference (stock_opname_detail_bahans, stock_opname_bahan_lines).
--
-- Mirror of resequence_stock_opnames.sql for the Bahan (supplies) side of
-- Stock Opname -- same mechanism, different table/column names.
--
-- NOTE: this repo has 0 real foreign keys (all references are "loose"/app-level,
-- see migration comments in stock_opname_bahan_lines). So this script IS the
-- cascade -- there is no DB-level ON UPDATE CASCADE doing it for you.
--
-- BEFORE RUNNING:
--   1. Take a full backup / run inside a transaction and verify before COMMIT.
--   2. Double check no other table you've added since references stob_id
--      (grep -rn "stob_id" app/Models  ->  as of 2026-09-02: StockOpnameBahan,
--      StockOpnameDetailBahan, StockOpnameBahanLine only).
--   3. Run during a maintenance window — app must not write to these tables
--      while this runs.
-- ============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 0. Filter: only rows with stob_id >= @start_from are remapped. Everything
--    below this stays completely untouched (id and code unchanged). The
--    remapped rows are renumbered starting AT @start_from, so they stay
--    contiguous with the untouched rows below it (no gap, no overlap) —
--    e.g. @start_from := 50 turns [50, 52, 53, 60, ...] into [50, 51, 52, 53, ...].
-- ---------------------------------------------------------------------------
SET @start_from := 50;

-- ---------------------------------------------------------------------------
-- 1. Build the old_id -> new_id / new_code mapping, ordered by current stob_id
--    (i.e. current chronological/creation order) so relative order is kept.
--    Only rows >= @start_from are included -- rows below it are left alone.
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS stob_id_map;
CREATE TEMPORARY TABLE stob_id_map (
  old_id   INT UNSIGNED PRIMARY KEY,
  new_id   INT UNSIGNED NOT NULL,
  new_code VARCHAR(6)   NOT NULL
);

SET @rownum := @start_from - 1;

INSERT INTO stob_id_map (old_id, new_id, new_code)
SELECT
  stob_id,
  (@rownum := @rownum + 1) AS new_id,
  CONCAT(
    prefix,
    LPAD(@rownum, LENGTH(stob_code) - LENGTH(prefix), '0')
  ) AS new_code
FROM (
  SELECT stob_id, stob_code, REGEXP_REPLACE(stob_code, '[0-9]+$', '') AS prefix
  FROM stock_opname_bahans
  WHERE stob_id >= @start_from
  ORDER BY stob_id ASC
) t;

-- Sanity check: mapping should cover every row and new_id must be dense from @start_from
SELECT COUNT(*) AS mapped_rows, MAX(new_id) AS max_new_id FROM stob_id_map;

-- ---------------------------------------------------------------------------
-- 2. Phase A: move every row to a temporary, guaranteed-non-colliding id
--    range (old_id + large offset) so phase B's UPDATE never hits a
--    duplicate-key error against a not-yet-moved row.
-- ---------------------------------------------------------------------------
SET @offset := 1000000;

UPDATE stock_opname_bahans so
JOIN stob_id_map m ON m.old_id = so.stob_id
SET so.stob_id = m.old_id + @offset;

UPDATE stock_opname_detail_bahans d
JOIN stob_id_map m ON m.old_id = d.stob_id
SET d.stob_id = m.old_id + @offset;

UPDATE stock_opname_bahan_lines l
JOIN stob_id_map m ON m.old_id = l.stob_id
SET l.stob_id = m.old_id + @offset;

-- ---------------------------------------------------------------------------
-- 3. Phase B: move every row from the offset id to its final, dense id and
--    write the resequenced code at the same time.
-- ---------------------------------------------------------------------------
UPDATE stock_opname_bahans so
JOIN stob_id_map m ON m.old_id + @offset = so.stob_id
SET so.stob_id = m.new_id,
    so.stob_code = m.new_code;

UPDATE stock_opname_detail_bahans d
JOIN stob_id_map m ON m.old_id + @offset = d.stob_id
SET d.stob_id = m.new_id;

UPDATE stock_opname_bahan_lines l
JOIN stob_id_map m ON m.old_id + @offset = l.stob_id
SET l.stob_id = m.new_id;

-- ---------------------------------------------------------------------------
-- 4. Reset AUTO_INCREMENT so the next insert continues right after the last
--    (now dense) id instead of resuming from the old high-water mark.
-- ---------------------------------------------------------------------------
SET @next_ai := (SELECT MAX(stob_id) + 1 FROM stock_opname_bahans);
SET @sql := CONCAT('ALTER TABLE stock_opname_bahans AUTO_INCREMENT = ', @next_ai);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. Verify before committing:
--    - stock_opname_bahans.stob_id should now be dense from @start_from upward
--    - every stock_opname_detail_bahans.stob_id / stock_opname_bahan_lines.stob_id
--      must still resolve to an existing stock_opname_bahans row (no orphans)
-- ---------------------------------------------------------------------------
SELECT stob_id, stob_code FROM stock_opname_bahans ORDER BY stob_id;

SELECT COUNT(*) AS orphan_detail_bahans
FROM stock_opname_detail_bahans d
LEFT JOIN stock_opname_bahans so ON so.stob_id = d.stob_id
WHERE so.stob_id IS NULL;

SELECT COUNT(*) AS orphan_bahan_lines
FROM stock_opname_bahan_lines l
LEFT JOIN stock_opname_bahans so ON so.stob_id = l.stob_id
WHERE so.stob_id IS NULL;

-- If everything above looks right:
-- COMMIT;
-- Otherwise:
-- ROLLBACK;
