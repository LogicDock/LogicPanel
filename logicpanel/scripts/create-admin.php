<?php
require_once __DIR__ . '/../vendor/autoload.php';

try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
}

$envFile = __DIR__ . '/../.env';
$envVars = [];
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            list($k, $v) = explode('=', $line, 2);
            $envVars[trim($k)] = trim($v, '"\'');
        }
    }
}

function env($k, $d, $e)
{
    if (isset($_ENV[$k]) && $_ENV[$k] !== '')
        return $_ENV[$k];
    $v = getenv($k);
    if ($v !== false && $v !== '')
        return $v;
    return $e[$k] ?? $d;
}

try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST', 'logicpanel-db', $envVars) .
        ";dbname=" . env('DB_DATABASE', 'logicpanel', $envVars),
        env('DB_USERNAME', 'logicpanel', $envVars),
        env('DB_PASSWORD', 'password', $envVars),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $username = env('ADMIN_USERNAME', 'admin', $envVars);
    $email = env('ADMIN_EMAIL', 'admin@localhost', $envVars);
    $password = env('ADMIN_PASSWORD', 'password', $envVars);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->query("SELECT id FROM lp_users WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        $pdo->prepare("UPDATE lp_users SET username=?, email=?, password=?, name='Administrator' WHERE id=?")
            ->execute([$username, $email, $hash, $admin['id']]);
        echo "Admin updated: $username\n";
    } else {
        $pdo->prepare("INSERT INTO lp_users (username, email, password, name, role, is_active, created_at, updated_at) VALUES (?, ?, ?, 'Administrator', 'admin', 1, NOW(), NOW())")
            ->execute([$username, $email, $hash]);
        echo "Admin created: $username\n";
    }

    $apiKey = env('API_KEY', 'lp_' . bin2hex(random_bytes(16)), $envVars);
    $apiSecret = env('API_SECRET', bin2hex(random_bytes(32)), $envVars);

    $stmt = $pdo->query("SELECT id FROM lp_api_keys WHERE name = 'WHMCS Integration' LIMIT 1");
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($key) {
        $pdo->prepare("UPDATE lp_api_keys SET api_key=?, api_secret=? WHERE id=?")
            ->execute([$apiKey, $apiSecret, $key['id']]);  // Plain text secret
    } else {
        $pdo->prepare("INSERT INTO lp_api_keys (name, api_key, api_secret, permissions, is_active, created_at, updated_at) VALUES ('WHMCS Integration', ?, ?, ?, 1, NOW(), NOW())")
            ->execute([$apiKey, $apiSecret, json_encode(['create', 'suspend', 'unsuspend', 'terminate', 'sso'])]);
    }

    echo "API Key: $apiKey\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
