-- sales_orders.retail_warehouse_id
-- Gudang eceran untuk item Pengiriman yang pakai satuan eceran.

ALTER TABLE `sales_orders`
  ADD COLUMN `retail_warehouse_id` bigint unsigned NULL AFTER `so_customer`,
  ADD INDEX `sales_orders_retail_warehouse_id_index` (`retail_warehouse_id`);
