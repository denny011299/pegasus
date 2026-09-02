-- Audit: stock stuck under-rolled from before GitHub #87 (unit roll-up fix, PR #88)
-- ============================================================================
-- READ-ONLY. Every statement below is a SELECT. Nothing is written, nothing is locked beyond a
-- normal read. Safe to run directly against production in any SQL client (phpMyAdmin, Adminer,
-- TablePlus, `mysql` CLI, ...) -- no artisan/PHP access required.
--
-- Why this exists: GitHub #87 fixed the unit roll-up logic (App\Support\UnitRollUp) going forward,
-- but the fix is pure code -- it never touches a stock row on its own. It only changes what happens
-- the NEXT TIME a stock-in transaction (production ACC, PO receipt, product-issue restore, Sales
-- Order revert) credits that specific row, or the next time a Stock Opname recomputes it. Any
-- product/bahan that was ALREADY stuck over-ratio before the fix, and hasn't been touched by one of
-- those since, stays wrong indefinitely and silently. This finds every one of those rows right now.
--
-- What "stuck" means, concretely: e.g. 1 DOS = 24 Piece, but a product's Piece-level row shows 24
-- (or more) while its DOS-level row never absorbed it -- the exact SKU HKCP60P bug that prompted
-- #87 (11 DOS + 20 Piece, +4 Piece production should have become 12 DOS + 0 Piece, stayed
-- 11 DOS + 24 Piece).
--
-- ⚠️ WAREHOUSE-SCOPED (fase2/main only -- differs from `main`'s copy of this file).
-- `main` has no warehouse concept for stock, so its version groups purely by
-- product_variant_id/supplies_id. On this branch product_stocks/supplies_stocks DO carry
-- warehouse_id, and a roll-up is always per-warehouse: stock in warehouse A can never roll into a
-- unit row belonging to warehouse B. Every CTE below therefore partitions/joins on
-- (item, warehouse_id), not item alone. Without that, a variant holding one perfectly healthy
-- single-unit row in each of two warehouses would be pooled together and reported as "stuck" --
-- a pure false positive. See app/Console/Commands/AuditStuckUnitRollUpCommand.php, which applies
-- the same scoping (and its regression test, which pins exactly this case).
--
-- How it decides "should_be": mirrors App\Support\UnitRollUp::collapse() -- the SAME primitive
-- Stock Opname already uses to decide a product's canonical unit breakdown -- not a separate,
-- independently-invented rule. Cross-validated against UnitRollUp::collapseProduct()/
-- collapseSupplies() on several constructed scenarios (a simple 2-unit combine, a full 3-level
-- cascade, and the trickiest case: an entered top-level unit that must stay UNTOUCHED because an
-- intermediate unit has no active stock row at all -- rolling up can only ever move stock into a
-- unit that already has its own row, same policy as every other roll-up call site); all matched
-- exactly. See PR #89 (main) / the fase2 Batch 12 PR for the full history.
--
-- Reading the output: `current_qty` is what's stored right now; `should_be_qty` is what
-- UnitRollUp's own logic says it should be. A row only appears here if those two differ -- i.e.
-- every row returned is currently wrong. Nothing is flagged for an (item, warehouse) with only one
-- active stock row (there's nothing to roll into) or where an intermediate unit has no active row
-- in that same warehouse (rolling up cannot reach past that gap, by the same design as every other
-- roll-up call site).
--
-- This is a diagnostic only. It deliberately does NOT fix anything -- decide what to do with the
-- results (an intentional --fix mode in a future artisan command, a manual correction, or leaving
-- it for the next transaction/opname to self-heal) as a separate step.
-- ============================================================================


-- ============================================================================
-- PART 1: PRODUCTS (product_stocks / product_relations)
-- ============================================================================
WITH RECURSIVE
chain AS (
    SELECT product_variant_id, pr_unit_id_2 AS small_unit, pr_unit_id_1 AS big_unit, pr_unit_value_2 AS ratio
    FROM product_relations WHERE status = 1
),
entered AS (
    -- warehouse_id carried through everywhere below: a roll-up is per-warehouse.
    SELECT product_variant_id, warehouse_id, unit_id, ps_stock AS qty
    FROM product_stocks WHERE status = 1
),
multipliers AS (
    -- Base case: units that are a "small" unit somewhere in their product's ladder but NEVER a
    -- "big" one -- the true bottom of that product's own ladder, multiplier 1. (The ladder itself
    -- is warehouse-independent -- product_relations has no warehouse_id -- so this CTE doesn't
    -- need the scope; it gets joined onto warehouse-scoped rows below.)
    SELECT DISTINCT c.product_variant_id, c.small_unit AS unit_id, 1 AS multiplier
    FROM chain c
    WHERE NOT EXISTS (
        SELECT 1 FROM chain c2
        WHERE c2.product_variant_id = c.product_variant_id AND c2.big_unit = c.small_unit
    )
    UNION ALL
    -- Recursive case: walk upward, each level's multiplier is the level below's times this hop's ratio.
    SELECT m.product_variant_id, c.big_unit, m.multiplier * c.ratio
    FROM multipliers m
    JOIN chain c ON c.product_variant_id = m.product_variant_id AND c.small_unit = m.unit_id
),
entered_with_mult AS (
    SELECT e.product_variant_id, e.warehouse_id, e.unit_id, e.qty, mu.multiplier
    FROM entered e
    JOIN multipliers mu ON mu.product_variant_id = e.product_variant_id AND mu.unit_id = e.unit_id
),
start_point AS (
    -- The smallest unit that ACTUALLY has an active stock row IN THIS WAREHOUSE -- collapse()'s
    -- starting point is never the ladder's absolute bottom, it's the smallest unit really touched
    -- (GitHub #78 rule: never fabricate a value for a unit smaller than the smallest one actually
    -- filled).
    SELECT product_variant_id, warehouse_id, start_unit, start_qty FROM (
        SELECT ewm.product_variant_id, ewm.warehouse_id, ewm.unit_id AS start_unit, ewm.qty AS start_qty,
               ROW_NUMBER() OVER (PARTITION BY ewm.product_variant_id, ewm.warehouse_id ORDER BY ewm.multiplier ASC, ewm.unit_id ASC) AS rn
        FROM entered_with_mult ewm
    ) t WHERE rn = 1
),
walk AS (
    SELECT sp.product_variant_id, sp.warehouse_id, sp.start_unit AS unit_id, sp.start_qty AS carry, 0 AS depth
    FROM start_point sp

    UNION ALL

    -- Can only continue to the next unit up if IT has an active row in the SAME WAREHOUSE too
    -- (INNER JOIN to `entered`, matched on warehouse_id as well) -- this is the exact condition
    -- that stops the walk dead at a gap, leaving anything above it (even if entered) completely
    -- untouched, matching UnitRollUp::plan()/collapse()'s `isset($allowed[...])` gate precisely
    -- (and that gate is itself warehouse-scoped, via allowedProductUnitIds($id, $warehouseId)).
    SELECT w.product_variant_id, w.warehouse_id, c.big_unit AS unit_id,
           FLOOR(w.carry / c.ratio) + big_stock.qty AS carry,
           w.depth + 1
    FROM walk w
    JOIN chain c ON c.product_variant_id = w.product_variant_id AND c.small_unit = w.unit_id
    JOIN entered big_stock ON big_stock.product_variant_id = w.product_variant_id
                          AND big_stock.warehouse_id = w.warehouse_id
                          AND big_stock.unit_id = c.big_unit
    WHERE c.ratio > 0 AND w.carry >= c.ratio AND w.depth < 20 -- 20-hop guard, same bound as the PHP walkers
),
walk_with_next AS (
    SELECT w.*,
           ch.ratio AS ratio_up,
           EXISTS (
               SELECT 1 FROM walk w2
               WHERE w2.product_variant_id = w.product_variant_id
                 AND w2.warehouse_id = w.warehouse_id
                 AND w2.depth = w.depth + 1
           ) AS has_next
    FROM walk w
    LEFT JOIN chain ch ON ch.product_variant_id = w.product_variant_id AND ch.small_unit = w.unit_id
),
result AS (
    -- If the walk continued past this level, the remainder stays; if this was the last level it
    -- reached, everything carried into it stays here in full.
    SELECT product_variant_id, warehouse_id, unit_id,
           CASE WHEN has_next THEN carry % ratio_up ELSE carry END AS canonical_qty
    FROM walk_with_next
)
SELECT
    e.product_variant_id,
    e.warehouse_id,
    w.warehouse_name,
    pv.product_variant_sku AS sku,
    TRIM(CONCAT(COALESCE(p.product_name, ''), ' ', COALESCE(pv.product_variant_name, ''))) AS product_name,
    u.unit_name,
    e.unit_id,
    e.qty AS current_qty,
    r.canonical_qty AS should_be_qty
FROM entered e
JOIN result r ON r.product_variant_id = e.product_variant_id
             AND r.warehouse_id = e.warehouse_id
             AND r.unit_id = e.unit_id
LEFT JOIN product_variants pv ON pv.product_variant_id = e.product_variant_id
LEFT JOIN products p ON p.product_id = pv.product_id
LEFT JOIN units u ON u.unit_id = e.unit_id
LEFT JOIN warehouses w ON w.id = e.warehouse_id
WHERE e.qty <> r.canonical_qty
ORDER BY e.warehouse_id, e.product_variant_id, e.unit_id;


-- ============================================================================
-- PART 2: BAHAN / SUPPLIES (supplies_stocks / supplies_relations)
-- Identical logic to Part 1, mirrored onto the supplies-side tables.
-- ============================================================================
WITH RECURSIVE
chain AS (
    SELECT supplies_id, su_id_2 AS small_unit, su_id_1 AS big_unit, sr_value_2 AS ratio
    FROM supplies_relations WHERE status = 1
),
entered AS (
    SELECT supplies_id, warehouse_id, unit_id, ss_stock AS qty
    FROM supplies_stocks WHERE status = 1
),
multipliers AS (
    SELECT DISTINCT c.supplies_id, c.small_unit AS unit_id, 1 AS multiplier
    FROM chain c
    WHERE NOT EXISTS (
        SELECT 1 FROM chain c2
        WHERE c2.supplies_id = c.supplies_id AND c2.big_unit = c.small_unit
    )
    UNION ALL
    SELECT m.supplies_id, c.big_unit, m.multiplier * c.ratio
    FROM multipliers m
    JOIN chain c ON c.supplies_id = m.supplies_id AND c.small_unit = m.unit_id
),
entered_with_mult AS (
    SELECT e.supplies_id, e.warehouse_id, e.unit_id, e.qty, mu.multiplier
    FROM entered e
    JOIN multipliers mu ON mu.supplies_id = e.supplies_id AND mu.unit_id = e.unit_id
),
start_point AS (
    SELECT supplies_id, warehouse_id, start_unit, start_qty FROM (
        SELECT ewm.supplies_id, ewm.warehouse_id, ewm.unit_id AS start_unit, ewm.qty AS start_qty,
               ROW_NUMBER() OVER (PARTITION BY ewm.supplies_id, ewm.warehouse_id ORDER BY ewm.multiplier ASC, ewm.unit_id ASC) AS rn
        FROM entered_with_mult ewm
    ) t WHERE rn = 1
),
walk AS (
    SELECT sp.supplies_id, sp.warehouse_id, sp.start_unit AS unit_id, sp.start_qty AS carry, 0 AS depth
    FROM start_point sp

    UNION ALL

    SELECT w.supplies_id, w.warehouse_id, c.big_unit AS unit_id,
           FLOOR(w.carry / c.ratio) + big_stock.qty AS carry,
           w.depth + 1
    FROM walk w
    JOIN chain c ON c.supplies_id = w.supplies_id AND c.small_unit = w.unit_id
    JOIN entered big_stock ON big_stock.supplies_id = w.supplies_id
                          AND big_stock.warehouse_id = w.warehouse_id
                          AND big_stock.unit_id = c.big_unit
    WHERE c.ratio > 0 AND w.carry >= c.ratio AND w.depth < 20
),
walk_with_next AS (
    SELECT w.*,
           ch.ratio AS ratio_up,
           EXISTS (
               SELECT 1 FROM walk w2
               WHERE w2.supplies_id = w.supplies_id
                 AND w2.warehouse_id = w.warehouse_id
                 AND w2.depth = w.depth + 1
           ) AS has_next
    FROM walk w
    LEFT JOIN chain ch ON ch.supplies_id = w.supplies_id AND ch.small_unit = w.unit_id
),
result AS (
    SELECT supplies_id, warehouse_id, unit_id,
           CASE WHEN has_next THEN carry % ratio_up ELSE carry END AS canonical_qty
    FROM walk_with_next
)
SELECT
    e.supplies_id,
    e.warehouse_id,
    w.warehouse_name,
    s.supplies_name,
    u.unit_name,
    e.unit_id,
    e.qty AS current_qty,
    r.canonical_qty AS should_be_qty
FROM entered e
JOIN result r ON r.supplies_id = e.supplies_id
             AND r.warehouse_id = e.warehouse_id
             AND r.unit_id = e.unit_id
LEFT JOIN supplies s ON s.supplies_id = e.supplies_id
LEFT JOIN units u ON u.unit_id = e.unit_id
LEFT JOIN warehouses w ON w.id = e.warehouse_id
WHERE e.qty <> r.canonical_qty
ORDER BY e.warehouse_id, e.supplies_id, e.unit_id;
