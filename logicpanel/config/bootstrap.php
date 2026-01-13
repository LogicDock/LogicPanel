<?php
/**
 * LogicPanel Bootstrap
 * Initialize Slim App with all dependencies
 */

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use Illuminate\Database\Capsule\Manager as Capsule;

// Create Container
$container = new Container();

// Database setup with Eloquent
$container->set('db', function () {
    $capsule = new Capsule;
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
    return $capsule;
});

// Docker Service
$container->set('docker', function () {
    return new \LogicPanel\Services\DockerService();
});

// Settings
$container->set('settings', function () {
    return [
        'app_name' => $_ENV['APP_NAME'] ?? 'LogicPanel',
        'app_url' => $_ENV['APP_URL'] ?? 'http://localhost/logicpanel',
        'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'jwt_secret' => $_ENV['JWT_SECRET'] ?? 'change-this-secret',
        'jwt_expiry' => (int) ($_ENV['JWT_EXPIRY'] ?? 86400),
    ];
});

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path for subdirectory installation
$app->setBasePath('');

// Add Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Initialize Database
$container->get('db');

// Load Routes
require BASE_PATH . '/config/routes.php';

// Run App
$app->run();
