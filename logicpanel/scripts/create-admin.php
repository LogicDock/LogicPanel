<?php
/**
 * Admin User Creation Script
 * Creates admin user from environment variables if not exists
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load .env file
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    echo "Warning: Could not load .env file\n";
}

// Read directly from .env file as backup
$envFile = __DIR__ . '/../.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $envVars[trim($key)] = trim($value);
        }
    }
}

// Get value from multiple sources
function getEnvValue($key, $default, $envVars)
{
    // 1. Check $_ENV (from dotenv)
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    // 2. Check getenv (from docker/system)
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    // 3. Check parsed env file
    if (isset($envVars[$key]) && $envVars[$key] !== '') {
        return $envVars[$key];
    }
    // 4. Return default
    return $default;
}

try {
    $dbHost = getEnvValue('DB_HOST', 'logicpanel-db', $envVars);
    $dbPort = getEnvValue('DB_PORT', '3306', $envVars);
    $dbName = getEnvValue('DB_DATABASE', 'logicpanel', $envVars);
    $dbUser = getEnvValue('DB_USERNAME', 'logicpanel', $envVars);
    $dbPass = getEnvValue('DB_PASSWORD', 'password', $envVars);

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

    // Get admin credentials
    $adminEmail = getEnvValue('ADMIN_EMAIL', 'admin@localhost', $envVars);
    $adminName = getEnvValue('ADMIN_NAME', 'Administrator', $envVars);
    $adminPassword = getEnvValue('ADMIN_PASSWORD', 'password', $envVars);

    echo "Creating admin with email: $adminEmail\n";

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
    $apiKey = getEnvValue('API_KEY', 'lp_' . bin2hex(random_bytes(16)), $envVars);
    $apiSecret = getEnvValue('API_SECRET', bin2hex(random_bytes(32)), $envVars);

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
