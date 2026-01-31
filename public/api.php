<?php

declare(strict_types=1);

use DI\Container;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use LogicPanel\Infrastructure\Database\Connection;
use LogicPanel\Application\Services\JwtService;
use LogicPanel\Infrastructure\Docker\DockerService;
use LogicPanel\Infrastructure\Database\DatabaseProvisionerService;
use LogicPanel\Application\Middleware\AuthMiddleware;
use LogicPanel\Application\Middleware\CorsMiddleware;
use LogicPanel\Application\Controllers\AuthController;
use LogicPanel\Application\Controllers\ServiceController;
use LogicPanel\Application\Controllers\DatabaseController;
use LogicPanel\Application\Controllers\FileController;

// Disable direct error display for production-like JSON responses
// But keep them for debugging if needed (via log)
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-API-Reached: true');

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (\Exception $e) {
    // Ignore missing .env
}

// Ensure settings are loaded
$settings = require __DIR__ . '/../config/settings.php';

// Initialize database connection
Connection::init($settings['database']);

// Create Container
$container = new Container();

// Register settings
$container->set('settings', $settings);

// Register services
$container->set(\LogicPanel\Application\Services\TokenBlacklistService::class, function () {
    return new \LogicPanel\Application\Services\TokenBlacklistService();
});

$container->set(JwtService::class, function () use ($settings) {
    return new JwtService($settings['jwt']);
});

$container->set(DockerService::class, function () use ($settings) {
    return new DockerService($settings['docker']);
});

$container->set(DatabaseProvisionerService::class, function () use ($settings) {
    return new DatabaseProvisionerService($settings['db_provisioner']);
});

// Register middleware
$container->set(AuthMiddleware::class, function ($container) {
    return new AuthMiddleware(
        $container->get(JwtService::class),
        $container->get(\LogicPanel\Application\Services\TokenBlacklistService::class)
    );
});

$container->set(CorsMiddleware::class, function () {
    return new CorsMiddleware();
});

// Register controllers
$container->set(AuthController::class, function ($container) {
    return new AuthController(
        $container->get(JwtService::class),
        $container->get(\LogicPanel\Application\Services\TokenBlacklistService::class)
    );
});

$container->set(ServiceController::class, function ($container) {
    return new ServiceController($container->get(DockerService::class));
});

$container->set(DatabaseController::class, function ($container) {
    return new DatabaseController($container->get(DatabaseProvisionerService::class));
});

$container->set(FileController::class, function () use ($settings) {
    $config = $settings['file_manager'];
    $config['user_apps_path'] = $settings['docker']['user_apps_path'];
    return new FileController($config);
});

// Master Panel Services
$container->set(\LogicPanel\Application\Services\SystemBridgeService::class, function () {
    return new \LogicPanel\Application\Services\SystemBridgeService();
});

// Master Panel Controllers
$container->set(\LogicPanel\Application\Controllers\Master\AccountController::class, function ($container) {
    return new \LogicPanel\Application\Controllers\Master\AccountController(
        $container->get(\LogicPanel\Application\Services\SystemBridgeService::class),
        $container->get(DockerService::class),
        $container->get(JwtService::class)
    );
});

$container->set(\LogicPanel\Application\Controllers\Master\ServiceController::class, function ($container) {
    return new \LogicPanel\Application\Controllers\Master\ServiceController(
        $container->get(\LogicPanel\Application\Services\SystemBridgeService::class),
        $container->get(DockerService::class)
    );
});

$container->set(\LogicPanel\Application\Controllers\Master\SystemController::class, function ($container) {
    return new \LogicPanel\Application\Controllers\Master\SystemController(
        $container->get(\LogicPanel\Application\Services\SystemBridgeService::class)
    );
});

$container->set(\LogicPanel\Application\Controllers\Master\DomainController::class, function () {
    return new \LogicPanel\Application\Controllers\Master\DomainController();
});

$container->set(LogicPanel\Application\Controllers\CronController::class, function ($container) {
    return new LogicPanel\Application\Controllers\CronController($container->get(DockerService::class));
});

// Set container to create App with on AppFactory
AppFactory::setContainer($container);
$app = AppFactory::create();

// Set base path dynamically to handle both XAMPP and Docker
$scriptPath = dirname(dirname($_SERVER['SCRIPT_NAME'])); // Gets parent of /public
$basePath = ($scriptPath === '/' || $scriptPath === '\\') ? '' : $scriptPath;
$basePath = rtrim($basePath, '/');
$app->setBasePath($basePath . '/public/api');

// Register middleware
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

// Error middleware
$app->addErrorMiddleware(
    (bool) ($settings['app']['debug'] ?? false),
    true,
    true
);

// Register routes based on Port / Role
$serverPort = $_SERVER['SERVER_PORT'];
$fwPort = $_SERVER['HTTP_X_FORWARDED_PORT'] ?? null;
$hostPort = null;
if (isset($_SERVER['HTTP_HOST'])) {
    $parsed = parse_url('http://' . $_SERVER['HTTP_HOST']);
    if (is_array($parsed))
        $hostPort = $parsed['port'] ?? null;
}
$effectivePort = $hostPort ?: ($fwPort ?: $serverPort);

try {
    // LogicPanel Dual-Port Routing
    $masterPort = (int) ($_ENV['MASTER_PORT'] ?? 967);
    if ((int) $effectivePort === $masterPort || getenv('APP_MODE') === 'master') {
        // Master Panel Routes
        $routes = require __DIR__ . '/../src/routes_master.php';
    } else {
        // User Panel Routes
        $routes = require __DIR__ . '/../src/routes_user.php';
    }

    $routes($app);

    // Force JSON response for all API calls
    header('Content-Type: application/json');

    // Run app
    $app->run();
} catch (\Throwable $e) {
    if (ob_get_level())
        ob_end_clean();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'error' => 'API Fatal Error',
        'message' => $e->getMessage(),
        'trace' => $_ENV['APP_DEBUG'] === 'true' ? $e->getTraceAsString() : null
    ]);
    exit;
}
