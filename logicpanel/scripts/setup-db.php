<?php
/**
 * Database Setup Script
 * Creates tables if they don't exist
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

    // Check if tables exist
    $result = $pdo->query("SHOW TABLES LIKE 'lp_users'");
    if ($result->rowCount() > 0) {
        echo "Tables already exist.\n";
        exit(0);
    }

    // Read and execute schema
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (!file_exists($schemaFile)) {
        echo "Schema file not found!\n";
        exit(1);
    }

    $schema = file_get_contents($schemaFile);

    // Remove comments and empty lines, then split by semicolon
    $schema = preg_replace('/--.*$/m', '', $schema);  // Remove single-line comments
    $schema = preg_replace('/\/\*.*?\*\//s', '', $schema);  // Remove multi-line comments

    $statements = array_filter(array_map('trim', explode(';', $schema)));

    $successCount = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Ignore errors for existing tables/duplicates
                if (
                    strpos($e->getMessage(), 'already exists') === false &&
                    strpos($e->getMessage(), 'Duplicate') === false
                ) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }

    echo "Database schema created successfully! ($successCount statements executed)\n";
    exit(0);

} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
