<?php
/**
 * Admin User Creation Script
 * Creates admin user from environment variables if not exists
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Try to load .env file, but don't fail if it doesn't exist
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    // .env file might not exist, continue with system env vars
}

// Helper function to get environment variable from multiple sources
function env($key, $default = null)
{
    // Check $_ENV first (from dotenv)
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    // Then check getenv (from docker/system)
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    // Finally return default
    return $default;
}

try {
    $dbHost = env('DB_HOST', 'logicpanel-db');
    $dbPort = env('DB_PORT', '3306');
    $dbName = env('DB_DATABASE', 'logicpanel');
    $dbUser = env('DB_USERNAME', 'logicpanel');
    $dbPass = env('DB_PASSWORD', 'password');

    $pdo = new PDO(
        "mysql:host={$dbHost};port={$dbPort};dbname={$dbName}",
        $dbUser,
        $dbPass,
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
    $adminEmail = env('ADMIN_EMAIL', 'admin@localhost');
    $adminName = env('ADMIN_NAME', 'Administrator');
    $adminPassword = env('ADMIN_PASSWORD', 'password');

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
    $apiKey = env('API_KEY', 'lp_' . bin2hex(random_bytes(16)));
    $apiSecret = env('API_SECRET', bin2hex(random_bytes(32)));

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
