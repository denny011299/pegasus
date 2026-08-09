-- Pengembalian produk jadi dari armada (tanpa referensi SO).
-- Idempotent untuk MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS `customer_product_returns` (
  `return_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(40) NOT NULL,
  `customer_id` INT NOT NULL,
  `return_date` DATE NOT NULL,
  `ref_number` VARCHAR(100) NULL,
  `notes` TEXT NULL,
  `proof_path` VARCHAR(255) NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1 COMMENT '0=deleted, 1=pending, 2=accepted, 3=declined',
  `created_by` INT NULL,
  `acc_by` INT NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_id`),
  UNIQUE KEY `cpr_return_number_unique` (`return_number`),
  KEY `cpr_customer_id_index` (`customer_id`),
  KEY `cpr_return_date_index` (`return_date`),
  KEY `cpr_status_index` (`status`),
  KEY `cpr_created_by_index` (`created_by`),
  KEY `cpr_acc_by_index` (`acc_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_product_return_details` (
  `return_detail_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` INT NOT NULL,
  `product_variant_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`),
  UNIQUE KEY `cpr_detail_item_unique` (`return_id`,`product_variant_id`,`unit_id`,`warehouse_id`),
  KEY `cpr_detail_return_id_index` (`return_id`),
  KEY `cpr_detail_product_variant_id_index` (`product_variant_id`),
  KEY `cpr_detail_unit_id_index` (`unit_id`),
  KEY `cpr_detail_warehouse_id_index` (`warehouse_id`),
  KEY `cpr_detail_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
