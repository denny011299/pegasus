-- =============================================================================
-- 07_remap_operational_fks.sql
-- Remap unit_id di semua dokumen operasional ke satuan PMO.
-- Tabel diubah (kolom):
--   log_stocks.unit_id
--   sales_order_details.unit_id
--   sales_delivery_orders_details.unit_id
--   stock_transfer_details.unit_id, received_unit_id
--   purchase_orders_details.unit_id
--   production_details.unit_id
--   product_issues_details.unit_id
--   customer_product_return_details.unit_id
--   customer_supply_return_details.unit_id
--   return_supplies_detail.unit_id
--   stock_opname_lines.unit_id
--   stock_opname_bahan_lines.unit_id
--   product_variants.unit_id, retail_unit, safety_unit_id
--   bom_details.unit_id
-- =============================================================================

UPDATE log_stocks t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE sales_order_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE sales_delivery_orders_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_transfer_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_transfer_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.received_unit_id
SET t.received_unit_id = m.to_unit_id;

UPDATE purchase_orders_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE production_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_issues_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE customer_product_return_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE customer_supply_return_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE return_supplies_detail t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_opname_lines t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE stock_opname_bahan_lines t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.retail_unit
SET t.retail_unit = m.to_unit_id;

UPDATE product_variants t
JOIN tmp_unit_remap m ON m.from_unit_id = t.safety_unit_id
SET t.safety_unit_id = m.to_unit_id;

UPDATE bom_details t
JOIN tmp_unit_remap m ON m.from_unit_id = t.unit_id
SET t.unit_id = m.to_unit_id;

-- Lanjut 08_deactivate_legacy_units.sql
