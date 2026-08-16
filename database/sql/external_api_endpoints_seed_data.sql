-- =============================================================================
-- PEGASUS - external_api_endpoints (data): status Status API Eksternal saat ini
-- Alternatif: php artisan db:seed (snapshot database/seeders/data/external_api_endpoints.json)
--
-- Jalankan SETELAH create_external_api_endpoints_table.sql. Nilai di sini adalah
-- konfigurasi aktif/dokumentasi-publik yang sudah diatur admin per endpoint —
-- 33 baris, dibangkitkan langsung dari snapshot seeder di atas, bukan ditulis
-- tangan. Idempotent (ON DUPLICATE KEY UPDATE), aman dijalankan berulang; bisa
-- dipakai untuk menyamakan lingkungan lain dengan konfigurasi ini apa adanya.
-- =============================================================================

INSERT INTO `external_api_endpoints` (`endpoint_key`, `is_active`, `is_public_docs_show`, `created_at`, `updated_at`)
VALUES
  ('armada', 0, 0, NOW(), NOW()),
  ('armada-create', 1, 1, NOW(), NOW()),
  ('armada-delete', 1, 1, NOW(), NOW()),
  ('armada-update', 1, 1, NOW(), NOW()),
  ('master-cash-categories', 0, 0, NOW(), NOW()),
  ('master-sales', 0, 0, NOW(), NOW()),
  ('master-sales-connect', 0, 0, NOW(), NOW()),
  ('master-sales-create', 1, 1, NOW(), NOW()),
  ('master-sales-delete', 1, 1, NOW(), NOW()),
  ('master-sales-update', 1, 1, NOW(), NOW()),
  ('master-units', 0, 0, NOW(), NOW()),
  ('master-units-connect', 0, 0, NOW(), NOW()),
  ('master-units-create', 1, 1, NOW(), NOW()),
  ('master-units-delete', 1, 1, NOW(), NOW()),
  ('master-units-update', 1, 1, NOW(), NOW()),
  ('master-warehouse-types', 0, 0, NOW(), NOW()),
  ('master-warehouses', 0, 0, NOW(), NOW()),
  ('master-warehouses-create', 1, 1, NOW(), NOW()),
  ('master-warehouses-delete', 1, 1, NOW(), NOW()),
  ('master-warehouses-update', 1, 1, NOW(), NOW()),
  ('pembayaran-kas-buat', 1, 1, NOW(), NOW()),
  ('pembayaran-kas-detail', 0, 0, NOW(), NOW()),
  ('produk', 0, 0, NOW(), NOW()),
  ('produk-connect', 0, 0, NOW(), NOW()),
  ('produk-create', 1, 1, NOW(), NOW()),
  ('produk-delete', 1, 1, NOW(), NOW()),
  ('produk-update', 1, 1, NOW(), NOW()),
  ('shipment-cancel', 1, 1, NOW(), NOW()),
  ('shipment-change-status', 1, 1, NOW(), NOW()),
  ('shipment-scheduled', 1, 1, NOW(), NOW()),
  ('shipment-shipped', 1, 1, NOW(), NOW()),
  ('shipment-show', 0, 0, NOW(), NOW()),
  ('stok-cek', 1, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  `is_active` = VALUES(`is_active`),
  `is_public_docs_show` = VALUES(`is_public_docs_show`),
  `updated_at` = VALUES(`updated_at`);

SELECT COUNT(*) AS external_api_endpoints_rows FROM `external_api_endpoints`;
