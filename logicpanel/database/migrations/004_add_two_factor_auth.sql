-- Migration: Add Two-Factor Authentication support
-- Date: 2026-01-19

-- Add 2FA columns to users table
ALTER TABLE `lp_users` 
    ADD COLUMN `two_factor_enabled` TINYINT(1) DEFAULT 0 AFTER `is_active`,
    ADD COLUMN `two_factor_secret` VARCHAR(255) NULL AFTER `two_factor_enabled`;
