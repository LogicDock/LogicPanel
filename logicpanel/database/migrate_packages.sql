-- Add packages table
CREATE TABLE IF NOT EXISTS `lp_packages` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `display_name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `memory_limit` INT UNSIGNED NOT NULL DEFAULT 512 COMMENT 'RAM in MB',
    `cpu_limit` DECIMAL(4,2) NOT NULL DEFAULT 0.50 COMMENT 'CPU cores',
    `disk_limit` INT UNSIGNED NOT NULL DEFAULT 5120 COMMENT 'Disk space in MB',
    `bandwidth_limit` BIGINT UNSIGNED DEFAULT 0 COMMENT 'Monthly bandwidth in MB (0 = unlimited)',
    `io_limit` INT UNSIGNED DEFAULT 0 COMMENT 'I/O speed limit in MB/s',
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
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add package_id to services if not exists
ALTER TABLE `lp_services` ADD COLUMN IF NOT EXISTS `package_id` INT UNSIGNED NULL AFTER `user_id`;
ALTER TABLE `lp_services` ADD COLUMN IF NOT EXISTS `disk_used` BIGINT UNSIGNED DEFAULT 0 AFTER `env_vars`;
ALTER TABLE `lp_services` ADD COLUMN IF NOT EXISTS `bandwidth_used` BIGINT UNSIGNED DEFAULT 0 AFTER `disk_used`;
ALTER TABLE `lp_services` ADD COLUMN IF NOT EXISTS `suspended_reason` VARCHAR(255) NULL AFTER `suspended_at`;

-- Insert default packages
INSERT INTO `lp_packages` (`name`, `display_name`, `description`, `memory_limit`, `cpu_limit`, `disk_limit`, `bandwidth_limit`, `max_domains`, `max_databases`, `max_backups`, `sort_order`) VALUES
('starter', 'Starter', 'Perfect for small projects and testing', 512, 0.50, 5120, 51200, 2, 1, 3, 1),
('pro', 'Professional', 'For growing applications with moderate traffic', 1024, 1.00, 10240, 102400, 5, 1, 5, 2),
('business', 'Business', 'For high-traffic production applications', 2048, 2.00, 20480, 204800, 10, 1, 10, 3),
('enterprise', 'Enterprise', 'Maximum resources for enterprise applications', 4096, 4.00, 51200, 0, 25, 1, 25, 4)
ON DUPLICATE KEY UPDATE `updated_at` = CURRENT_TIMESTAMP;
