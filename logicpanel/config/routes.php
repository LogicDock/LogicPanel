<?php
/**
 * LogicPanel Routes
 */

declare(strict_types=1);

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Controllers\AuthController;
use LogicPanel\Controllers\DashboardController;
use LogicPanel\Controllers\ServiceController;
use LogicPanel\Controllers\DomainController;
use LogicPanel\Controllers\DatabaseController;
use LogicPanel\Controllers\BackupController;
use LogicPanel\Controllers\TerminalController;
use LogicPanel\Controllers\FileController;
use LogicPanel\Controllers\GitController;
use LogicPanel\Controllers\ApiController;
use LogicPanel\Middleware\AuthMiddleware;
use LogicPanel\Middleware\ApiAuthMiddleware;

/** @var App $app */

// ============================================
// Public Routes (No Authentication)
// ============================================

// Login Page
$app->get('/login', [AuthController::class, 'showLogin'])->setName('login');
$app->post('/login', [AuthController::class, 'processLogin']);
$app->get('/logout', [AuthController::class, 'logout'])->setName('logout');

// SSO Login (from WHMCS)
$app->get('/sso/{token}', [AuthController::class, 'ssoLogin'])->setName('sso');

// Setup/Install (only if not installed)
$app->get('/setup', [AuthController::class, 'showSetup'])->setName('setup');
$app->post('/setup', [AuthController::class, 'processSetup']);

// ============================================
// Authenticated Routes (User Panel)
// ============================================

$app->group('', function (RouteCollectorProxy $group) {

    // Dashboard
    $group->get('/', [DashboardController::class, 'index'])->setName('dashboard');
    $group->get('/dashboard', [DashboardController::class, 'index']);

    // Services/Containers Management
    $group->group('/services', function (RouteCollectorProxy $services) {
        $services->get('', [ServiceController::class, 'index'])->setName('services');
        $services->get('/{id}', [ServiceController::class, 'show'])->setName('service.show');
        $services->post('/{id}/start', [ServiceController::class, 'start'])->setName('service.start');
        $services->post('/{id}/stop', [ServiceController::class, 'stop'])->setName('service.stop');
        $services->post('/{id}/restart', [ServiceController::class, 'restart'])->setName('service.restart');
        $services->post('/{id}/rebuild', [ServiceController::class, 'rebuild'])->setName('service.rebuild');
        $services->get('/{id}/logs', [ServiceController::class, 'logs'])->setName('service.logs');
        $services->get('/{id}/stats', [ServiceController::class, 'stats'])->setName('service.stats');
        $services->get('/{id}/env', [ServiceController::class, 'envEditor'])->setName('service.env');
        $services->post('/{id}/env', [ServiceController::class, 'saveEnv']);
    });

    // Domain Management
    $group->group('/domains', function (RouteCollectorProxy $domains) {
        $domains->get('', [DomainController::class, 'index'])->setName('domains');
        $domains->get('/{serviceId}', [DomainController::class, 'show'])->setName('domains.show');
        $domains->post('/{serviceId}/add', [DomainController::class, 'add'])->setName('domain.add');
        $domains->post('/{serviceId}/remove', [DomainController::class, 'remove'])->setName('domain.remove');
        $domains->post('/{serviceId}/primary', [DomainController::class, 'setPrimary'])->setName('domain.primary');
    });

    // Database Management
    $group->group('/databases', function (RouteCollectorProxy $db) {
        $db->get('', [DatabaseController::class, 'index'])->setName('databases');
        $db->get('/{serviceId}', [DatabaseController::class, 'show'])->setName('databases.show');
        $db->post('/create', [DatabaseController::class, 'create'])->setName('database.create');
        $db->post('/add-user', [DatabaseController::class, 'addUser'])->setName('database.addUser');
        $db->post('/{id}/delete', [DatabaseController::class, 'delete'])->setName('database.delete');
        $db->post('/{id}/reset-password', [DatabaseController::class, 'resetPassword'])->setName('database.resetPassword');
        $db->get('/{id}/adminer', [DatabaseController::class, 'adminer'])->setName('database.adminer');
    });

    // Backup Management
    $group->group('/backups', function (RouteCollectorProxy $backup) {
        $backup->get('', [BackupController::class, 'index'])->setName('backups');
        $backup->get('/{serviceId}', [BackupController::class, 'show'])->setName('backups.show');
        $backup->post('/{serviceId}/create', [BackupController::class, 'create'])->setName('backup.create');
        $backup->post('/{id}/restore', [BackupController::class, 'restore'])->setName('backup.restore');
        $backup->post('/{id}/delete', [BackupController::class, 'delete'])->setName('backup.delete');
        $backup->get('/{id}/download', [BackupController::class, 'download'])->setName('backup.download');
    });

    // Terminal (WebSocket endpoint will be separate)
    $group->group('/terminal', function (RouteCollectorProxy $term) {
        $term->get('/{serviceId}', [TerminalController::class, 'index'])->setName('terminal');
        $term->post('/{serviceId}/exec', [TerminalController::class, 'exec'])->setName('terminal.exec');
    });

    // File Manager
    $group->group('/files', function (RouteCollectorProxy $files) {
        $files->get('/{serviceId}', [FileController::class, 'index'])->setName('files');
        $files->get('/{serviceId}/browse', [FileController::class, 'browse'])->setName('files.browse');
        $files->get('/{serviceId}/download', [FileController::class, 'download'])->setName('files.download');
        $files->post('/{serviceId}/upload', [FileController::class, 'upload'])->setName('files.upload');
        $files->post('/{serviceId}/delete', [FileController::class, 'delete'])->setName('files.delete');
        $files->post('/{serviceId}/edit', [FileController::class, 'edit'])->setName('files.edit');
        $files->post('/{serviceId}/mkdir', [FileController::class, 'mkdir'])->setName('files.mkdir');
    });

    // Git Deployment
    $group->group('/git', function (RouteCollectorProxy $git) {
        $git->get('/{serviceId}', [GitController::class, 'index'])->setName('git');
        $git->post('/{serviceId}/deploy', [GitController::class, 'deploy'])->setName('git.deploy');
        $git->post('/{serviceId}/config', [GitController::class, 'saveConfig'])->setName('git.config');
        $git->get('/{serviceId}/history', [GitController::class, 'history'])->setName('git.history');
    });

    // User Settings
    $group->get('/settings', [DashboardController::class, 'settings'])->setName('settings');
    $group->post('/settings', [DashboardController::class, 'updateSettings']);
    $group->post('/settings/theme', [DashboardController::class, 'updateTheme'])->setName('settings.theme');

    // Two-Factor Authentication
    $group->post('/settings/2fa/setup', [DashboardController::class, 'setup2FA'])->setName('settings.2fa.setup');
    $group->post('/settings/2fa/verify', [DashboardController::class, 'verify2FA'])->setName('settings.2fa.verify');
    $group->post('/settings/2fa/disable', [DashboardController::class, 'disable2FA'])->setName('settings.2fa.disable');

})->add(new AuthMiddleware($container));

// ============================================
// API Routes (For WHMCS Integration)
// ============================================

$app->group('/api/v1', function (RouteCollectorProxy $api) {

    // Account Management (WHMCS calls these)
    $api->post('/account/create', [ApiController::class, 'createAccount']);
    $api->post('/account/suspend', [ApiController::class, 'suspendAccount']);
    $api->post('/account/unsuspend', [ApiController::class, 'unsuspendAccount']);
    $api->post('/account/terminate', [ApiController::class, 'terminateAccount']);
    $api->post('/account/password', [ApiController::class, 'changePassword']);

    // SSO Token Generation
    $api->post('/sso/generate', [ApiController::class, 'generateSSO']);

    // Health Check
    $api->get('/health', [ApiController::class, 'health']);

    // Service Info
    $api->get('/service/{id}', [ApiController::class, 'serviceInfo']);
    $api->get('/service/{id}/stats', [ApiController::class, 'serviceStats']);

})->add(new ApiAuthMiddleware($container));

// Public API (no auth required - for WHMCS to fetch packages)
$app->get('/api/packages', [ApiController::class, 'listPackages']);

// ============================================
// Reseller Routes (Admin + Reseller only)
// ============================================


use LogicPanel\Controllers\ResellerController;
use LogicPanel\Middleware\ResellerMiddleware;

$app->group('/reseller', function (RouteCollectorProxy $reseller) {
    // Reseller Dashboard
    $reseller->get('', [ResellerController::class, 'dashboard'])->setName('reseller');
    $reseller->get('/dashboard', [ResellerController::class, 'dashboard']);

    // User Management
    $reseller->get('/users', [ResellerController::class, 'listUsers'])->setName('reseller.users');
    $reseller->get('/users/create', [ResellerController::class, 'createUserForm'])->setName('reseller.users.create');
    $reseller->post('/users/create', [ResellerController::class, 'createUser']);
    $reseller->get('/users/{id}/edit', [ResellerController::class, 'editUserForm'])->setName('reseller.users.edit');
    $reseller->post('/users/{id}/update', [ResellerController::class, 'updateUser']);
    $reseller->post('/users/{id}/delete', [ResellerController::class, 'deleteUser']);
    $reseller->post('/users/{id}/toggle', [ResellerController::class, 'toggleUserStatus']);

    // Package Management (Reseller's own packages for their users)
    $reseller->get('/packages', [ResellerController::class, 'listPackages'])->setName('reseller.packages');
    $reseller->get('/packages/create', [ResellerController::class, 'createPackageForm'])->setName('reseller.packages.create');
    $reseller->post('/packages/create', [ResellerController::class, 'createPackage']);
})->add(new ResellerMiddleware())->add(new AuthMiddleware($container));

// ============================================
// Admin Routes
// ============================================

$app->group('/admin', function (RouteCollectorProxy $admin) {
    $admin->get('', [DashboardController::class, 'adminDashboard'])->setName('admin');
    $admin->get('/users', [DashboardController::class, 'adminUsers'])->setName('admin.users');
    $admin->post('/users/create', [DashboardController::class, 'createUser']);
    $admin->get('/services', [DashboardController::class, 'adminServices'])->setName('admin.services');
    $admin->get('/settings', [DashboardController::class, 'adminSettings'])->setName('admin.settings');
    $admin->post('/settings', [DashboardController::class, 'saveAdminSettings']);

    // Package Management
    $admin->get('/packages', [DashboardController::class, 'adminPackages'])->setName('admin.packages');
    $admin->post('/packages/create', [DashboardController::class, 'createPackage']);
    $admin->post('/packages/{id}/update', [DashboardController::class, 'updatePackage']);
    $admin->post('/packages/{id}/delete', [DashboardController::class, 'deletePackage']);

    // Reseller Package Management (Admin creates packages for resellers)
    $admin->get('/reseller-packages', [DashboardController::class, 'adminResellerPackages'])->setName('admin.reseller_packages');
    $admin->post('/reseller-packages/create', [DashboardController::class, 'createResellerPackage']);
    $admin->post('/reseller-packages/{id}/update', [DashboardController::class, 'updateResellerPackage']);
    $admin->post('/reseller-packages/{id}/delete', [DashboardController::class, 'deleteResellerPackage']);

    // API Keys Management
    $admin->get('/api-keys', [DashboardController::class, 'adminApiKeys'])->setName('admin.apikeys');
    $admin->post('/api-keys/create', [DashboardController::class, 'createApiKey']);
    $admin->post('/api-keys/{id}/toggle', [DashboardController::class, 'toggleApiKey']);
    $admin->post('/api-keys/{id}/delete', [DashboardController::class, 'deleteApiKey']);

    // Service Actions (Admin can suspend/terminate any service)
    $admin->post('/services/create', [ServiceController::class, 'adminCreateService'])->setName('admin.service.create');
    $admin->post('/services/{id}/suspend', [ServiceController::class, 'adminSuspend'])->setName('admin.service.suspend');
    $admin->post('/services/{id}/unsuspend', [ServiceController::class, 'adminUnsuspend'])->setName('admin.service.unsuspend');
    $admin->post('/services/{id}/terminate', [ServiceController::class, 'adminTerminate'])->setName('admin.service.terminate');
})->add(new AuthMiddleware($container, true)); // true = admin only

