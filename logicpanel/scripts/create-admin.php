<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    echo "Warning: Could not load .env file\n";
}

$envFile = __DIR__ . '/../.env';
$envVars = [];
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $value = trim($value, '"\'');
            $envVars[trim($key)] = $value;
        }
    }
}

function getEnvValue($key, $default, $envVars)
{
    if (isset($_ENV[$key]) && $_ENV[$key] !== '')
        return $_ENV[$key];
    $val = getenv($key);
    if ($val !== false && $val !== '')
        return $val;
    if (isset($envVars[$key]) && $envVars[$key] !== '')
        return $envVars[$key];
    return $default;
}

try {
    $pdo = new PDO(
        "mysql:host=" . getEnvValue('DB_HOST', 'logicpanel-db', $envVars) .
        ";port=" . getEnvValue('DB_PORT', '3306', $envVars) .
        ";dbname=" . getEnvValue('DB_DATABASE', 'logicpanel', $envVars),
        getEnvValue('DB_USERNAME', 'logicpanel', $envVars),
        getEnvValue('DB_PASSWORD', 'password', $envVars),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT id FROM lp_users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo "Admin user already exists.\n";
        exit(0);
    }

    $adminUsername = getEnvValue('ADMIN_USERNAME', 'admin', $envVars);
    $adminEmail = getEnvValue('ADMIN_EMAIL', 'admin@localhost', $envVars);
    $adminPassword = getEnvValue('ADMIN_PASSWORD', 'password', $envVars);

    echo "Creating admin: $adminUsername ($adminEmail)\n";

    $stmt = $pdo->prepare("SELECT id FROM lp_users WHERE username = ?");
    $stmt->execute([$adminUsername]);
    if ($stmt->rowCount() > 0) {
        $adminUsername = 'admin_' . substr(md5(time()), 0, 6);
    }

    $hashedPassword = password_hash($adminPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO lp_users (username, email, password, name, role, is_active, created_at, updated_at)
        VALUES (?, ?, ?, 'Administrator', 'admin', 1, NOW(), NOW())
    ");
    $stmt->execute([$adminUsername, $adminEmail, $hashedPassword]);

    echo "Admin created: $adminUsername / $adminPassword\n";

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
        echo "API Key: $apiKey\n";
    }

    exit(0);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
