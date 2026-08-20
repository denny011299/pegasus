-- =============================================================================
-- PEGASUS - platform External API (aplikasi, API key, log request)
-- Alternatif: php artisan migrate --path=...
--   2026_07_28_220000_create_external_applications_table
--   2026_07_28_220100_create_external_api_keys_table
--   2026_07_28_220200_create_external_api_request_logs_table
--
-- Idempotent, aman diulang. Tidak ada seed aplikasi — daftar kosong sampai
-- admin menambah di halaman Aplikasi API Eksternal.
-- =============================================================================

SET @db := DATABASE();

CREATE TABLE IF NOT EXISTS `external_applications` (
  `external_application_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_code` VARCHAR(100) NOT NULL COMMENT 'Identitas stabil, tidak ikut berubah saat nama diganti',
  `application_name` VARCHAR(150) NOT NULL,
  `company` VARCHAR(150) NULL DEFAULT NULL,
  `contact_name` VARCHAR(150) NULL DEFAULT NULL,
  `contact_email` VARCHAR(150) NULL DEFAULT NULL,
  `description` TEXT NULL,
  `application_status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, disabled',
  `created_by` INT NULL DEFAULT NULL,
  `updated_by` INT NULL DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = dead',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_application_id`),
  UNIQUE KEY `external_applications_code_unique` (`application_code`),
  KEY `external_applications_status_idx` (`status`, `application_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_api_keys` (
  `external_api_key_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_application_id` BIGINT UNSIGNED NOT NULL,
  `key_name` VARCHAR(150) NOT NULL,
  `environment` VARCHAR(20) NOT NULL DEFAULT 'production' COMMENT 'production, staging, development',
  `key_prefix` VARCHAR(64) NOT NULL COMMENT 'Bagian awal kunci, dipakai untuk pencarian saat autentikasi',
  `key_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 dari kunci utuh',
  `key_last_four` VARCHAR(8) NOT NULL,
  `key_status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'active, revoked',
  `expires_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'NULL = tidak pernah kedaluwarsa',
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `revoked_at` TIMESTAMP NULL DEFAULT NULL,
  `revoked_by` INT NULL DEFAULT NULL,
  `created_by` INT NULL DEFAULT NULL,
  `status` INT NOT NULL DEFAULT 1 COMMENT '1 = active, 0 = dead',
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_key_id`),
  UNIQUE KEY `external_api_keys_prefix_unique` (`key_prefix`),
  KEY `external_api_keys_application_idx` (`external_application_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `external_api_request_logs` (
  `external_api_request_log_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `external_application_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `external_api_key_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `application_name` VARCHAR(150) NULL DEFAULT NULL COMMENT 'Disalin saat request agar log lama tetap terbaca',
  `key_name` VARCHAR(150) NULL DEFAULT NULL,
  `method` VARCHAR(10) NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL COMMENT 'Path yang diminta, termasuk query string',
  `route_name` VARCHAR(150) NULL DEFAULT NULL,
  `api_version` VARCHAR(20) NULL DEFAULT NULL,
  `status_code` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `duration_ms` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` VARCHAR(255) NULL DEFAULT NULL,
  `requested_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`external_api_request_log_id`),
  KEY `external_api_request_logs_requested_at_idx` (`requested_at`),
  KEY `external_api_request_logs_application_idx` (`external_application_id`, `requested_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_28_220000_create_external_applications_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_28_220000_create_external_applications_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_28_220100_create_external_api_keys_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_28_220100_create_external_api_keys_table');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_07_28_220200_create_external_api_request_logs_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_07_28_220200_create_external_api_request_logs_table');
