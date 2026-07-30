-- Gudang eceran per baris produk pengiriman.
-- Jalankan hanya jika kolom warehouse_id belum ada.
ALTER TABLE `sales_order_details`
    ADD COLUMN `warehouse_id` BIGINT UNSIGNED NULL AFTER `unit_id`,
    ADD INDEX `sales_order_details_warehouse_id_index` (`warehouse_id`);

-- Backfill pengiriman lama: gudang header dipindahkan ke item satuan eceran.
UPDATE `sales_order_details` sod
INNER JOIN `sales_orders` so ON so.so_id = sod.so_id
INNER JOIN `product_variants` pv ON pv.product_variant_id = sod.product_variant_id
SET sod.warehouse_id = so.retail_warehouse_id
WHERE sod.warehouse_id IS NULL
  AND so.retail_warehouse_id IS NOT NULL
  AND pv.retail_unit > 0
  AND sod.unit_id = pv.retail_unit;
