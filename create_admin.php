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
        $schemaPath = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaPath)) {
            $schema = file_get_contents($schemaPath);
            if ($schema) {
                $pdo->exec($schema);
                echo "Database schema imported.\n";
            }
        } else {
            throw new Exception("Schema file not found at $schemaPath");
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
