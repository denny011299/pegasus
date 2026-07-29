-- log_stocks.warehouse_id (manual jika migrate belum dijalankan)
-- Backfill Stock Transfer dari stock_transfers

ALTER TABLE `log_stocks`
  ADD COLUMN `warehouse_id` bigint unsigned NULL AFTER `staff_id`,
  ADD INDEX `log_stocks_warehouse_id_index` (`warehouse_id`);

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
