<?php
/**
 * LogicPanel - CLI Admin Creator (Standalone Version)
 * This script is dependency-free to ensure it runs even if vendor is missing.
 */

// Security: Prevent web access
if (php_sapi_name() !== 'cli') {
    header('HTTP/1.1 403 Forbidden');
    die("Access Denied: This script can only be run via CLI.");
}

// Parse CLI Arguments
$options = getopt("", ["user:", "email:", "pass:"]);
$username = $options['user'] ?? 'admin';
$email = $options['email'] ?? 'admin@example.cloud';
$password = $options['pass'] ?? 'logicpanel123';

try {
    // Prefer getenv() as it works regardless of variables_order in php.ini
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $db = getenv('DB_DATABASE') ?: 'logicpanel';
    $user = getenv('DB_USERNAME') ?: 'root';
    $pass = getenv('DB_PASSWORD') ?: '';

    // Connect to MySQL
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Ensure users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        // EMBEDDED SCHEMA (Fallback for when file download fails)
        $embeddedSchema = <<<'SQL'
-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'reseller', 'user') DEFAULT 'user',
    status ENUM('active', 'suspended', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Services Table (User Applications)
CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    domain VARCHAR(255),
    type ENUM('nodejs', 'python') NOT NULL,
    status ENUM('creating', 'deploying', 'running', 'stopped', 'error') DEFAULT 'creating',
    container_id VARCHAR(64),
    port INT UNSIGNED,
    env_vars TEXT,
    cpu_limit DECIMAL(3,2) DEFAULT 0.50,
    memory_limit VARCHAR(10) DEFAULT '512M',
    disk_limit VARCHAR(10) DEFAULT '1G',
    runtime_version VARCHAR(50) DEFAULT '',
    install_command VARCHAR(255) DEFAULT '',
    build_command VARCHAR(255) DEFAULT '',
    start_command VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_container_id (container_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Domains Table
CREATE TABLE IF NOT EXISTS domains (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    type ENUM('primary', 'addon', 'subdomain', 'alias') DEFAULT 'primary',
    path VARCHAR(255) DEFAULT '/public_html',
    parent_id INT UNSIGNED DEFAULT NULL,
    status ENUM('active', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES domains(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Databases Table
CREATE TABLE IF NOT EXISTS `databases` (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    service_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    db_type ENUM('mysql', 'postgresql', 'mongodb') NOT NULL,
    db_name VARCHAR(64) NOT NULL,
    db_user VARCHAR(64) NOT NULL,
    db_password TEXT NOT NULL,
    db_host VARCHAR(255) NOT NULL,
    db_port INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_db_name (db_type, db_name),
    INDEX idx_service_id (service_id),
    INDEX idx_user_id (user_id),
    INDEX idx_db_type (db_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Packages Table
CREATE TABLE IF NOT EXISTS packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    cpu_limit DECIMAL(3,2) DEFAULT 0.50,
    memory_limit VARCHAR(10) DEFAULT '512M',
    disk_limit VARCHAR(10) DEFAULT '1G',
    bandwidth_limit VARCHAR(10) DEFAULT '10G',
    email_limit INT UNSIGNED DEFAULT 10,
    ftp_limit INT UNSIGNED DEFAULT 1,
    db_limit INT UNSIGNED DEFAULT 3,
    max_subdomains INT UNSIGNED DEFAULT 5,
    max_parked_domains INT UNSIGNED DEFAULT 0,
    max_addon_domains INT UNSIGNED DEFAULT 0,
    max_services INT UNSIGNED DEFAULT 1,
    max_databases INT UNSIGNED DEFAULT 3,
    price DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Packages Table
CREATE TABLE IF NOT EXISTS user_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_package_id (package_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API Keys Table
CREATE TABLE IF NOT EXISTS api_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    key_hash VARCHAR(255) NOT NULL,
    permissions JSON,
    last_used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_key_hash (key_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    resource_type VARCHAR(50),
    resource_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    metadata JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource (resource_type, resource_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table
CREATE TABLE IF NOT EXISTS sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Admin User (Placeholder - can be removed if not needed since we create one below)
-- INSERT INTO users (username, email, password_hash, role, status) VALUES
-- ('setup_temp', 'temp@local', 'temp', 'admin', 'active');
-- DELETE FROM users WHERE username = 'setup_temp';
SQL;

        // Try to load from file first, fallback to embedded
        $possiblePaths = [
            __DIR__ . '/database/schema.sql',
            '/var/www/html/database/schema.sql',
            __DIR__ . '/schema.sql'
        ];

        $schemaToRun = $embeddedSchema; // Default to embedded

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $fileContent = file_get_contents($path);
                if ($fileContent && strlen($fileContent) > 100) { // Simple validity check
                    $schemaToRun = $fileContent;
                    echo "Loaded schema from file: $path\n";
                    break;
                }
            }
        }

        if ($schemaToRun === $embeddedSchema) {
            echo "Using embedded schema fallback.\n";
        }

        try {
            $pdo->exec($schemaToRun);
            echo "Database schema imported successfully.\n";
        } catch (PDOException $e) {
            throw new Exception("Schema Import Failed: " . $e->getMessage());
        }
    }

    // Create or Update Admin
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$username, $email, $passwordHash]);
        echo "Admin user created: $email\n";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$username, $passwordHash, $existing['id']]);
        echo "Admin user updated: $email\n";
    }

} catch (\Exception $e) {
    echo "Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
