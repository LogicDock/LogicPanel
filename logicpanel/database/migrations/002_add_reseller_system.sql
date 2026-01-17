-- LogicPanel Migration: Add Reseller Role System
-- Migration: 002_add_reseller_system.sql
-- Date: 2026-01-17

-- ============================================
-- Step 1: Modify users table for 3-level hierarchy
-- ============================================

-- Add 'reseller' to role enum
ALTER TABLE `lp_users` 
  MODIFY COLUMN `role` ENUM('user', 'reseller', 'admin') DEFAULT 'user';

-- Add parent_id for user hierarchy (reseller -> user relationship)
ALTER TABLE `lp_users` 
  ADD COLUMN `parent_id` INT UNSIGNED NULL AFTER `role`,
  ADD COLUMN `reseller_package_id` INT UNSIGNED NULL AFTER `parent_id`,
  ADD INDEX `idx_parent` (`parent_id`);

-- ============================================
-- Step 2: Create Reseller Packages table
-- These are created by Admin for Resellers
-- ============================================

CREATE TABLE IF NOT EXISTS `lp_reseller_packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    
    -- Reseller Limits
    `max_users` INT UNSIGNED DEFAULT 50 COMMENT 'Max users this reseller can create',
    `max_services_per_user` INT UNSIGNED DEFAULT 5 COMMENT 'Max services each of their users can have',
    `max_total_disk_gb` INT UNSIGNED DEFAULT 100 COMMENT 'Total disk across all users',
    `max_total_ram_gb` INT UNSIGNED DEFAULT 16 COMMENT 'Total RAM across all users',
    
    -- Can create custom packages for their users
    `can_create_packages` TINYINT(1) DEFAULT 1,
    `can_oversell` TINYINT(1) DEFAULT 0 COMMENT 'Allow overselling resources',
    
    -- Pricing (for future billing)
    `price_monthly` DECIMAL(10,2) DEFAULT 0,
    `price_yearly` DECIMAL(10,2) DEFAULT 0,
    
    -- Status
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Step 3: Modify lp_packages table
-- Add reseller_id so resellers can create their own packages
-- ============================================

ALTER TABLE `lp_packages`
  ADD COLUMN `reseller_id` INT UNSIGNED NULL AFTER `id`,
  ADD COLUMN `is_public` TINYINT(1) DEFAULT 1 COMMENT 'Show in public listing',
  ADD INDEX `idx_reseller` (`reseller_id`);

-- ============================================
-- Step 4: Create User Sessions table for security
-- ============================================

CREATE TABLE IF NOT EXISTS `lp_sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `user_agent` TEXT NULL,
    `payload` TEXT NULL,
    `last_activity` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `lp_users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Step 5: Create Login Attempts table for rate limiting
-- ============================================

CREATE TABLE IF NOT EXISTS `lp_login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(100) NULL,
    `success` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- Step 6: Insert default reseller packages
-- ============================================

INSERT INTO `lp_reseller_packages` (`name`, `display_name`, `description`, `max_users`, `max_services_per_user`, `max_total_disk_gb`, `max_total_ram_gb`, `price_monthly`) VALUES
('starter_reseller', 'Starter Reseller', 'For small hosting providers', 30, 3, 50, 8, 29.99),
('pro_reseller', 'Pro Reseller', 'For growing hosting businesses', 100, 5, 200, 32, 79.99),
('enterprise_reseller', 'Enterprise Reseller', 'For large hosting companies', 500, 10, 1000, 128, 199.99)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;
