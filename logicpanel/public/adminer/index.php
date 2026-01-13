<?php
/**
 * LogicPanel Adminer Proxy
 * Provides authenticated access to Adminer for database management
 */

// Start session for authentication
session_start();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: /logicpanel/public/login');
    exit;
}

// Get database ID from URL
$dbId = $_GET['db'] ?? null;

if (!$dbId) {
    die('Database ID required');
}

// Load environment
require __DIR__ . '/../../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Initialize database connection
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'logicpanel',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => 'lp_',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

use Illuminate\Database\Capsule\Manager as DB;

// Get database info
$database = DB::table('databases')
    ->join('services', 'databases.service_id', '=', 'services.id')
    ->where('databases.id', $dbId)
    ->where('services.user_id', $_SESSION['user_id'])
    ->select('databases.*')
    ->first();

if (!$database) {
    die('Database not found or access denied');
}

// Determine Adminer driver and server
$drivers = [
    'mariadb' => 'server',
    'mysql' => 'server',
    'postgresql' => 'pgsql',
    'mongodb' => 'mongo'
];

$driver = $drivers[$database->type] ?? 'server';
$host = $database->container_id ?? 'lp_' . $database->service_id . '_' . $database->type;
$port = $database->type === 'postgresql' ? 5432 : ($database->type === 'mongodb' ? 27017 : 3306);

// Auto-login configuration
$_GET['username'] = $database->db_user;
$_GET['db'] = $database->db_name;

// For Adminer auto-login, we'll use a custom plugin approach
// Store credentials in session for Adminer
$_SESSION['adminer_db'] = [
    'driver' => $driver,
    'server' => $host . ':' . $port,
    'username' => $database->db_user,
    'password' => $database->db_password,
    'database' => $database->db_name
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database -
        <?= htmlspecialchars($database->db_name) ?>
    </title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f6f8;
        }

        .notice {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            line-height: 1.6;
        }

        .credentials {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            text-align: left;
        }

        .credentials p {
            margin: 8px 0;
            font-family: monospace;
        }

        .credentials strong {
            color: #333;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #3C873A;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 15px;
        }

        .btn:hover {
            background: #2D6A2E;
        }

        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="notice">
        <h1>Database:
            <?= htmlspecialchars($database->db_name) ?>
        </h1>
        <p>Use the credentials below to connect via your database client or Adminer.</p>

        <div class="warning">
            ⚠️ Adminer must be installed on the Docker host server to manage remote databases.
        </div>

        <div class="credentials">
            <p><strong>Type:</strong>
                <?= strtoupper($database->type) ?>
            </p>
            <p><strong>Host:</strong>
                <?= htmlspecialchars($host) ?>
            </p>
            <p><strong>Port:</strong>
                <?= $port ?>
            </p>
            <p><strong>Database:</strong>
                <?= htmlspecialchars($database->db_name) ?>
            </p>
            <p><strong>Username:</strong>
                <?= htmlspecialchars($database->db_user) ?>
            </p>
            <p><strong>Password:</strong>
                <?= htmlspecialchars($database->db_password) ?>
            </p>
        </div>

        <p>
            For external access, use Adminer at:<br>
            <code>https://adminer.yourdomain.com</code>
        </p>

        <a href="/logicpanel/public/databases" class="btn">← Back to Databases</a>
    </div>
</body>

</html>