-- Pengembalian bahan/kemasan customer dari Sales Order diterima.
-- Idempotent untuk MySQL/MariaDB; jalankan pada database Pegasus.

CREATE TABLE IF NOT EXISTS `customer_supply_returns` (
  `return_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_number` VARCHAR(40) NOT NULL,
  `so_id` INT NOT NULL,
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
  UNIQUE KEY `csr_return_number_unique` (`return_number`),
  KEY `csr_so_id_index` (`so_id`),
  KEY `csr_customer_id_index` (`customer_id`),
  KEY `csr_return_date_index` (`return_date`),
  KEY `csr_status_index` (`status`),
  KEY `csr_created_by_index` (`created_by`),
  KEY `csr_acc_by_index` (`acc_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `customer_supply_return_details` (
  `return_detail_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `return_id` INT NOT NULL,
  `supplies_id` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `warehouse_id` INT NOT NULL,
  `qty` BIGINT UNSIGNED NOT NULL,
  `status` TINYINT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`return_detail_id`),
  UNIQUE KEY `csr_detail_item_unique` (`return_id`,`supplies_id`,`unit_id`,`warehouse_id`),
  KEY `csr_detail_return_id_index` (`return_id`),
  KEY `csr_detail_supplies_id_index` (`supplies_id`),
  KEY `csr_detail_unit_id_index` (`unit_id`),
  KEY `csr_detail_warehouse_id_index` (`warehouse_id`),
  KEY `csr_detail_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
