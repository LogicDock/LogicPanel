<?php
/**
 * LogicPanel - CLI Admin Creator
 * This script is used by the installer to set up the initial admin account.
 */

require 'vendor/autoload.php';
use Dotenv\Dotenv;

// Load Env
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Parse CLI Arguments
$options = getopt("", ["user:", "email:", "pass:"]);
$username = $options['user'] ?? 'admin';
$email = $options['email'] ?? 'admin@example.cloud';
$password = $options['pass'] ?? 'logicpanel123';

try {
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $db = $_ENV['DB_DATABASE'] ?? 'logicpanel';
    $user = $_ENV['DB_USERNAME'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    // Connect to MySQL
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure schema exists (basic table check)
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        $schema = file_get_contents(__DIR__ . '/database/schema.sql');
        if ($schema) {
            $pdo->exec($schema);
            echo "Database schema imported.\n";
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
