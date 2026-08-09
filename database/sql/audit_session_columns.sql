-- Kolom audit berbasis staff_id dari sesi login (referensi: struktur okeh8644_spegasus (3).sql).
-- Jalankan di MySQL 8+. Jika muncul error "Duplicate column", lewati baris tersebut — kolom sudah ada di DB Anda.
-- Semua kolom bertipe INT mengacu ke staff_id (tabel staffs).

-- Produksi: penyetuju/tolak & pengaju pembatalan
ALTER TABLE `productions`
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `production_created_by`,
  ADD COLUMN `cancel_requested_by` int(11) DEFAULT NULL COMMENT 'staff_id pengaju batal' AFTER `notes`;

-- Master variasi
ALTER TABLE `variants`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `variant_attribute`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Bank & kategori kas
ALTER TABLE `banks`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`;

ALTER TABLE `cash_categories`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`;

-- Kas besar (header)
ALTER TABLE `cashes`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Kas operasional (per jalur)
ALTER TABLE `cash_admins`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

ALTER TABLE `cash_gudangs`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

ALTER TABLE `cash_armadas`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

ALTER TABLE `cash_sales`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Produk bermasalah
ALTER TABLE `product_issues`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Stok opname produk & bahan
ALTER TABLE `stock_opnames`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

ALTER TABLE `stock_opname_bahans`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Pengiriman / sales order (penyetuju)
ALTER TABLE `sales_orders`
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `so_cashier`;

-- Pembelian
ALTER TABLE `purchase_orders`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;

-- Kas kecil (sesuai dump: kolom transaksi mungkin sudah ditambah manual — hanya audit)
ALTER TABLE `petty_cashes`
  ADD COLUMN `created_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `status`,
  ADD COLUMN `acc_by` int(11) DEFAULT NULL COMMENT 'staff_id' AFTER `created_by`;
