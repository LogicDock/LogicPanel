-- LogicPanel Database Schema
-- MySQL/MariaDB - Complete Schema with all features

CREATE DATABASE IF NOT EXISTS `logicpanel` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `logicpanel`;

-- Users table
CREATE TABLE IF NOT EXISTS `lp_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `name` VARCHAR(100) NULL,
    `role` ENUM('user', 'reseller', 'admin') DEFAULT 'user',
    `parent_id` INT UNSIGNED NULL,
    `reseller_package_id` INT UNSIGNED NULL,
    `theme` ENUM('light', 'dark', 'auto') DEFAULT 'auto',
    `whmcs_user_id` INT UNSIGNED NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `two_factor_enabled` TINYINT(1) DEFAULT 0,
    `two_factor_secret` VARCHAR(255) NULL,
    `last_login` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_parent` (`parent_id`),
    INDEX `idx_whmcs_user` (`whmcs_user_id`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reseller Packages table
CREATE TABLE IF NOT EXISTS `lp_reseller_packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `max_users` INT UNSIGNED DEFAULT 50,
    `max_services` INT UNSIGNED DEFAULT 500,
    `max_disk_gb` INT UNSIGNED DEFAULT 100,
    `max_bandwidth_gb` INT UNSIGNED DEFAULT 1000,
    `can_create_packages` TINYINT(1) DEFAULT 1,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages table
CREATE TABLE IF NOT EXISTS `lp_packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `reseller_id` INT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `is_public` TINYINT(1) DEFAULT 1,
    `memory_limit` INT UNSIGNED NOT NULL DEFAULT 512,
    `cpu_limit` DECIMAL(4,2) NOT NULL DEFAULT 0.50,
    `disk_limit` INT UNSIGNED NOT NULL DEFAULT 5120,
    `bandwidth_limit` BIGINT UNSIGNED DEFAULT 0,
    `io_limit` INT UNSIGNED DEFAULT 0,
    `max_domains` INT UNSIGNED DEFAULT 3,
    `max_databases` INT UNSIGNED DEFAULT 1,
    `max_backups` INT UNSIGNED DEFAULT 3,
    `max_deployments_per_day` INT UNSIGNED DEFAULT 10,
    `allow_ssh` TINYINT(1) DEFAULT 1,
    `allow_git_deploy` TINYINT(1) DEFAULT 1,
    `allow_custom_node_version` TINYINT(1) DEFAULT 0,
    `allowed_node_versions` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `sort_order` INT UNSIGNED DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_reseller` (`reseller_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services table
CREATE TABLE IF NOT EXISTS `lp_services` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `package_id` INT UNSIGNED NULL,
    `name` VARCHAR(100) NOT NULL,
    `container_id` VARCHAR(64) NULL,
    `container_name` VARCHAR(100) NULL,
    `status` ENUM('pending', 'creating', 'running', 'stopped', 'error', 'suspended', 'terminated') DEFAULT 'pending',
    `runtime` VARCHAR(20) DEFAULT 'nodejs',
    `node_version` VARCHAR(10) DEFAULT '20',
    `port` INT UNSIGNED DEFAULT 3000,
    `git_repo` VARCHAR(500) NULL,
    `git_branch` VARCHAR(100) DEFAULT 'main',
    `github_pat` TEXT NULL,
    `install_cmd` VARCHAR(500) DEFAULT 'npm install',
    `build_cmd` VARCHAR(500) DEFAULT 'npm run build',
    `start_cmd` VARCHAR(500) DEFAULT 'npm start',
    `env_vars` JSON NULL,
    `disk_used` BIGINT UNSIGNED DEFAULT 0,
    `bandwidth_used` BIGINT UNSIGNED DEFAULT 0,
    `whmcs_service_id` INT UNSIGNED NULL,
    `plan` VARCHAR(50) DEFAULT 'basic',
    `provisioned_at` DATETIME NULL,
    `suspended_at` DATETIME NULL,
    `suspended_reason` VARCHAR(255) NULL,
    `error_message` TEXT NULL,
    `expires_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `lp_users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`package_id`) REFERENCES `lp_packages`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_package` (`package_id`),
    INDEX `idx_container` (`container_id`),
    INDEX `idx_whmcs_service` (`whmcs_service_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domains table
CREATE TABLE IF NOT EXISTS `lp_domains` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT UNSIGNED NOT NULL,
    `domain` VARCHAR(255) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 0,
    `ssl_enabled` TINYINT(1) DEFAULT 1,
    `ssl_expires_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `lp_services`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_domain` (`domain`),
    INDEX `idx_service` (`service_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Databases table
CREATE TABLE IF NOT EXISTS `lp_databases` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT UNSIGNED NOT NULL,
    `container_id` VARCHAR(64) NULL,
    `container_name` VARCHAR(100) NULL,
    `type` ENUM('mariadb', 'postgresql', 'mongodb') NOT NULL,
    `db_name` VARCHAR(100) NOT NULL,
    `db_user` VARCHAR(100) NOT NULL,
    `db_password` TEXT NOT NULL,
    `root_password` TEXT NULL,
    `port` INT UNSIGNED NULL,
    `status` ENUM('creating', 'running', 'stopped', 'error') DEFAULT 'creating',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `lp_services`(`id`) ON DELETE CASCADE,
    INDEX `idx_service` (`service_id`),
    INDEX `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backups table
CREATE TABLE IF NOT EXISTS `lp_backups` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT UNSIGNED NOT NULL,
    `filename` VARCHAR(255) NOT NULL,
    `path` VARCHAR(500) NOT NULL,
    `size` BIGINT UNSIGNED DEFAULT 0,
    `type` ENUM('full', 'files', 'database') DEFAULT 'full',
    `status` ENUM('pending', 'in_progress', 'completed', 'failed') DEFAULT 'pending',
    `notes` TEXT NULL,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `lp_services`(`id`) ON DELETE CASCADE,
    INDEX `idx_service` (`service_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deployments table
CREATE TABLE IF NOT EXISTS `lp_deployments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `service_id` INT UNSIGNED NOT NULL,
    `commit_hash` VARCHAR(40) NULL,
    `commit_message` TEXT NULL,
    `branch` VARCHAR(100) NULL,
    `status` ENUM('pending', 'cloning', 'installing', 'building', 'starting', 'completed', 'failed') DEFAULT 'pending',
    `log` LONGTEXT NULL,
    `started_at` DATETIME NULL,
    `completed_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`service_id`) REFERENCES `lp_services`(`id`) ON DELETE CASCADE,
    INDEX `idx_service` (`service_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SSO Tokens table
CREATE TABLE IF NOT EXISTS `lp_sso_tokens` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `lp_users`(`id`) ON DELETE CASCADE,
    INDEX `idx_token` (`token`),
    INDEX `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys table
CREATE TABLE IF NOT EXISTS `lp_api_keys` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `api_key` VARCHAR(64) NOT NULL UNIQUE,
    `api_secret` VARCHAR(64) NOT NULL,
    `permissions` JSON NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `last_used_at` DATETIME NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE IF NOT EXISTS `lp_settings` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE,
    `value` TEXT NULL,
    `type` ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log table
CREATE TABLE IF NOT EXISTS `lp_activity_log` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `service_id` INT UNSIGNED NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_service` (`service_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table
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

-- Login Attempts table
CREATE TABLE IF NOT EXISTS `lp_login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `ip_address` VARCHAR(45) NOT NULL,
    `username` VARCHAR(100) NULL,
    `success` TINYINT(1) DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ip` (`ip_address`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `lp_settings` (`key`, `value`, `type`) VALUES
('app_name', 'LogicPanel', 'string'),
('app_version', '1.0.0', 'string'),
('default_node_version', '20', 'string'),
('max_services_per_user', '10', 'int'),
('max_databases_per_service', '3', 'int'),
('max_domains_per_service', '5', 'int'),
('max_backups_per_service', '5', 'int'),
('backup_retention_days', '30', 'int'),
('allow_registration', '0', 'bool'),
('maintenance_mode', '0', 'bool')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Create default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO `lp_users` (`username`, `email`, `password`, `name`, `role`) VALUES
('admin', 'admin@localhost', '$2y$12$LQv3c1yqBWVHxkd0LHAkCOYz6TtxMQJqhN8/X4oaU/PguQm3JQF0W', 'Administrator', 'admin')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Create default API key for WHMCS
INSERT INTO `lp_api_keys` (`name`, `api_key`, `api_secret`, `permissions`) VALUES
('WHMCS Integration', 'lp_whmcs_default_key_change_me', 'lp_whmcs_default_secret_change_me', '{"create": true, "suspend": true, "terminate": true, "sso": true}')
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;

-- Create default packages
INSERT INTO `lp_packages` (`name`, `display_name`, `description`, `memory_limit`, `cpu_limit`, `disk_limit`, `bandwidth_limit`, `max_domains`, `max_databases`, `max_backups`, `sort_order`) VALUES
('starter', 'Starter', 'Perfect for small projects and testing', 512, 0.50, 5120, 51200, 2, 1, 3, 1),
('pro', 'Professional', 'For growing applications with moderate traffic', 1024, 1.00, 10240, 102400, 5, 2, 5, 2),
('business', 'Business', 'For high-traffic production applications', 2048, 2.00, 20480, 204800, 10, 3, 10, 3),
('enterprise', 'Enterprise', 'Maximum resources for enterprise applications', 4096, 4.00, 51200, 0, 25, 5, 25, 4)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;
