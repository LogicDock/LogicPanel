<?php
/**
 * LogicPanel - API Controller
 * Handles WHMCS integration API endpoints
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\User;
use LogicPanel\Models\Service;
use LogicPanel\Models\Domain;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class ApiController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * Health check endpoint
     */
    public function health(Request $request, Response $response): Response
    {
        // Docker ping causes timeout if socket permissions are not correct (chmod 666 /var/run/docker.sock)
        // Disabling it to ensure panel speed
        $dockerOk = false;
        $dbOk = true;

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbOk = false;
        }

        // Database is required, Docker is optional for WHMCS integration
        return $this->jsonResponse($response, [
            'status' => $dbOk ? 'healthy' : 'degraded',
            'docker' => $dockerOk ? 'connected' : 'disconnected',
            'database' => $dbOk ? 'connected' : 'disconnected',
            'timestamp' => date('c')
        ]);
    }

    /**
     * List available packages (public endpoint for WHMCS)
     */
    public function listPackages(Request $request, Response $response): Response
    {
        $packages = \LogicPanel\Models\Package::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $formattedPackages = [];
        foreach ($packages as $package) {
            $formattedPackages[] = $package->toWhmcsFormat();
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'packages' => $formattedPackages
        ]);
    }

    /**
     * Create account (called by WHMCS on order activation)
     */
    public function createAccount(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Required fields
        $required = ['whmcs_user_id', 'whmcs_service_id', 'email', 'domain'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => "Missing required field: {$field}"
                ], 400);
            }
        }

        // Find or create user
        $user = User::where('whmcs_user_id', $data['whmcs_user_id'])->first();

        if (!$user) {
            // Create new user
            $user = new User();
            $user->whmcs_user_id = $data['whmcs_user_id'];
            $user->email = $data['email'];
            $user->username = $data['username'] ?? 'user_' . $data['whmcs_user_id'];
            $user->password = bin2hex(random_bytes(16));
            $user->name = $data['name'] ?? $data['username'] ?? 'User';
            $user->role = 'user';
            $user->is_active = true;
            $user->save();
        }

        // Check if service already exists
        $existingService = Service::where('whmcs_service_id', $data['whmcs_service_id'])->first();

        if ($existingService) {
            // If service exists but is in error state, allow retry
            if ($existingService->status === 'error') {
                // Delete the old failed service and continue creating new one
                Domain::where('service_id', $existingService->id)->delete();
                $existingService->delete();
            } else {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Service already exists',
                    'service_id' => $existingService->id
                ], 409);
            }
        }

        // Lookup package by name (WHMCS sends package name like 'starter', 'pro', etc.)
        $packageName = $data['package'] ?? 'starter';
        $package = \LogicPanel\Models\Package::where('name', $packageName)->first();

        if (!$package) {
            // Fallback to first active package
            $package = \LogicPanel\Models\Package::where('is_active', true)->first();
        }

        // Generate service name
        $serviceName = $this->generateServiceName($data['domain']);

        // Create service record
        $service = new Service();
        $service->user_id = $user->id;
        $service->package_id = $package ? $package->id : null;
        $service->name = $serviceName;
        $service->whmcs_service_id = $data['whmcs_service_id'];
        $service->node_version = $data['node_version'] ?? '20';
        $service->port = (int) ($data['port'] ?? 3000);
        $service->github_repo = $data['github_repo'] ?? null;
        $service->github_branch = $data['github_branch'] ?? 'main';
        $service->install_cmd = $data['install_cmd'] ?? 'npm install';
        $service->build_cmd = $data['build_cmd'] ?? '';
        $service->start_cmd = $data['start_cmd'] ?? 'npm start';
        $service->plan = $package ? $package->name : 'basic';
        $service->status = 'pending'; // Start as pending, will be 'running' after container creation
        $service->save();

        // Create domain record
        $domain = new Domain();
        $domain->service_id = $service->id;
        $domain->domain = strtolower($data['domain']);
        $domain->is_primary = true;
        $domain->ssl_enabled = true;
        $domain->save();

        // Try to create Docker container (may fail if Docker socket not accessible)
        try {
            $containerResult = $this->docker->createNodeJsApp([
                'name' => $serviceName,
                'domain' => $data['domain'],
                'node_version' => $service->node_version,
                'port' => $service->port,
                'env' => $this->buildEnvVars($service, $data)
            ]);

            if ($containerResult) {
                $service->container_id = $containerResult['Id'];
                $service->container_name = "lp_{$serviceName}";
                $service->status = 'running';
                $service->save();
            }
        } catch (\Exception $e) {
            // Container creation failed, but service record is created
            // Service remains in 'pending' state - can be provisioned manually later
            error_log("Docker container creation failed: " . $e->getMessage());
        }

        $this->logActivity($user->id, $service->id, 'service_create', 'Service created via WHMCS');

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Account created successfully',
            'service_id' => $service->id,
            'container_id' => $service->container_id ?? null,
            'domain' => $data['domain'],
            'package' => $package ? $package->display_name : 'Basic'
        ]);
    }

    /**
     * Suspend account
     */
    public function suspendAccount(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $service = $this->findService($data);

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        // Stop container
        if ($service->container_id) {
            $this->docker->stopContainer($service->container_id);
        }

        $service->status = 'suspended';
        $service->suspended_at = date('Y-m-d H:i:s');
        $service->save();

        $this->logActivity($service->user_id, $service->id, 'service_suspend', 'Service suspended via WHMCS');

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Account suspended'
        ]);
    }

    /**
     * Unsuspend account
     */
    public function unsuspendAccount(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $service = $this->findService($data);

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        // Start container
        if ($service->container_id) {
            $this->docker->startContainer($service->container_id);
        }

        $service->status = 'running';
        $service->suspended_at = null;
        $service->save();

        $this->logActivity($service->user_id, $service->id, 'service_unsuspend', 'Service unsuspended via WHMCS');

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Account unsuspended'
        ]);
    }

    /**
     * Terminate account
     */
    public function terminateAccount(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $service = $this->findService($data);

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        // Remove container and volumes
        if ($service->container_id) {
            $this->docker->stopContainer($service->container_id);
            $this->docker->removeContainer($service->container_id, true, true);
        }

        // Remove associated database containers
        foreach ($service->databases as $db) {
            if ($db->container_id) {
                $this->docker->stopContainer($db->container_id);
                $this->docker->removeContainer($db->container_id, true, true);
            }
            $db->delete();
        }

        // Remove volume
        $volumeName = "lp_{$service->name}_data";
        $this->docker->removeVolume($volumeName, true);

        $this->logActivity($service->user_id, $service->id, 'service_terminate', 'Service terminated via WHMCS');

        // Delete domains
        Domain::where('service_id', $service->id)->delete();

        // Mark as terminated
        $service->status = 'terminated';
        $service->container_id = null;
        $service->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Account terminated'
        ]);
    }

    /**
     * Change password (regenerate SSO for WHMCS user)
     */
    public function changePassword(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        if (empty($data['whmcs_user_id']) && empty($data['email'])) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'User identifier required'], 400);
        }

        $user = User::where('whmcs_user_id', $data['whmcs_user_id'] ?? 0)
            ->orWhere('email', $data['email'] ?? '')
            ->first();

        if (!$user) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'User not found'], 404);
        }

        // Generate new random password (user uses SSO anyway)
        $newPassword = bin2hex(random_bytes(16));
        $user->password = $newPassword;
        $user->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Password changed'
        ]);
    }

    /**
     * Generate SSO token
     */
    public function generateSSO(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        // Find user by WHMCS user ID or service ID
        $user = null;

        if (!empty($data['whmcs_user_id'])) {
            $user = User::where('whmcs_user_id', $data['whmcs_user_id'])->first();
        } elseif (!empty($data['whmcs_service_id'])) {
            $service = Service::where('whmcs_service_id', $data['whmcs_service_id'])->first();
            if ($service) {
                $user = $service->user;
            }
        }

        if (!$user) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'User not found'], 404);
        }

        // Generate token
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token
        DB::table('sso_tokens')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $baseUrl = $_ENV['APP_URL'] ?? 'https://logicpanel.logicdock.cloud';
        $ssoUrl = "{$baseUrl}/sso/{$token}";

        return $this->jsonResponse($response, [
            'success' => true,
            'sso_url' => $ssoUrl,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);
    }

    /**
     * Get service info
     */
    public function serviceInfo(Request $request, Response $response, array $args): Response
    {
        $serviceId = $args['id'];

        // Find by ID or WHMCS service ID
        $service = Service::where('id', $serviceId)
            ->orWhere('whmcs_service_id', $serviceId)
            ->with(['primaryDomain', 'databases'])
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        // Get container status
        $containerStatus = null;
        if ($service->container_id) {
            $info = $this->docker->inspectContainer($service->container_id);
            if ($info) {
                $containerStatus = [
                    'running' => $info['State']['Running'] ?? false,
                    'status' => $info['State']['Status'] ?? 'unknown',
                    'started_at' => $info['State']['StartedAt'] ?? null
                ];
            }
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'status' => $service->status,
                'domain' => $service->primaryDomain?->domain,
                'node_version' => $service->node_version,
                'container' => $containerStatus,
                'databases' => $service->databases->count(),
                'created_at' => $service->created_at
            ]
        ]);
    }

    /**
     * Get service stats
     */
    public function serviceStats(Request $request, Response $response, array $args): Response
    {
        $serviceId = $args['id'];

        $service = Service::where('id', $serviceId)
            ->orWhere('whmcs_service_id', $serviceId)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        $stats = $this->docker->getContainerStats($service->container_id);

        if (!$stats) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Could not get stats'], 500);
        }

        // Calculate percentages
        $cpuDelta = ($stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0) -
            ($stats['precpu_stats']['cpu_usage']['total_usage'] ?? 0);
        $systemDelta = ($stats['cpu_stats']['system_cpu_usage'] ?? 0) -
            ($stats['precpu_stats']['system_cpu_usage'] ?? 0);
        $cpuCount = $stats['cpu_stats']['online_cpus'] ?? 1;
        $cpuPercent = ($systemDelta > 0 && $cpuDelta > 0)
            ? ($cpuDelta / $systemDelta) * $cpuCount * 100
            : 0;

        $memoryUsage = $stats['memory_stats']['usage'] ?? 0;
        $memoryLimit = $stats['memory_stats']['limit'] ?? 0;
        $memoryPercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

        return $this->jsonResponse($response, [
            'success' => true,
            'stats' => [
                'cpu_percent' => round($cpuPercent, 2),
                'memory_used' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'memory_percent' => round($memoryPercent, 2)
            ]
        ]);
    }

    /**
     * Find service by various identifiers
     */
    private function findService(array $data): ?Service
    {
        if (!empty($data['service_id'])) {
            return Service::find($data['service_id']);
        }
        if (!empty($data['whmcs_service_id'])) {
            return Service::where('whmcs_service_id', $data['whmcs_service_id'])->first();
        }
        if (!empty($data['container_id'])) {
            return Service::where('container_id', $data['container_id'])->first();
        }
        return null;
    }

    /**
     * Generate unique service name
     */
    private function generateServiceName(string $domain): string
    {
        // Remove common parts
        $name = preg_replace('/\.(com|net|org|io|dev|app|xyz)$/i', '', $domain);
        $name = preg_replace('/[^a-z0-9]/i', '', $name);
        $name = strtolower(substr($name, 0, 20));

        // Ensure uniqueness
        $baseName = $name;
        $counter = 1;
        while (Service::where('name', $name)->exists()) {
            $name = $baseName . $counter;
            $counter++;
        }

        return $name;
    }

    /**
     * Build environment variables for container
     */
    private function buildEnvVars(Service $service, array $data): array
    {
        $env = [];

        // Custom env vars from WHMCS
        if (!empty($data['env_vars']) && is_array($data['env_vars'])) {
            foreach ($data['env_vars'] as $key => $value) {
                $env[] = "{$key}={$value}";
            }
        }

        return $env;
    }

    /**
     * Log activity
     */
    private function logActivity(?int $userId, int $serviceId, string $action, string $description): void
    {
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'service_id' => $serviceId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
