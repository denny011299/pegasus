-- Peringatan stok per gudang (product_stocks.ps_alert_stock)
-- Threshold alert mengikuti gudang aktif (mirip safety stock).

ALTER TABLE `product_stocks`
  ADD COLUMN `ps_alert_stock` INT NOT NULL DEFAULT 0 AFTER `ps_safety_stock`;

-- Backfill dari product_variants.product_variant_alert (unit default variant)
UPDATE product_stocks ps
INNER JOIN product_variants pv
    ON pv.product_variant_id = ps.product_variant_id
   AND pv.unit_id = ps.unit_id
   AND pv.status = 1
SET ps.ps_alert_stock = COALESCE(pv.product_variant_alert, 0)
WHERE COALESCE(ps.ps_alert_stock, 0) = 0
  AND COALESCE(pv.product_variant_alert, 0) > 0
  AND ps.status = 1;
