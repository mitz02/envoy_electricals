-- =====================================================
-- Database Migrations for Solar Calculator & Quotation System
-- Generated: 2026-09-26
-- Run on live database: mysql -u user -p database_name < new.sql
-- =====================================================

-- =====================================================
-- 1. SETTINGS TABLE
-- Created by: 2026_01_01_000012_create_media_audit_settings_tables.php
-- =====================================================
-- WARNING: Only run if table does not exist on live database

CREATE TABLE IF NOT EXISTS `settings` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(255) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `group` VARCHAR(255) NOT NULL DEFAULT 'general',
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example records that admin UI will create:
-- INSERT INTO `settings` (`key`, `value`, `group`) VALUES
-- ('calculator.panel_wattage', '620', 'calculator'),
-- ('calculator.panel_efficiency', '0.8', 'calculator'),
-- ('calculator.inverter_safety_factor', '1.4', 'calculator'),
-- ('calculator.battery_capacities', '1.2, 2.56, 5.12, 10.24, 16, 20, 25.6, 32', 'calculator'),
-- ('calculator.inverter_sizes', '1.5, 3, 5, 8, 10, 12', 'calculator'),
-- ('calculator.price_per_panel', '185000', 'calculator'),
-- ('calculator.price_per_kwh_daily', '220000', 'calculator'),
-- ('calculator.inverter_prices', '{"1.5": 350000, "3": 500000, "5": 750000, "8": 1100000, "10": 1300000, "12": 1500000}', 'calculator'),
-- ('calculator.battery_prices', '{"1.2": 80000, "2.56": 150000, "5.12": 300000, "10.24": 550000, "16": 850000, "20": 1050000, "25.6": 1300000, "32": 1600000}', 'calculator'),
-- ('calculator.installation_costs', '{"1.5": 300000, "3": 350000, "5": 450000, "8": 600000, "10": 700000, "12": 800000}', 'calculator');

-- =====================================================
-- 2. ADD additional_logistics TO QUOTATIONS TABLE
-- Created by: 2026_09_26_195451_add_additional_logistics_to_quotations_table.php
-- =====================================================

ALTER TABLE `quotations` 
ADD COLUMN `additional_logistics` DECIMAL(14,2) NULL DEFAULT 0 
AFTER `estimated_price`;

-- =====================================================
-- 3. SOLAR PACKAGE IMAGES TABLE
-- Created by: 2026_09_26_064926_create_solar_package_images_table.php
-- =====================================================

CREATE TABLE IF NOT EXISTS `solar_package_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `solar_package_id` BIGINT UNSIGNED NOT NULL,
    `media_id` BIGINT UNSIGNED NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`solar_package_id`) REFERENCES `solar_packages`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`media_id`) REFERENCES `media`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CUSTOM INVERTER CAPACITY FOR SOLAR PACKAGES
-- Created by: 2026_09_26_062027_add_custom_inverter_capacity_to_solar_packages_table.php
-- =====================================================

ALTER TABLE `solar_packages` 
ADD COLUMN IF NOT EXISTS `custom_inverter_capacity` VARCHAR(255) NULL 
AFTER `inverter_capacity`;

-- =====================================================
-- Verification Queries
-- =====================================================

-- Verify additional_logistics column
SHOW COLUMNS FROM `quotations` LIKE 'additional_logistics';

-- Verify settings table has calculator group
SELECT * FROM `settings` WHERE `group` = 'calculator';

-- =====================================================
-- Rollback (if needed)
-- =====================================================
-- ALTER TABLE `quotations` DROP COLUMN `additional_logistics`;
-- ALTER TABLE `solar_packages` DROP COLUMN `custom_inverter_capacity`;
-- DROP TABLE `solar_package_images`;
-- DROP TABLE `settings`;