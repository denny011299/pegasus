-- =============================================================================
-- PEGASUS - external_api_endpoints (status per endpoint External API)
-- Alternatif: php artisan migrate
--   (migration: 2026_08_17_090000_create_external_api_endpoints_table)
--
-- Satu baris per endpoint terdokumentasi (App\ExternalApi\Docs\ApiEndpointDoc),
-- lintas semua versi API sekaligus — `endpoint_key` diisi ApiEndpointDoc::key().
-- Baris hanya ada untuk endpoint yang salah satu saklarnya pernah diubah dari
-- nilai bawaan lewat halaman Status API Eksternal; endpoint tanpa baris di
-- sini dianggap is_active=1, is_public_docs_show=0 (lihat
-- App\ExternalApi\Support\ApiEndpointSettings).
--
-- Menggantikan penyimpanan awal lewat tabel `settings` generik
-- (setting_key `external_api_endpoint_active_{key}` /
-- `external_api_endpoint_public_docs_{key}`) — kalau lingkungan ini sempat
-- memakai bentuk lama itu, baris yang sudah diatur admin dipindah ke sini
-- lalu dihapus dari `settings` supaya konfigurasi yang ada tidak hilang.
-- Idempotent, aman dijalankan berulang.
-- =============================================================================

SET @db := DATABASE();

CREATE TABLE IF NOT EXISTS `external_api_endpoints` (
  `external_api_endpoint_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `endpoint_key` VARCHAR(150) NOT NULL COMMENT 'ApiEndpointDoc::key() — identitas unik endpoint lintas semua versi API',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Endpoint bisa dipanggil — dicek di AuthenticateExternalApi sebelum autentikasi API Key',
  `is_public_docs_show` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Endpoint muncul di halaman dokumentasi publik /api-docs',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_endpoint_id`),
  UNIQUE KEY `external_api_endpoints_key_unique` (`endpoint_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Pindahkan baris lama dari `settings` (bentuk sebelum tabel ini ada), kalau
-- lingkungan ini sempat memakainya. Tidak berpengaruh apa-apa kalau tidak ada
-- baris yang cocok (mis. server yang langsung dapat tabel ini sejak awal).
-- -----------------------------------------------------------------------------
INSERT INTO `external_api_endpoints` (`endpoint_key`, `is_active`, `is_public_docs_show`, `created_at`, `updated_at`)
SELECT
  SUBSTRING(a.`setting_key`, LENGTH('external_api_endpoint_active_') + 1) AS endpoint_key,
  CAST(a.`setting_value` AS UNSIGNED) AS is_active,
  IFNULL(CAST(p.`setting_value` AS UNSIGNED), 0) AS is_public_docs_show,
  NOW(), NOW()
FROM `settings` a
LEFT JOIN `settings` p
  ON p.`setting_key` = CONCAT('external_api_endpoint_public_docs_', SUBSTRING(a.`setting_key`, LENGTH('external_api_endpoint_active_') + 1))
WHERE a.`setting_key` LIKE 'external_api_endpoint_active_%'
ON DUPLICATE KEY UPDATE
  `is_active` = VALUES(`is_active`),
  `is_public_docs_show` = VALUES(`is_public_docs_show`),
  `updated_at` = VALUES(`updated_at`);

-- public_docs saklar yang pernah diubah TAPI active-nya tidak pernah disentuh
-- (masih default) tidak tercakup oleh JOIN di atas (tidak ada baris `active`
-- pasangannya) — tangani sisanya di sini.
INSERT INTO `external_api_endpoints` (`endpoint_key`, `is_active`, `is_public_docs_show`, `created_at`, `updated_at`)
SELECT
  SUBSTRING(p.`setting_key`, LENGTH('external_api_endpoint_public_docs_') + 1) AS endpoint_key,
  1 AS is_active,
  CAST(p.`setting_value` AS UNSIGNED) AS is_public_docs_show,
  NOW(), NOW()
FROM `settings` p
WHERE p.`setting_key` LIKE 'external_api_endpoint_public_docs_%'
ON DUPLICATE KEY UPDATE
  `is_public_docs_show` = VALUES(`is_public_docs_show`),
  `updated_at` = VALUES(`updated_at`);

DELETE FROM `settings`
WHERE `setting_key` LIKE 'external_api_endpoint_active_%'
   OR `setting_key` LIKE 'external_api_endpoint_public_docs_%';

-- -----------------------------------------------------------------------------
-- Catat di migrations (supaya artisan migrate tidak bentrok)
-- -----------------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_17_090000_create_external_api_endpoints_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_17_090000_create_external_api_endpoints_table');

SELECT COUNT(*) AS external_api_endpoints_rows FROM `external_api_endpoints`;
