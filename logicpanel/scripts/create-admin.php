<?php
/**
 * Admin User Creation Script
 * Creates admin user from environment variables if not exists
 */

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$_ENV['DB_DATABASE']}",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id FROM lp_users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists.\n";
        exit(0);
    }
    
    // Get admin credentials from environment
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? 'admin@example.com';
    $adminName = $_ENV['ADMIN_NAME'] ?? 'Administrator';
    $adminPassword = $_ENV['ADMIN_PASSWORD'] ?? 'password';
    
    // Generate username from email
    $adminUsername = explode('@', $adminEmail)[0];
    if (strlen($adminUsername) < 3) {
        $adminUsername = 'admin';
    }
    
    // Check if username exists
    $stmt = $pdo->prepare("SELECT id FROM lp_users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    if ($stmt->rowCount() > 0) {
        $adminUsername = 'admin_' . substr(md5(time()), 0, 6);
    }
    
    // Hash password
    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // Insert admin user
    $stmt = $pdo->prepare("
        INSERT INTO lp_users (username, email, password, name, role, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'admin', 1, NOW(), NOW())
    ");
    
    $stmt->execute([$adminUsername, $adminEmail, $hashedPassword, $adminName]);
    
    echo "Admin user created successfully!\n";
    echo "Username: $adminUsername\n";
    echo "Email: $adminEmail\n";
    echo "Password: $adminPassword\n";
    
    // Create default API key for WHMCS
    $apiKey = $_ENV['API_KEY'] ?? 'lp_' . bin2hex(random_bytes(16));
    $apiSecret = $_ENV['API_SECRET'] ?? bin2hex(random_bytes(32));
    
    $stmt = $pdo->prepare("SELECT id FROM lp_api_keys WHERE name = 'WHMCS Integration' LIMIT 1");
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $stmt = $pdo->prepare("
            INSERT INTO lp_api_keys (name, api_key, api_secret, permissions, status, created_at, updated_at)
            VALUES ('WHMCS Integration', ?, ?, ?, 'active', NOW(), NOW())
        ");
        
        $permissions = json_encode(['create', 'suspend', 'unsuspend', 'terminate', 'sso']);
        $stmt->execute([$apiKey, password_hash($apiSecret, PASSWORD_DEFAULT), $permissions]);
        
        echo "\nAPI Key created:\n";
        echo "API Key: $apiKey\n";
        echo "API Secret: $apiSecret\n";
    }
    
    exit(0);
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
