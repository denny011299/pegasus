-- Migration: 2026_07_27_220000_remap_stock_transfer_statuses
-- Copied from stock_transfer_status_remap.sql (undated kept).

-- Stock Transfer status remap (3-step flow)
-- Lama: 1=pending (stok sudah dipotong), 2=diterima, 3=ditolak
-- Baru: 1=pending (belum potong), 2=kirim (sudah potong), 3=ditolak, 4=diterima
--
-- Pending lama (stok sudah keluar) → Kirim
-- Diterima lama → Diterima (4)


SET @db := DATABASE();

UPDATE stock_transfers
SET status = 4
WHERE status = 2;

UPDATE stock_transfers
SET status = 2
WHERE status = 1;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_27_220000_remap_stock_transfer_statuses',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_27_220000_remap_stock_transfer_statuses');

SELECT '2026_07_27_220000_remap_stock_transfer_statuses OK' AS result;
