-- Stock Transfer status remap (3-step flow)
-- Lama: 1=pending (stok sudah dipotong), 2=diterima, 3=ditolak
-- Baru: 1=pending (belum potong), 2=kirim (sudah potong), 3=ditolak, 4=diterima
--
-- Pending lama (stok sudah keluar) → Kirim
-- Diterima lama → Diterima (4)

UPDATE stock_transfers
SET status = 4
WHERE status = 2;

UPDATE stock_transfers
SET status = 2
WHERE status = 1;
