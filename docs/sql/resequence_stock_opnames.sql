-- ============================================================================
-- Resequence stock_opnames.sto_id / sto_code to close gaps left by deleted rows,
-- cascading the id change into every table that stores sto_id as a loose
-- reference (stock_opname_details, stock_opname_lines).
--
-- NOTE: this repo has 0 real foreign keys (all references are "loose"/app-level,
-- see migration comments in stock_opname_lines). So this script IS the cascade —
-- there is no DB-level ON UPDATE CASCADE doing it for you.
--
-- BEFORE RUNNING:
--   1. Take a full backup / run inside a transaction and verify before COMMIT.
--   2. Double check no other table you've added since references sto_id
--      (grep -rn "sto_id" app/Models  ->  as of 2026-09-02: StockOpname,
--      StockOpnameDetail, StockOpnameLine only).
--   3. Run during a maintenance window — app must not write to these tables
--      while this runs.
-- ============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- 0. Filter: only rows with sto_id >= @start_from are remapped. Everything
--    below this stays completely untouched (id and code unchanged). The
--    remapped rows are renumbered starting AT @start_from, so they stay
--    contiguous with the untouched rows below it (no gap, no overlap) —
--    e.g. @start_from := 50 turns [50, 52, 53, 60, ...] into [50, 51, 52, 53, ...].
-- ---------------------------------------------------------------------------
SET @start_from := 50;

-- ---------------------------------------------------------------------------
-- 1. Build the old_id -> new_id / new_code mapping, ordered by current sto_id
--    (i.e. current chronological/creation order) so relative order is kept.
--    Only rows >= @start_from are included -- rows below it are left alone.
-- ---------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS sto_id_map;
CREATE TEMPORARY TABLE sto_id_map (
  old_id   INT UNSIGNED PRIMARY KEY,
  new_id   INT UNSIGNED NOT NULL,
  new_code VARCHAR(6)   NOT NULL
);

SET @rownum := @start_from - 1;

INSERT INTO sto_id_map (old_id, new_id, new_code)
SELECT
  sto_id,
  (@rownum := @rownum + 1) AS new_id,
  CONCAT(
    prefix,
    LPAD(@rownum, LENGTH(sto_code) - LENGTH(prefix), '0')
  ) AS new_code
FROM (
  SELECT sto_id, sto_code, REGEXP_REPLACE(sto_code, '[0-9]+$', '') AS prefix
  FROM stock_opnames
  WHERE sto_id >= @start_from
  ORDER BY sto_id ASC
) t;

-- Sanity check: mapping should cover every row and new_id must be dense from @start_from
SELECT COUNT(*) AS mapped_rows, MAX(new_id) AS max_new_id FROM sto_id_map;

-- ---------------------------------------------------------------------------
-- 2. Phase A: move every row to a temporary, guaranteed-non-colliding id
--    range (old_id + large offset) so phase B's UPDATE never hits a
--    duplicate-key error against a not-yet-moved row.
-- ---------------------------------------------------------------------------
SET @offset := 1000000;

UPDATE stock_opnames so
JOIN sto_id_map m ON m.old_id = so.sto_id
SET so.sto_id = m.old_id + @offset;

UPDATE stock_opname_details d
JOIN sto_id_map m ON m.old_id = d.sto_id
SET d.sto_id = m.old_id + @offset;

UPDATE stock_opname_lines l
JOIN sto_id_map m ON m.old_id = l.sto_id
SET l.sto_id = m.old_id + @offset;

-- ---------------------------------------------------------------------------
-- 3. Phase B: move every row from the offset id to its final, dense id and
--    write the resequenced code at the same time.
-- ---------------------------------------------------------------------------
UPDATE stock_opnames so
JOIN sto_id_map m ON m.old_id + @offset = so.sto_id
SET so.sto_id = m.new_id,
    so.sto_code = m.new_code;

UPDATE stock_opname_details d
JOIN sto_id_map m ON m.old_id + @offset = d.sto_id
SET d.sto_id = m.new_id;

UPDATE stock_opname_lines l
JOIN sto_id_map m ON m.old_id + @offset = l.sto_id
SET l.sto_id = m.new_id;

-- ---------------------------------------------------------------------------
-- 4. Reset AUTO_INCREMENT so the next insert continues right after the last
--    (now dense) id instead of resuming from the old high-water mark.
-- ---------------------------------------------------------------------------
SET @next_ai := (SELECT MAX(sto_id) + 1 FROM stock_opnames);
SET @sql := CONCAT('ALTER TABLE stock_opnames AUTO_INCREMENT = ', @next_ai);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ---------------------------------------------------------------------------
-- 5. Verify before committing:
--    - stock_opnames.sto_id should now be dense from @start_from upward
--    - every stock_opname_details.sto_id / stock_opname_lines.sto_id must
--      still resolve to an existing stock_opnames row (no orphans created)
-- ---------------------------------------------------------------------------
SELECT sto_id, sto_code FROM stock_opnames ORDER BY sto_id;

SELECT COUNT(*) AS orphan_details
FROM stock_opname_details d
LEFT JOIN stock_opnames so ON so.sto_id = d.sto_id
WHERE so.sto_id IS NULL;

SELECT COUNT(*) AS orphan_lines
FROM stock_opname_lines l
LEFT JOIN stock_opnames so ON so.sto_id = l.sto_id
WHERE so.sto_id IS NULL;

-- If everything above looks right:
-- COMMIT;
-- Otherwise:
-- ROLLBACK;
