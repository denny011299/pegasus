-- product_variants.retail_unit
-- Satuan eceran default per variant (unit_id). Nullable.

ALTER TABLE `product_variants`
  ADD COLUMN `retail_unit` int NULL AFTER `product_variant_stock`;
