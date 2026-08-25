-- Migration: 2026_08_23_090100_create_armada_match_reviews_table
-- Idempotent (CREATE TABLE IF NOT EXISTS). Alternatif: php artisan migrate
--
-- Antrean armada PMO yang cocok ke lebih dari satu pelanggan/armada Pegasus
-- saat dicocokkan lewat No Pol + PIC -- diselesaikan manual oleh operator
-- lewat langkah "Konfirmasi Armada Ambigu" di wizard Sinkronisasi.

CREATE TABLE IF NOT EXISTS `armada_match_reviews` (
  `armada_match_review_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ref_armada_id` BIGINT UNSIGNED NOT NULL COMMENT 'armada_id pada sistem PMO',
  `pic_name` VARCHAR(250) NULL,
  `nomer_pol` VARCHAR(100) NULL,
  `nomer_telp` VARCHAR(50) NULL,
  `saldo_armada` INT NULL DEFAULT 0,
  `candidate_customer_ids` JSON NOT NULL COMMENT 'customer_id yang bentrok saat pencocokan No Pol + PIC',
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, connected, discarded',
  `resolved_customer_id` INT NULL,
  `resolved_by` INT NULL,
  `resolved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`armada_match_review_id`),
  UNIQUE KEY `armada_match_reviews_ref_armada_id_unique` (`ref_armada_id`),
  KEY `armada_match_reviews_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Catat di migrations (supaya artisan migrate tidak bentrok)
SET @db := DATABASE();

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_08_23_090100_create_armada_match_reviews_table',
       (SELECT IFNULL(MAX(batch), 0) + 1 FROM migrations AS m)
WHERE EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'migrations')
  AND NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_08_23_090100_create_armada_match_reviews_table');

SELECT '2026_08_23_090100_create_armada_match_reviews_table OK' AS result;
