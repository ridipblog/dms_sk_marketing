-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for dms_jsw_2
CREATE DATABASE IF NOT EXISTS `dms_jsw_2` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `dms_jsw_2`;

-- Dumping structure for table dms_jsw_2.cache
CREATE TABLE IF NOT EXISTS `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.cache: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.cache_locks
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.cache_locks: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.cash_discount_slabs
CREATE TABLE IF NOT EXISTS `cash_discount_slabs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint unsigned NOT NULL,
  `slab_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `minimum_days` int NOT NULL,
  `maximum_days` int NOT NULL,
  `discount_percent` decimal(5,2) NOT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_discount_slabs_company_id_foreign` (`company_id`),
  CONSTRAINT `cash_discount_slabs_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.cash_discount_slabs: ~5 rows (approximately)
INSERT INTO `cash_discount_slabs` (`id`, `company_id`, `slab_name`, `minimum_days`, `maximum_days`, `discount_percent`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'ON ADVANCE PAYMENT', -9999, 0, 900.00, 'active', '2026-06-15 04:46:26', '2026-06-15 04:46:26'),
	(2, 1, '1 TO 4 DAYS', 1, 4, 700.00, 'active', '2026-06-15 04:46:26', '2026-06-15 04:46:26'),
	(3, 1, '5 TO 10 DAYS', 5, 10, 500.00, 'active', '2026-06-15 04:46:26', '2026-06-15 04:46:26'),
	(4, 1, '11 TO 15 DAYS', 11, 15, 300.00, 'active', '2026-06-15 04:46:26', '2026-06-15 04:46:26'),
	(5, 1, '16 TO 20 DAYS', 16, 20, 100.00, 'active', '2026-06-15 04:46:26', '2026-06-15 04:46:26');

-- Dumping structure for table dms_jsw_2.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_category_code_unique` (`category_code`),
  KEY `categories_company_id_foreign` (`company_id`),
  CONSTRAINT `categories_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.categories: ~2 rows (approximately)
INSERT INTO `categories` (`id`, `category_name`, `category_code`, `company_id`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'categry 1', 'cat001', 1, NULL, 'active', '2026-06-15 04:54:29', '2026-06-15 04:54:29'),
	(2, 'categry 2', 'cat002', 2, NULL, 'active', '2026-06-15 04:54:45', '2026-06-15 04:59:44');

-- Dumping structure for table dms_jsw_2.companies
CREATE TABLE IF NOT EXISTS `companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `company_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gst_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pan_no` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `companies_company_code_unique` (`company_code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.companies: ~2 rows (approximately)
INSERT INTO `companies` (`id`, `company_code`, `company_name`, `email`, `phone`, `gst_no`, `pan_no`, `address`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'AS-0001', 'NE Infra', 'neinfra@gmail.com', '1111111111', '8732783268736', 'AS3232M09', 'guwahati,kamrup,assam', 'active', NULL, NULL),
	(2, 'AS-0002', 'NE Infra 2', 'neinfra2@gmail.com', '1111111112', '8732783268731', 'AS3232M01', 'guwahati,kamrup,assam', 'active', NULL, NULL);

-- Dumping structure for table dms_jsw_2.credit_note_tracks
CREATE TABLE IF NOT EXISTS `credit_note_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_track_id` bigint unsigned NOT NULL,
  `cash_discount_slab_id` bigint unsigned NOT NULL,
  `nos` int NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `credit_note_tracks_payment_track_id_foreign` (`payment_track_id`),
  KEY `credit_note_tracks_cash_discount_slab_id_foreign` (`cash_discount_slab_id`),
  CONSTRAINT `credit_note_tracks_cash_discount_slab_id_foreign` FOREIGN KEY (`cash_discount_slab_id`) REFERENCES `cash_discount_slabs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `credit_note_tracks_payment_track_id_foreign` FOREIGN KEY (`payment_track_id`) REFERENCES `payment_tracks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.credit_note_tracks: ~4 rows (approximately)
INSERT INTO `credit_note_tracks` (`id`, `payment_track_id`, `cash_discount_slab_id`, `nos`, `amount`, `created_at`, `updated_at`) VALUES
	(1, 3, 2, 2, 364.00, '2026-06-15 06:37:02', '2026-06-15 06:37:02'),
	(2, 6, 1, 0, 80.10, '2026-06-16 05:18:12', '2026-06-16 05:18:12'),
	(3, 8, 3, 10, 33.00, '2026-06-16 05:32:54', '2026-06-16 05:32:54'),
	(4, 11, 3, 10, 120.00, '2026-06-16 05:47:31', '2026-06-16 05:47:31');

-- Dumping structure for table dms_jsw_2.dealers
CREATE TABLE IF NOT EXISTS `dealers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dealer_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pan_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `gst_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dealer_code` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dealers_dealer_code_unique` (`dealer_code`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.dealers: ~2 rows (approximately)
INSERT INTO `dealers` (`id`, `dealer_name`, `pan_number`, `email`, `phone`, `address`, `gst_number`, `dealer_code`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'delaer', 'AS322780M', 'delaer@gmail.com', '3728937298', 'panjabari,kamrup,assam', '4637643433', 'del1', 'active', '2026-06-15 04:50:40', '2026-06-15 04:50:40'),
	(2, 'dealer 2', 'AS322781M', 'delaer2@gmail.com', '3728937291', 'Goneshguri,kamrup,assam', '4637643431', 'del2', 'active', '2026-06-15 04:51:40', '2026-06-15 04:51:40');

-- Dumping structure for table dms_jsw_2.dealer_companies
CREATE TABLE IF NOT EXISTS `dealer_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `total_debit_note_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `total_credit_note_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `dealer_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `opening_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `created_by` bigint unsigned NOT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dealer_companies_dealer_id_company_id_unique` (`dealer_id`,`company_id`),
  KEY `dealer_companies_company_id_foreign` (`company_id`),
  KEY `dealer_companies_created_by_foreign` (`created_by`),
  KEY `dealer_companies_updated_by_foreign` (`updated_by`),
  CONSTRAINT `dealer_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `dealer_companies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `dealer_companies_dealer_id_foreign` FOREIGN KEY (`dealer_id`) REFERENCES `dealers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `dealer_companies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.dealer_companies: ~2 rows (approximately)
INSERT INTO `dealer_companies` (`id`, `total_debit_note_amount`, `total_credit_note_amount`, `dealer_id`, `company_id`, `opening_balance`, `created_by`, `updated_by`, `status`, `created_at`, `updated_at`) VALUES
	(1, 0.00, 0.00, 1, 1, 0.00, 1, NULL, 'active', '2026-06-15 04:50:40', '2026-06-15 04:50:40'),
	(2, 0.00, 0.00, 2, 1, 0.00, 1, 1, 'active', '2026-06-15 04:51:40', '2026-06-15 04:51:46');

-- Dumping structure for table dms_jsw_2.debit_note_tracks
CREATE TABLE IF NOT EXISTS `debit_note_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_track_id` bigint unsigned NOT NULL,
  `nos` int NOT NULL,
  `amount` decimal(18,2) NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `debit_note_tracks_payment_track_id_foreign` (`payment_track_id`),
  CONSTRAINT `debit_note_tracks_payment_track_id_foreign` FOREIGN KEY (`payment_track_id`) REFERENCES `payment_tracks` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.debit_note_tracks: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.failed_jobs
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.failed_jobs: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.invoices
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `buyer_id` bigint unsigned DEFAULT NULL,
  `ship_to` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL COMMENT 'user_role_company_mapId',
  `total_quantity` decimal(15,3) DEFAULT NULL COMMENT 'Store In MT',
  `total_amount` decimal(15,2) DEFAULT NULL COMMENT 'without GST',
  `chargeable_amount` decimal(15,2) DEFAULT NULL COMMENT 'with GST',
  `total_gst_amount` decimal(15,2) DEFAULT NULL,
  `total_cgst_amount` decimal(15,2) DEFAULT NULL,
  `total_sgst_amount` decimal(15,2) DEFAULT NULL,
  `cgst` decimal(5,2) NOT NULL DEFAULT '9.00' COMMENT 'CGST default 9%',
  `sgst` decimal(5,2) NOT NULL DEFAULT '9.00' COMMENT 'SGST default 9%',
  `gst` decimal(5,2) NOT NULL DEFAULT '18.00' COMMENT 'GST default 18%',
  `invoice_generate_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `no_of_goods` int DEFAULT NULL,
  `round_of` decimal(15,2) DEFAULT NULL,
  `invoice_status` tinyint DEFAULT '0' COMMENT '0=>not_generated,1=>generated',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_no_unique` (`invoice_no`),
  KEY `invoices_buyer_id_foreign` (`buyer_id`),
  KEY `invoices_ship_to_foreign` (`ship_to`),
  KEY `invoices_created_by_foreign` (`created_by`),
  CONSTRAINT `invoices_buyer_id_foreign` FOREIGN KEY (`buyer_id`) REFERENCES `dealer_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `user_role_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ship_to_foreign` FOREIGN KEY (`ship_to`) REFERENCES `dealer_companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.invoices: ~6 rows (approximately)
INSERT INTO `invoices` (`id`, `invoice_no`, `buyer_id`, `ship_to`, `created_by`, `total_quantity`, `total_amount`, `chargeable_amount`, `total_gst_amount`, `total_cgst_amount`, `total_sgst_amount`, `cgst`, `sgst`, `gst`, `invoice_generate_date`, `due_date`, `no_of_goods`, `round_of`, `invoice_status`, `created_at`, `updated_at`) VALUES
	(1, 'INV-202606-0000001', 1, 1, 4, 10.566, 206792.00, 244014.56, 37222.56, 18611.28, 18611.28, 9.00, 9.00, 18.00, '2026-06-15', '2026-07-06', 2, NULL, 1, '2026-06-15 05:26:16', '2026-06-15 06:26:16'),
	(6, 'INV-202606-0000002', 1, 1, 4, 10.000, 200000.00, 236000.00, 36000.00, 18000.00, 18000.00, 0.00, 0.00, 0.00, NULL, NULL, 1, NULL, 0, '2026-06-16 03:59:32', '2026-06-16 03:59:32'),
	(7, 'INV-202606-0000007', 2, 2, 4, 6.200, 84000.00, 99120.00, 15120.00, 7560.00, 7560.00, 0.00, 0.00, 0.00, NULL, NULL, 2, NULL, 0, '2026-06-16 03:59:33', '2026-06-16 04:11:24'),
	(8, 'INV-202606-0000008', 1, 1, 4, 10.899, 210788.00, 248729.84, 37941.84, 18970.92, 18970.92, 0.00, 0.00, 0.00, NULL, NULL, 2, NULL, 0, '2026-06-16 04:01:27', '2026-06-16 04:03:43'),
	(9, 'INV-202606-0000009', 2, 2, 4, 7.000, 100000.00, 118000.00, 18000.00, 9000.00, 9000.00, 0.00, 0.00, 0.00, '2026-06-16', '2026-07-07', 2, NULL, 1, '2026-06-16 04:01:27', '2026-06-16 05:17:55'),
	(10, 'INV-202606-0000010', 2, 2, 4, 1.100, 22000.00, 25960.00, 3960.00, 1980.00, 1980.00, 9.00, 9.00, 18.00, '2026-06-18', '2026-07-09', 1, NULL, 1, '2026-06-17 23:15:07', '2026-06-17 23:15:31');

-- Dumping structure for table dms_jsw_2.invoice_details
CREATE TABLE IF NOT EXISTS `invoice_details` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `product_pricing_id` bigint unsigned DEFAULT NULL,
  `cgst_amount` decimal(15,2) NOT NULL DEFAULT '9.00' COMMENT 'default 9%',
  `sgst_amount` decimal(15,2) NOT NULL DEFAULT '9.00' COMMENT 'default 9%',
  `gst_amount` decimal(15,2) DEFAULT NULL COMMENT 'cgst + sgst',
  `quantity` decimal(15,3) DEFAULT NULL COMMENT 'Store in MT',
  `total_amount` decimal(15,2) DEFAULT NULL COMMENT 'without GST',
  `chargeable_amount` decimal(15,2) DEFAULT NULL COMMENT 'with GST',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_details_invoice_id_foreign` (`invoice_id`),
  KEY `invoice_details_product_pricing_id_foreign` (`product_pricing_id`),
  CONSTRAINT `invoice_details_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoice_details_product_pricing_id_foreign` FOREIGN KEY (`product_pricing_id`) REFERENCES `product_pricings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.invoice_details: ~8 rows (approximately)
INSERT INTO `invoice_details` (`id`, `invoice_id`, `product_pricing_id`, `cgst_amount`, `sgst_amount`, `gst_amount`, `quantity`, `total_amount`, `chargeable_amount`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 18000.00, 18000.00, 36000.00, 10.000, 200000.00, 236000.00, '2026-06-15 05:27:04', '2026-06-15 05:27:04'),
	(2, 1, 2, 611.28, 611.28, 1222.56, 0.566, 6792.00, 8014.56, '2026-06-15 05:33:46', '2026-06-15 05:33:56'),
	(3, 6, 1, 18000.00, 18000.00, 36000.00, 10.000, 200000.00, 236000.00, '2026-06-16 03:59:32', '2026-06-16 03:59:32'),
	(4, 7, 2, 5400.00, 5400.00, 10800.00, 5.000, 60000.00, 70800.00, '2026-06-16 03:59:33', '2026-06-16 03:59:33'),
	(5, 8, 1, 18000.00, 18000.00, 36000.00, 10.000, 200000.00, 236000.00, '2026-06-16 04:01:27', '2026-06-16 04:01:27'),
	(6, 9, 2, 5400.00, 5400.00, 10800.00, 5.000, 60000.00, 70800.00, '2026-06-16 04:01:27', '2026-06-16 04:01:27'),
	(7, 8, 2, 970.92, 970.92, 1941.84, 0.899, 10788.00, 12729.84, '2026-06-16 04:03:43', '2026-06-16 04:03:43'),
	(8, 9, 1, 3600.00, 3600.00, 7200.00, 2.000, 40000.00, 47200.00, '2026-06-16 04:03:43', '2026-06-16 04:03:43'),
	(9, 7, 1, 2160.00, 2160.00, 4320.00, 1.200, 24000.00, 28320.00, '2026-06-16 04:11:24', '2026-06-16 04:11:24'),
	(10, 10, 1, 1980.00, 1980.00, 3960.00, 1.100, 22000.00, 25960.00, '2026-06-17 23:15:19', '2026-06-17 23:15:19');

-- Dumping structure for table dms_jsw_2.invoice_payments
CREATE TABLE IF NOT EXISTS `invoice_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `outstanding_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `paid_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `debit_note_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `credit_note_amount` decimal(15,2) NOT NULL DEFAULT '0.00',
  `clear_status` enum('clear payment','pending payment') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending payment',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_payments_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `invoice_payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.invoice_payments: ~2 rows (approximately)
INSERT INTO `invoice_payments` (`id`, `invoice_id`, `outstanding_amount`, `paid_amount`, `debit_note_amount`, `credit_note_amount`, `clear_status`, `created_at`, `updated_at`) VALUES
	(1, 1, 231650.56, 12000.00, 0.00, 364.00, 'pending payment', '2026-06-15 06:26:16', '2026-06-15 06:37:02'),
	(2, 9, 115146.40, 2620.50, 0.00, 233.10, 'pending payment', '2026-06-16 05:17:55', '2026-06-16 05:47:31'),
	(3, 10, 25960.00, 0.00, 0.00, 0.00, 'pending payment', '2026-06-17 23:15:31', '2026-06-17 23:15:31');

-- Dumping structure for table dms_jsw_2.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.jobs: ~1 rows (approximately)

-- Dumping structure for table dms_jsw_2.job_batches
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.job_batches: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.migrations
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.migrations: ~21 rows (approximately)
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
	(1, '0001_01_01_000000_create_users_table', 1),
	(2, '0001_01_01_000001_create_cache_table', 1),
	(3, '0001_01_01_000002_create_jobs_table', 1),
	(4, '2026_05_30_123112_create_roles_table', 1),
	(5, '2026_05_30_124059_create_companies_table', 1),
	(6, '2026_05_30_124734_create_user_role_companies_table', 1),
	(7, '2026_06_01_074009_create_dealers_table', 1),
	(8, '2026_06_01_074449_create_dealer_companies_table', 1),
	(9, '2026_06_01_084410_create_categories_table', 1),
	(10, '2026_06_01_092758_create_products_table', 1),
	(11, '2026_06_01_093515_create_product_pricings_table', 1),
	(12, '2026_06_01_100912_create_payment_tracks_table', 1),
	(13, '2026_06_01_101326_create_debit_note_tracks_table', 1),
	(14, '2026_06_01_101752_create_cash_discount_slabs_table', 1),
	(15, '2026_06_01_102348_create_credit_note_tracks_table', 1),
	(16, '2026_06_03_071404_create_upload_tracks_table', 1),
	(17, '2026_06_05_083823_create_voucher_types_table', 1),
	(18, '2026_06_05_083859_add_opening_balance_and_voucher_type_id', 1),
	(19, '2026_06_10_055424_create_invoices_table', 1),
	(20, '2026_06_10_061534_create_invoice_details_table', 1),
	(21, '2026_06_10_073734_update_payment_tracks_replace_order_with_invoice', 2),
	(22, '2026_06_12_071737_create_invoice_payments_table', 2),
	(23, '2026_06_16_081632_add_role_user_company_id_to_upload_tracks_table', 3),
	(24, '2026_06_16_085325_add_error_file_path_to_upload_tracks_table', 4);

-- Dumping structure for table dms_jsw_2.password_reset_tokens
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.password_reset_tokens: ~0 rows (approximately)

-- Dumping structure for table dms_jsw_2.payment_tracks
CREATE TABLE IF NOT EXISTS `payment_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `transaction_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `balance_amount` decimal(18,2) DEFAULT NULL,
  `payment_for_mt` decimal(18,3) DEFAULT NULL,
  `payment_mode` enum('cash','bank_transfer','cheque','upi','adjustment','entry') COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_date` datetime NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `voucher_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_tracks_voucher_type_id_foreign` (`voucher_type_id`),
  KEY `payment_tracks_invoice_id_foreign` (`invoice_id`),
  CONSTRAINT `payment_tracks_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `payment_tracks_voucher_type_id_foreign` FOREIGN KEY (`voucher_type_id`) REFERENCES `voucher_types` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.payment_tracks: ~9 rows (approximately)
INSERT INTO `payment_tracks` (`id`, `invoice_id`, `transaction_id`, `amount`, `balance_amount`, `payment_for_mt`, `payment_mode`, `transaction_date`, `remarks`, `created_at`, `updated_at`, `voucher_type_id`) VALUES
	(1, 1, NULL, 244014.56, 244014.56, 10.566, 'entry', '2026-06-15 11:56:16', 'Auto generated sale voucher on invoice finalization', '2026-06-15 06:26:16', '2026-06-15 06:26:16', 5),
	(2, 1, 'TXN-6A2FEAE6A2DF7', 12000.00, 232014.56, 0.520, 'cash', '2026-06-17 00:00:00', NULL, '2026-06-15 06:37:02', '2026-06-15 06:37:02', 1),
	(3, 1, 'TXN-6A2FEAE6A7F06', 364.00, 231650.56, 0.000, 'cash', '2026-06-17 00:00:00', 'Auto-generated Cash Discount at Rs 700.00/MT (1 TO 4 DAYS)', '2026-06-15 06:37:02', '2026-06-15 06:37:02', 3),
	(4, 9, NULL, 118000.00, 118000.00, 7.000, 'entry', '2026-06-16 10:47:55', 'Auto generated sale voucher on invoice finalization', '2026-06-16 05:17:55', '2026-06-16 05:17:55', 5),
	(5, 9, 'TXN-IMP-6A3129EC6BF93', 1500.50, 116499.50, 0.089, 'cash', '2026-06-16 00:00:00', 'Imported via Excel', '2026-06-16 05:18:12', '2026-06-16 05:18:12', 1),
	(6, 9, 'TXN-IMP-6A3129EC72CD6', 80.10, 116419.40, 0.000, 'cash', '2026-06-16 00:00:00', 'Auto-generated Cash Discount at Rs 900.00/MT (ON ADVANCE PAYMENT)', '2026-06-16 05:18:12', '2026-06-16 05:18:12', 3),
	(7, 9, 'TXN-IMP-6A312D5EC6D58', 1120.00, 115219.30, 0.066, 'cash', '2026-06-26 00:00:00', 'Imported via Excel', '2026-06-16 05:32:54', '2026-06-16 05:32:54', 1),
	(8, 9, 'TXN-IMP-6A312D5EC7953', 33.00, 115186.30, 0.000, 'cash', '2026-06-26 00:00:00', 'Auto-generated Cash Discount at Rs 500.00/MT (5 TO 10 DAYS)', '2026-06-16 05:32:54', '2026-06-16 05:32:54', 3),
	(11, 9, 'TXN-IMP-6A3130CBB9C95', 120.00, 115033.30, 0.000, 'cash', '2026-06-16 00:00:00', 'Imported via Excel', '2026-06-16 05:47:31', '2026-06-16 05:47:31', 3),
	(12, 10, NULL, 25960.00, 25960.00, 1.100, 'entry', '2026-06-18 04:45:31', 'Auto generated sale voucher on invoice finalization', '2026-06-17 23:15:31', '2026-06-17 23:15:31', 5);

-- Dumping structure for table dms_jsw_2.products
CREATE TABLE IF NOT EXISTS `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `product_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sku_code` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hsn_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `base_price` decimal(18,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_sku_code_unique` (`sku_code`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.products: ~2 rows (approximately)
INSERT INTO `products` (`id`, `category_id`, `product_name`, `sku_code`, `hsn_code`, `size`, `unit`, `base_price`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'product 1', 'code1', 'hsn_code1', '5', 'MT', 20000.00, 'active', '2026-06-15 05:00:33', '2026-06-15 05:00:33'),
	(2, 1, 'product 2', 'code2', 'hsn_code2', '10', 'MT', 12000.00, 'active', '2026-06-15 05:04:10', '2026-06-15 05:14:18');

-- Dumping structure for table dms_jsw_2.product_pricings
CREATE TABLE IF NOT EXISTS `product_pricings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `price_per_mt` decimal(18,2) NOT NULL,
  `gst_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `discount_amount` decimal(18,2) NOT NULL DEFAULT '0.00',
  `price_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_from` date NOT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_pricings_product_id_foreign` (`product_id`),
  CONSTRAINT `product_pricings_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.product_pricings: ~2 rows (approximately)
INSERT INTO `product_pricings` (`id`, `product_id`, `price_per_mt`, `gst_percentage`, `discount_amount`, `price_type`, `effective_from`, `effective_to`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 20000.00, 18.00, 0.00, NULL, '2026-06-15', '2026-06-30', 'active', '2026-06-15 05:24:01', '2026-06-15 05:24:01'),
	(2, 2, 12000.00, 18.00, 0.00, NULL, '2026-06-15', '2026-07-08', 'active', '2026-06-15 05:24:23', '2026-06-15 05:24:23');

-- Dumping structure for table dms_jsw_2.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.roles: ~7 rows (approximately)
INSERT INTO `roles` (`id`, `role_name`, `description`, `status`, `created_at`, `updated_at`, `priority`) VALUES
	(1, 'Super Admin', 'System-wide administrator with full privileges.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 1),
	(2, 'Admin Users', 'Administrative users with management capabilities.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 2),
	(3, 'Branch Manager (BM)', 'Manages business operations at a branch level.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 3),
	(4, 'Area Sales Manager (ASM)', 'Responsible for sales performance in specific areas.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 4),
	(5, 'Assistant Section Officer (ASO)', 'Assists with regional dealer accounts and field operations.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 5),
	(6, 'Dealer', 'Standard business dealer account.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 6),
	(7, 'Finance Team', 'Handles transaction, invoice, and payment tracking.', 'active', '2026-06-22 23:42:25', '2026-06-22 23:42:25', 7);

-- Dumping structure for table dms_jsw_2.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.sessions: ~1 rows (approximately)
INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('q6mmaVpYjRqwULaIYEdwQKcUttXYcu5yjKElgl5X', 3, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'YTo5OntzOjY6Il90b2tlbiI7czo0MDoiR0NMNTU1MTJMdGhmTmNDZG9KV0d6UTVkZTQ3WVZoVkxxMktvdHNJOSI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzU6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC91c2Vycy91cGxvYWRzIjtzOjU6InJvdXRlIjtzOjE5OiJ1c2Vycy51cGxvYWRzLmluZGV4Ijt9czo1MDoibG9naW5fd2ViXzU5YmEzNmFkZGMyYjJmOTQwMTU4MGYwMTRjN2Y1OGVhNGUzMDk4OWQiO2k6MztzOjEzOiJhY3RpdmVfbWFwX2lkIjtpOjE7czoxNzoiYWN0aXZlX2NvbXBhbnlfaWQiO2k6MTtzOjE5OiJhY3RpdmVfY29tcGFueV9uYW1lIjtzOjg6Ik5FIEluZnJhIjtzOjE0OiJhY3RpdmVfcm9sZV9pZCI7aToyO3M6MTY6ImFjdGl2ZV9yb2xlX25hbWUiO3M6MTE6IkFkbWluIFVzZXJzIjt9', 1782280612);

-- Dumping structure for table dms_jsw_2.upload_tracks
CREATE TABLE IF NOT EXISTS `upload_tracks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `role_user_company_id` bigint unsigned DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','processing','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_rows` int NOT NULL DEFAULT '0',
  `imported_rows` int NOT NULL DEFAULT '0',
  `failed_rows` int NOT NULL DEFAULT '0',
  `error_log` text COLLATE utf8mb4_unicode_ci,
  `error_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `upload_type` enum('inventory','dealers','users','accounts_invoices','accounts_vouchers') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inventory',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `upload_tracks_user_id_foreign` (`user_id`),
  KEY `upload_tracks_company_id_foreign` (`company_id`),
  KEY `upload_tracks_role_user_company_id_foreign` (`role_user_company_id`),
  CONSTRAINT `upload_tracks_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `upload_tracks_role_user_company_id_foreign` FOREIGN KEY (`role_user_company_id`) REFERENCES `user_role_companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `upload_tracks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.upload_tracks: ~17 rows (approximately)
INSERT INTO `upload_tracks` (`id`, `user_id`, `company_id`, `role_user_company_id`, `file_name`, `status`, `total_rows`, `imported_rows`, `failed_rows`, `error_log`, `error_file_path`, `upload_type`, `created_at`, `updated_at`) VALUES
	(1, 1, 1, 1, 'invoice_upload_template (1).csv', 'failed', 0, 0, 0, 'System Error: File [D:\\dealer_management_system\\delaler_management_system_jsw\\storage\\app/local/accounts_uploads/1781600555_invoice_upload_template (1).csv] does not exist and can therefore not be imported.', NULL, 'accounts_invoices', '2026-06-16 03:32:35', '2026-06-16 03:32:36'),
	(2, 1, 1, 1, 'invoice_upload_template (1).csv', 'failed', 0, 0, 0, 'System Error: File [D:\\dealer_management_system\\delaler_management_system_jsw\\storage\\app/local/accounts_uploads/1781600743_invoice_upload_template (1).csv] does not exist and can therefore not be imported.', NULL, 'accounts_invoices', '2026-06-16 03:35:43', '2026-06-16 03:35:46'),
	(3, 1, 1, 1, 'invoice_upload_template (1).csv', 'failed', 0, 0, 0, 'System Error: File [D:\\dealer_management_system\\delaler_management_system_jsw\\storage\\app/local/accounts_uploads/1781601201_invoice_upload_template (1).csv] does not exist and can therefore not be imported.', NULL, 'accounts_invoices', '2026-06-16 03:43:21', '2026-06-16 03:43:23'),
	(4, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 0, 2, 'Group (delaer|16-06-2026) Failed: Buyer code delaer not found in this company.\nGroup (delaer 2|16-06-2026) Failed: Buyer code delaer 2 not found in this company.', 'accounts_uploads/errors/error_4_1781601409.csv', 'accounts_invoices', '2026-06-16 03:46:47', '2026-06-16 03:46:49'),
	(5, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 0, 2, 'Group (del1|16-06-2026) Failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'product_code\' in \'EXISTS subquery\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: select * from `products` where exists (select * from `categories` where `products`.`category_id` = `categories`.`id` and `company_id` = 1) and `product_code` = code1 limit 1)\nGroup (del2|16-06-2026) Failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'product_code\' in \'EXISTS subquery\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: select * from `products` where exists (select * from `categories` where `products`.`category_id` = `categories`.`id` and `company_id` = 1) and `product_code` = code2 limit 1)', 'accounts_uploads/errors/error_5_1781601840.csv', 'accounts_invoices', '2026-06-16 03:53:59', '2026-06-16 03:54:00'),
	(6, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 0, 2, 'Group (del1|16-06-2026) Failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'product_code\' in \'EXISTS subquery\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: select * from `products` where exists (select * from `categories` where `products`.`category_id` = `categories`.`id` and `company_id` = 1) and `product_code` = hsn_code1 limit 1)\nGroup (del2|16-06-2026) Failed: SQLSTATE[42S22]: Column not found: 1054 Unknown column \'product_code\' in \'EXISTS subquery\' (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: select * from `products` where exists (select * from `categories` where `products`.`category_id` = `categories`.`id` and `company_id` = 1) and `product_code` = hsn_code2 limit 1)', 'accounts_uploads/errors/error_6_1781602042.csv', 'accounts_invoices', '2026-06-16 03:57:19', '2026-06-16 03:57:22'),
	(7, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 2, 0, NULL, NULL, 'accounts_invoices', '2026-06-16 03:59:30', '2026-06-16 03:59:33'),
	(8, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 2, 0, NULL, NULL, 'accounts_invoices', '2026-06-16 04:01:25', '2026-06-16 04:01:27'),
	(9, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 2, 0, NULL, NULL, 'accounts_invoices', '2026-06-16 04:03:42', '2026-06-16 04:03:43'),
	(10, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 0, 2, 'Group (INV-202606-0000007) Failed: Buyer code  not found in this company.\nGroup (INV-202606-0000009111111) Failed: Buyer code del22 not found in this company.', 'accounts_uploads/errors/error_10_1781602781.csv', 'accounts_invoices', '2026-06-16 04:09:41', '2026-06-16 04:09:41'),
	(11, 1, 1, 1, 'invoice_upload_template (1).csv', 'completed', 2, 1, 1, 'Group (INV-202606-0000009111111) Failed: Buyer code del22 not found in this company.', 'accounts_uploads/errors/error_11_1781602884.csv', 'accounts_invoices', '2026-06-16 04:11:21', '2026-06-16 04:11:24'),
	(12, 1, 1, 1, 'voucher_upload_template.csv', 'completed', 1, 0, 1, 'Row 2: Invoice INV-202606-0000009 is not finalized. Vouchers cannot be added.', 'accounts_uploads/errors/error_12_1781606859.csv', 'accounts_vouchers', '2026-06-16 05:17:37', '2026-06-16 05:17:39'),
	(13, 1, 1, 1, 'voucher_upload_template.csv', 'completed', 1, 1, 0, NULL, NULL, 'accounts_vouchers', '2026-06-16 05:18:11', '2026-06-16 05:18:12'),
	(14, 1, 1, 1, 'voucher_upload_template.csv', 'completed', 2, 1, 1, 'Row 3: SQLSTATE[01000]: Warning: 1265 Data truncated for column \'payment_mode\' at row 1 (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: insert into `payment_tracks` (`invoice_id`, `transaction_id`, `amount`, `balance_amount`, `payment_for_mt`, `payment_mode`, `voucher_type_id`, `transaction_date`, `remarks`, `updated_at`, `created_at`) values (9, TXN-IMP-6A312D5ECA358, 120, 115033.3, 0, Credit Note, 3, 2026-06-16 00:00:00, Imported via Excel, 2026-06-16 11:02:54, 2026-06-16 11:02:54))', 'accounts_uploads/errors/error_14_1781607774.csv', 'accounts_vouchers', '2026-06-16 05:32:52', '2026-06-16 05:32:54'),
	(15, 1, 1, 1, 'voucher_upload_template.csv', 'completed', 1, 0, 1, 'Row 2: SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'nos\' cannot be null (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: insert into `credit_note_tracks` (`payment_track_id`, `nos`, `amount`, `cash_discount_slab_id`, `updated_at`, `created_at`) values (9, ?, 120, ?, 2026-06-16 11:05:37, 2026-06-16 11:05:37))', 'accounts_uploads/errors/error_15_1781607937.csv', 'accounts_vouchers', '2026-06-16 05:35:37', '2026-06-16 05:35:37'),
	(16, 1, 1, 1, 'voucher_upload_template.csv', 'completed', 1, 0, 1, 'Row 2: SQLSTATE[23000]: Integrity constraint violation: 1048 Column \'nos\' cannot be null (Connection: mysql, Host: 127.0.0.1, Port: 3306, Database: dms_jsw_2, SQL: insert into `credit_note_tracks` (`payment_track_id`, `nos`, `amount`, `cash_discount_slab_id`, `updated_at`, `created_at`) values (10, ?, 120, ?, 2026-06-16 11:14:55, 2026-06-16 11:14:55))', 'accounts_uploads/errors/error_16_1781608495.csv', 'accounts_vouchers', '2026-06-16 05:44:52', '2026-06-16 05:44:55'),
	(17, 1, 1, 1, 'voucher_upload_template (1).csv', 'completed', 1, 1, 0, NULL, NULL, 'accounts_vouchers', '2026-06-16 05:47:29', '2026-06-16 05:47:31'),
	(18, 3, 1, 1, 'user_upload_template.csv', 'completed', 2, 2, 0, NULL, NULL, 'users', '2026-06-24 00:23:12', '2026-06-24 00:23:15'),
	(19, 3, 1, 1, 'user_upload_template.csv', 'completed', 2, 0, 2, 'Row 2: The email field is required.\nRow 3: The email field is required.', 'user_uploads/errors/error_19_1782280510.csv', 'users', '2026-06-24 00:25:09', '2026-06-24 00:25:10');

-- Dumping structure for table dms_jsw_2.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_phone_unique` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.users: ~3 rows (approximately)
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `designation`, `status`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
	(1, 'user 1', 'user1@gmail.com', '9999999999', 'finance department', 'active', NULL, '$2y$12$sP.vh0oWV5KgNuBHYfKgWe3cR2zbfQHIIs1Cw507NCpJhhNRv7ty.', NULL, NULL, NULL),
	(2, 'user 2', 'user2@gmail.com', '9999999991', 'finance department', 'active', NULL, '$2y$12$glfoInjzZ9pv85B/7ylrcucfBToflpb5db0ZKJWFq9aGnX00Dp1y6', NULL, '2026-06-23 00:30:56', '2026-06-23 00:30:56'),
	(3, 'user 3', 'user3@gmail.com', '9999999992', 'admin department', 'active', NULL, '$2y$12$mkBmOhGtbbuAQfe/nGxJkeIE7KNVL3qWkvYWwyegI04gG5s8WvIk.', NULL, '2026-06-23 00:31:16', '2026-06-23 00:31:16'),
	(4, 'John Doe', 'john.doe@example.com', '9876543210', 'Area Manager', 'active', NULL, '$2y$12$Ftr5jqYyMehS5THXbWzJ9./O1hE6skS17Xjdc/QpddBnjDKl.85Di', NULL, '2026-06-24 00:23:15', '2026-06-24 00:23:15');

-- Dumping structure for table dms_jsw_2.user_role_companies
CREATE TABLE IF NOT EXISTS `user_role_companies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  `company_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_role_companies_user_id_role_id_company_id_unique` (`user_id`,`role_id`,`company_id`),
  KEY `user_role_companies_role_id_foreign` (`role_id`),
  KEY `user_role_companies_company_id_foreign` (`company_id`),
  CONSTRAINT `user_role_companies_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_role_companies_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `user_role_companies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.user_role_companies: ~2 rows (approximately)
INSERT INTO `user_role_companies` (`id`, `user_id`, `role_id`, `company_id`, `created_at`, `updated_at`) VALUES
	(1, 3, 2, 1, '2026-06-23 00:31:35', '2026-06-23 00:31:35'),
	(2, 3, 2, 2, '2026-06-23 00:35:22', '2026-06-23 00:35:22'),
	(4, 1, 7, 1, '2026-06-23 23:48:08', '2026-06-23 23:48:08'),
	(5, 4, 4, 1, '2026-06-24 00:23:15', '2026-06-24 00:23:15'),
	(6, 4, 4, 2, '2026-06-24 00:23:15', '2026-06-24 00:23:15');

-- Dumping structure for table dms_jsw_2.voucher_types
CREATE TABLE IF NOT EXISTS `voucher_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `voucher_types_name_unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table dms_jsw_2.voucher_types: ~5 rows (approximately)
INSERT INTO `voucher_types` (`id`, `name`, `description`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Receipt', 'Payment received from dealer', 1, '2026-06-15 06:04:10', '2026-06-15 06:04:10'),
	(2, 'Journal', 'Adjustment entry', 1, '2026-06-15 06:04:10', '2026-06-15 06:04:10'),
	(3, 'Credit Note', 'Amount reduced from dealer account', 1, '2026-06-15 06:04:10', '2026-06-15 06:04:10'),
	(4, 'Debit Note', 'Extra amount added with GST', 1, '2026-06-15 06:04:10', '2026-06-15 06:04:10'),
	(5, 'SALES', 'Sales invoice entry', 1, '2026-06-15 06:04:10', '2026-06-15 06:04:10');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
