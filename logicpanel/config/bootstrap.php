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
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;

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

// Check debug mode properly
$isDebug = filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

// Add Error Middleware with custom handler for API
$errorMiddleware = $app->addErrorMiddleware($isDebug, true, true);

// Custom error handler for JSON responses (API routes)
$errorMiddleware->setDefaultErrorHandler(function (ServerRequestInterface $request, \Throwable $exception, bool $displayErrorDetails, bool $logErrors, bool $logErrorDetails) use ($app) {
    $response = $app->getResponseFactory()->createResponse();
    $uri = $request->getUri()->getPath();

    // For API routes, always return JSON
    if (strpos($uri, '/api/') !== false) {
        $error = [
            'success' => false,
            'error' => $exception->getMessage(),
        ];

        if ($displayErrorDetails) {
            $error['trace'] = $exception->getTraceAsString();
            $error['file'] = $exception->getFile();
            $error['line'] = $exception->getLine();
        }

        $response->getBody()->write(json_encode($error));
        $statusCode = $exception instanceof HttpException ? $exception->getCode() : 500;
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode ?: 500);
    }

    // For web routes, show HTML error
    $response->getBody()->write('<html><body>');
    $response->getBody()->write('<h1>Error</h1>');
    $response->getBody()->write('<p>' . htmlspecialchars($exception->getMessage()) . '</p>');
    if ($displayErrorDetails) {
        $response->getBody()->write('<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>');
    }
    $response->getBody()->write('</body></html>');
    return $response->withStatus(500);
});

// Add Body Parsing Middleware
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Initialize Database
$container->get('db');

// Auto-run pending database migrations
try {
    $migrationService = new \LogicPanel\Services\MigrationService();
    $migrationService->runPendingMigrations();
} catch (\Exception $e) {
    // Log but don't fail - allow app to start even if migrations fail
    error_log('Migration error: ' . $e->getMessage());
}

// Load Routes
require BASE_PATH . '/config/routes.php';

// Run App
$app->run();

