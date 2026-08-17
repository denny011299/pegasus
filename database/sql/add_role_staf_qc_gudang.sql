-- Role "Staf QC & Gudang" (role_id = 7) untuk dropdown Staff QC di pengembalian.
-- Aman diulang. Tidak menimpa role_id 7 jika sudah terpakai nama lain.

INSERT INTO `roles` (`role_id`, `role_name`, `role_access`, `status`, `created_at`, `updated_at`)
SELECT
  7,
  'Staf QC & Gudang',
  '[{"name":"Daftar Produk","akses":["create","view","others"]},{"name":"Daftar Bahan Mentah","akses":["create","view","others"]},{"name":"Armada","akses":["view"]},{"name":"Pemasok","akses":["view"]},{"name":"Produk Bermasalah","akses":["create","view"]},{"name":"Peringatan Stok Produk","akses":["create","edit","delete","view","others"]},{"name":"Peringatan Stok Bahan Mentah","akses":["create","edit","delete","view","others"]},{"name":"Stok Opname Produk","akses":["create","view"]},{"name":"Stok Opname Bahan Mentah","akses":["create","view"]},{"name":"Pengiriman","akses":["create","edit","view"]},{"name":"Pembelian","akses":["create","edit","view"]},{"name":"Resep Bahan Mentah","akses":["view"]},{"name":"Produksi","akses":["create","edit","view"]},{"name":"Pengelolaan Bahan Mentah","akses":["create","edit","delete","view","others"]},{"name":"Retur Produk","akses":["create","edit","delete","view","others"]},{"name":"Laporan Produksi","akses":["create","edit","delete","view","others"]},{"name":"Barang Masuk Keluar","akses":["create","edit","delete","view","others"]},{"name":"Laporan Stock Aging","akses":["create","edit","delete","view","others"]},{"name":"Gudang","akses":["view"]},{"name":"Stock Transfer","akses":["view"]},{"name":"Dashboard Widgets","akses":["approval_logs","stock_alert_bahan","overstock_rekomendasi"]}]',
  1,
  NOW(),
  NOW()
WHERE NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_id` = 7)
  AND NOT EXISTS (SELECT 1 FROM `roles` WHERE `role_name` = 'Staf QC & Gudang');

UPDATE `roles`
SET `status` = 1, `updated_at` = NOW()
WHERE `role_name` = 'Staf QC & Gudang'
  AND (`status` IS NULL OR `status` <> 1);
