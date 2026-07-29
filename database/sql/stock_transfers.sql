-- Stock Transfer tables (manual run jika migrate belum dijalankan)
-- Status: 0=deleted, 1=pending, 2=success, 3=rejected

CREATE TABLE IF NOT EXISTS `stock_transfers` (
  `st_id` int unsigned NOT NULL AUTO_INCREMENT,
  `transfer_code` varchar(30) NOT NULL,
  `transfer_date` date NOT NULL,
  `sender_id` int unsigned NOT NULL,
  `receiver_id` int unsigned DEFAULT NULL,
  `from_warehouse_id` bigint unsigned NOT NULL,
  `to_warehouse_id` bigint unsigned NOT NULL,
  `note` longtext,
  `accept_note` longtext,
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '0=deleted,1=pending,2=success,3=rejected',
  `created_by` int unsigned DEFAULT NULL,
  `acc_by` int unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`st_id`),
  UNIQUE KEY `stock_transfers_transfer_code_unique` (`transfer_code`),
  KEY `stock_transfers_from_wh_idx` (`from_warehouse_id`),
  KEY `stock_transfers_to_wh_idx` (`to_warehouse_id`),
  KEY `stock_transfers_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `stock_transfer_details` (
  `std_id` int unsigned NOT NULL AUTO_INCREMENT,
  `st_id` int unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `product_variant_id` int unsigned NOT NULL,
  `unit_id` int unsigned NOT NULL,
  `qty` decimal(18,4) NOT NULL DEFAULT 0.0000,
  `qty_received` decimal(18,4) DEFAULT NULL,
  `status` tinyint NOT NULL DEFAULT 1 COMMENT '1=active,0=inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`std_id`),
  KEY `stock_transfer_details_st_id_idx` (`st_id`),
  KEY `stock_transfer_details_pv_idx` (`product_variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
