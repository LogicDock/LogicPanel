-- LogicPanel Migration: Add Multi-Language Support
-- Migration: 003_add_multi_language.sql
-- Date: 2026-01-18

-- ============================================
-- Step 1: Add language field to services table
-- ============================================

ALTER TABLE `lp_services`
  ADD COLUMN `language` VARCHAR(20) DEFAULT 'nodejs' AFTER `status`,
  ADD COLUMN `language_version` VARCHAR(20) DEFAULT '20' AFTER `language`,
  ADD COLUMN `disk_limit_mb` INT UNSIGNED DEFAULT 5120 AFTER `bandwidth_used`,
  ADD COLUMN `ram_limit_mb` INT UNSIGNED DEFAULT 512 AFTER `disk_limit_mb`,
  ADD COLUMN `cpu_limit` DECIMAL(4,2) DEFAULT 1.00 AFTER `ram_limit_mb`;

-- Rename node_version to language_version (keep for backward compatibility)
-- UPDATE `lp_services` SET `language_version` = `node_version`;

-- ============================================
-- Step 2: Add index for language
-- ============================================

ALTER TABLE `lp_services`
  ADD INDEX `idx_language` (`language`);

-- ============================================
-- Step 3: Update existing services to nodejs
-- ============================================

UPDATE `lp_services` SET `language` = 'nodejs' WHERE `language` IS NULL;
