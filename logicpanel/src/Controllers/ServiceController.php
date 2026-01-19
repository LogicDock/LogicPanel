<?php
/**
 * LogicPanel - Service Controller
 * Handles container management operations
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Models\Package;
use LogicPanel\Models\Domain;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class ServiceController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * Show create service form
     */
    public function showCreate(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $packages = Package::all();

        return $this->render($response, 'services/create', [
            'title' => 'Create New Service',
            'packages' => $packages,
            'current_page' => 'service_create'
        ]);
    }

    /**
     * Process service creation
     */
    public function processCreate(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // 1. Basic Validation
        $name = $this->sanitizeName($data['name'] ?? '');
        $runtime = $data['runtime'] ?? 'nodejs';
        $packageId = (int) ($data['package_id'] ?? 0);

        if (empty($name)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service name is required'], 400);
        }

        // 2. Validate Selected Package
        $packageId = (int) ($data['package_id'] ?? 0);
        $package = Package::find($packageId);

        if (!$package) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Please select a valid service plan.'], 400);
        }

        // Check usage against the selected package limits
        $currentCount = Service::where('user_id', $user->id)->count();
        if ($currentCount >= $package->max_services) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service limit reached for this plan.'], 400);
        }

        // 3. Create Service Record
        $service = new Service();
        $service->user_id = $user->id;
        $service->package_id = $package->id;
        $service->name = $name;
        $service->runtime = $runtime;
        $service->status = 'provisioning';
        $service->port = in_array($runtime, ['java', 'go']) ? 8080 : ($runtime === 'python' ? 8000 : 3000);
        $service->save();

        // 4. Assign Default Domain
        $domain = new Domain();
        $domain->service_id = $service->id;
        $domain->domain = $name . '-' . $service->id . '.' . ($_ENV['APP_DOMAIN'] ?? 'logicpanel.io');
        $domain->is_primary = true;
        $domain->ssl_enabled = true;
        $domain->save();

        // 5. Trigger Container Provisioning
        $result = $this->provisionContainer($service, $package);

        if ($result['success']) {
            $service->container_id = $result['container_id'];
            $service->status = 'running';
            $service->save();

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Service created successfully',
                'service_id' => $service->id
            ]);
        } else {
            // Rollback record if docker fails
            $service->delete();
            return $this->jsonResponse($response, ['success' => false, 'error' => $result['error']], 500);
        }
    }

    /**
     * List all services
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $services = Service::where('user_id', $user->id)
            ->with(['primaryDomain', 'domains', 'databases'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get live status from Docker
        foreach ($services as $service) {
            if ($service->container_id) {
                $info = $this->docker->inspectContainer($service->container_id);
                if ($info) {
                    $service->live_status = $info['State']['Status'] ?? 'unknown';
                    $service->live_running = $info['State']['Running'] ?? false;
                }
            }
        }

        return $this->render($response, 'services/index', [
            'title' => 'My Services - LogicPanel',
            'services' => $services
        ]);
    }

    /**
     * Show single service details
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->with([
                'domains',
                'databases',
                'backups',
                'deployments' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->first();

        if (!$service) {
            return $this->render($response, 'errors/404', [
                'title' => 'Not Found - LogicPanel',
                'message' => 'Service not found'
            ]);
        }

        // Get container details
        $containerInfo = null;
        $containerStats = null;

        if ($service->container_id) {
            $containerInfo = $this->docker->inspectContainer($service->container_id);
            if ($containerInfo && ($containerInfo['State']['Running'] ?? false)) {
                $stats = $this->docker->getContainerStats($service->container_id);
                if ($stats) {
                    $containerStats = $this->calculateStats($stats);
                }
            }
        }

        return $this->render($response, 'services/show', [
            'title' => $service->name . ' - LogicPanel',
            'service' => $service,
            'containerInfo' => $containerInfo,
            'containerStats' => $containerStats
        ]);
    }

    /**
     * Start service container
     */
    public function start(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not created'], 400);
        }

        $result = $this->docker->startContainer($service->container_id);

        if ($result) {
            $service->status = 'running';
            $service->save();
            $this->logActivity($user->id, $service->id, 'service_start', 'Service started');
            return $this->jsonResponse($response, ['success' => true, 'message' => 'Service started']);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Failed to start service: ' . $this->docker->getLastError()
        ], 500);
    }

    /**
     * Stop service container
     */
    public function stop(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not created'], 400);
        }

        $result = $this->docker->stopContainer($service->container_id);

        if ($result) {
            $service->status = 'stopped';
            $service->save();
            $this->logActivity($user->id, $service->id, 'service_stop', 'Service stopped');
            return $this->jsonResponse($response, ['success' => true, 'message' => 'Service stopped']);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Failed to stop service: ' . $this->docker->getLastError()
        ], 500);
    }

    /**
     * Restart service container
     */
    public function restart(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not created'], 400);
        }

        $result = $this->docker->restartContainer($service->container_id);

        if ($result) {
            $service->status = 'running';
            $service->save();
            $this->logActivity($user->id, $service->id, 'service_restart', 'Service restarted');
            return $this->jsonResponse($response, ['success' => true, 'message' => 'Service restarted']);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Failed to restart service: ' . $this->docker->getLastError()
        ], 500);
    }

    /**
     * Rebuild service (re-deploy from git)
     */
    public function rebuild(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        // Create deployment record
        $deploymentId = DB::table('deployments')->insertGetId([
            'service_id' => $service->id,
            'branch' => $service->github_branch,
            'status' => 'pending',
            'started_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Queue the rebuild process
        // In production, this would be handled by a background job
        // For now, we'll do a synchronous rebuild

        try {
            $this->performRebuild($service, $deploymentId);
            $this->logActivity($user->id, $service->id, 'service_rebuild', 'Service rebuild initiated');
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Rebuild started',
                'deployment_id' => $deploymentId
            ]);
        } catch (\Exception $e) {
            DB::table('deployments')
                ->where('id', $deploymentId)
                ->update([
                    'status' => 'failed',
                    'log' => $e->getMessage(),
                    'completed_at' => date('Y-m-d H:i:s')
                ]);

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Rebuild failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service logs
     */
    public function logs(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];
        $params = $request->getQueryParams();
        $tail = (int) ($params['tail'] ?? 100);

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        $logs = $this->docker->getContainerLogs($service->container_id, $tail);

        if ($logs !== null) {
            return $this->jsonResponse($response, [
                'success' => true,
                'logs' => $logs
            ]);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Failed to get logs'
        ], 500);
    }

    /**
     * Get service stats
     */
    public function stats(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        $stats = $this->docker->getContainerStats($service->container_id);

        if ($stats) {
            return $this->jsonResponse($response, [
                'success' => true,
                'stats' => $this->calculateStats($stats)
            ]);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Failed to get stats'
        ], 500);
    }

    /**
     * Environment variables editor
     */
    public function envEditor(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->render($response, 'errors/404', ['message' => 'Service not found']);
        }

        return $this->render($response, 'services/env', [
            'title' => 'Environment Variables - ' . $service->name,
            'service' => $service,
            'envVars' => $service->env_vars ?? []
        ]);
    }

    /**
     * Save environment variables
     */
    public function saveEnv(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['id'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        // Parse env vars from request
        $envVars = [];
        if (isset($data['env_keys']) && isset($data['env_values'])) {
            foreach ($data['env_keys'] as $i => $key) {
                $key = trim($key);
                if (!empty($key)) {
                    $envVars[$key] = $data['env_values'][$i] ?? '';
                }
            }
        }

        $service->env_vars = $envVars;
        $service->save();

        // Restart container to apply changes
        if ($service->container_id && $service->status === 'running') {
            $this->docker->restartContainer($service->container_id);
        }

        $this->logActivity($user->id, $service->id, 'env_update', 'Environment variables updated');

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Environment variables saved'
        ]);
    }

    /**
     * Perform rebuild process
     */
    private function performRebuild(Service $service, int $deploymentId): void
    {
        $log = [];

        // Update deployment status
        $updateDeployment = function ($status, $logEntry = null) use ($deploymentId, &$log) {
            if ($logEntry) {
                $log[] = date('H:i:s') . ' - ' . $logEntry;
            }
            DB::table('deployments')
                ->where('id', $deploymentId)
                ->update([
                    'status' => $status,
                    'log' => implode("\n", $log),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
        };

        // Stop existing container
        if ($service->container_id) {
            $updateDeployment('cloning', 'Stopping existing container...');
            $this->docker->stopContainer($service->container_id);
        }

        // Clone/pull repository
        if ($service->github_repo) {
            $updateDeployment('cloning', 'Cloning repository: ' . $service->github_repo);

            // Build git clone command with PAT if available
            $repoUrl = $service->github_repo;
            if ($service->github_pat) {
                // Insert PAT into URL for private repos
                $repoUrl = preg_replace(
                    '/https:\/\//',
                    'https://' . $service->github_pat . '@',
                    $repoUrl
                );
            }

            // Execute git commands inside container
            $gitCmd = [
                'sh',
                '-c',
                "cd /app && rm -rf * && git clone --branch {$service->github_branch} --single-branch {$repoUrl} ."
            ];
            $this->docker->execInContainer($service->container_id, $gitCmd);
        }

        // Install dependencies
        $updateDeployment('installing', 'Installing dependencies...');
        $installCmd = ['sh', '-c', "cd /app && {$service->install_cmd}"];
        $this->docker->startContainer($service->container_id);
        $this->docker->execInContainer($service->container_id, $installCmd);

        // Build
        if ($service->build_cmd && $service->build_cmd !== 'npm run build') {
            $updateDeployment('building', 'Building application...');
            $buildCmd = ['sh', '-c', "cd /app && {$service->build_cmd}"];
            $this->docker->execInContainer($service->container_id, $buildCmd);
        }

        // Start application
        $updateDeployment('starting', 'Starting application...');
        $this->docker->restartContainer($service->container_id);

        // Mark as completed
        DB::table('deployments')
            ->where('id', $deploymentId)
            ->update([
                'status' => 'completed',
                'log' => implode("\n", $log) . "\n" . date('H:i:s') . ' - Deployment completed successfully',
                'completed_at' => date('Y-m-d H:i:s')
            ]);

        $service->status = 'running';
        $service->save();
    }

    /**
     * Calculate stats from Docker API
     */
    private function calculateStats(array $stats): array
    {
        $cpuDelta = ($stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0) -
            ($stats['precpu_stats']['cpu_usage']['total_usage'] ?? 0);
        $systemDelta = ($stats['cpu_stats']['system_cpu_usage'] ?? 0) -
            ($stats['precpu_stats']['system_cpu_usage'] ?? 0);
        $cpuCount = $stats['cpu_stats']['online_cpus'] ?? 1;

        $cpuPercent = 0;
        if ($systemDelta > 0 && $cpuDelta > 0) {
            $cpuPercent = ($cpuDelta / $systemDelta) * $cpuCount * 100;
        }

        $memoryUsage = $stats['memory_stats']['usage'] ?? 0;
        $memoryLimit = $stats['memory_stats']['limit'] ?? 0;
        $memoryPercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

        $networkRx = 0;
        $networkTx = 0;
        if (isset($stats['networks'])) {
            foreach ($stats['networks'] as $network) {
                $networkRx += $network['rx_bytes'] ?? 0;
                $networkTx += $network['tx_bytes'] ?? 0;
            }
        }

        return [
            'cpu' => round($cpuPercent, 2),
            'memory_used' => $memoryUsage,
            'memory_limit' => $memoryLimit,
            'memory_percent' => round($memoryPercent, 2),
            'memory_human' => $this->formatBytes($memoryUsage) . ' / ' . $this->formatBytes($memoryLimit),
            'network_rx' => $networkRx,
            'network_tx' => $networkTx,
            'network_rx_human' => $this->formatBytes($networkRx),
            'network_tx_human' => $this->formatBytes($networkTx)
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Log activity
     */
    private function logActivity(int $userId, int $serviceId, string $action, string $description): void
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

    // ============================================
    // Admin Actions (no ownership check)
    // ============================================

    /**
     * Admin: Suspend any service
     */
    public function adminSuspend(Request $request, Response $response, array $args): Response
    {
        $serviceId = (int) $args['id'];
        $service = Service::find($serviceId);

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

        $admin = $request->getAttribute('user');
        $this->logActivity($admin->id, $service->id, 'admin_suspend', "Admin suspended service by {$admin->name}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Service suspended successfully'
        ]);
    }

    /**
     * Admin: Unsuspend any service
     */
    public function adminUnsuspend(Request $request, Response $response, array $args): Response
    {
        $serviceId = (int) $args['id'];
        $service = Service::find($serviceId);

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

        $admin = $request->getAttribute('user');
        $this->logActivity($admin->id, $service->id, 'admin_unsuspend', "Admin unsuspended service by {$admin->name}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Service unsuspended successfully'
        ]);
    }

    /**
     * Admin: Terminate any service
     */
    public function adminTerminate(Request $request, Response $response, array $args): Response
    {
        $serviceId = (int) $args['id'];
        $service = Service::with(['databases', 'domains'])->find($serviceId);

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        $admin = $request->getAttribute('user');
        $serviceName = $service->name;
        $userId = $service->user_id;

        // Remove container
        if ($service->container_id) {
            try {
                $this->docker->stopContainer($service->container_id);
                $this->docker->removeContainer($service->container_id);
            } catch (\Exception $e) {
                // Log but continue
            }
        }

        // Delete databases
        foreach ($service->databases as $db) {
            $db->delete();
        }

        // Delete domains
        foreach ($service->domains as $domain) {
            $domain->delete();
        }

        // Delete deployments
        DB::table('deployments')->where('service_id', $serviceId)->delete();

        // Delete backups
        DB::table('backups')->where('service_id', $serviceId)->delete();

        // Delete service
        $service->delete();

        // Log after delete (use user_id from before deletion)
        DB::table('activity_log')->insert([
            'user_id' => $admin->id,
            'service_id' => null,
            'action' => 'admin_terminate',
            'description' => "Admin terminated service '{$serviceName}' (was owned by user #{$userId})",
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Service terminated and all data deleted'
        ]);
    }

    /**
     * Admin: Create new service (Standalone Mode)
     */
    public function adminCreateService(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $admin = $request->getAttribute('user');

        // Validate required fields
        if (empty($data['service_name']) || empty($data['domain'])) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Service name and domain are required'
            ], 400);
        }

        // Get or create user
        $userId = null;
        if (!empty($data['new_user'])) {
            // Create new user
            $newUser = $data['new_user'];

            if (empty($newUser['name']) || empty($newUser['email']) || empty($newUser['username']) || empty($newUser['password'])) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'All new user fields are required'
                ], 400);
            }

            // Check if email/username exists
            $exists = \LogicPanel\Models\User::where('email', $newUser['email'])
                ->orWhere('username', $newUser['username'])
                ->first();

            if ($exists) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'User with this email or username already exists'
                ], 400);
            }

            $user = new \LogicPanel\Models\User();
            $user->name = $newUser['name'];
            $user->email = $newUser['email'];
            $user->username = $newUser['username'];
            $user->password = $newUser['password']; // Will be hashed by model
            $user->role = 'user';
            $user->is_active = true;
            $user->save();

            $userId = $user->id;
        } else {
            $userId = (int) ($data['user_id'] ?? 0);
            if (!$userId) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'User selection is required'
                ], 400);
            }
        }

        // Get package
        $packageId = (int) ($data['package_id'] ?? 0);
        $package = \LogicPanel\Models\Package::find($packageId);
        if (!$package) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Invalid package selected'
            ], 400);
        }

        // Create service
        $service = new Service();
        $service->user_id = $userId;
        $service->package_id = $packageId;
        $service->name = $this->generateServiceName($data['service_name']);
        $service->plan = $package->name;
        $service->status = 'pending';
        $service->runtime = $data['runtime'] ?? 'nodejs';
        $service->runtime_version = $data['runtime_version'] ?? 'latest';
        $service->git_repo = $data['git_repo'] ?? null;
        $service->git_branch = $data['git_branch'] ?? 'main';
        $service->env_vars = json_encode([]);

        // Track creator (for hierarchy: admin created this service)
        $service->created_by = $admin->id;

        $service->save();

        // Create primary domain
        $domain = new Domain();
        $domain->service_id = $service->id;
        $domain->domain = strtolower(trim($data['domain']));
        $domain->is_primary = true;
        $domain->ssl_enabled = true;
        $domain->save();

        // Try to provision container
        try {
            $containerResult = $this->provisionContainer($service, $package);
            if ($containerResult['success']) {
                $service->container_id = $containerResult['container_id'];
                $service->status = 'running';
                $service->provisioned_at = date('Y-m-d H:i:s');
            } else {
                $service->status = 'error';
                $service->error_message = $containerResult['error'] ?? 'Container provisioning failed';
            }
            $service->save();
        } catch (\Exception $e) {
            $service->status = 'error';
            $service->error_message = $e->getMessage();
            $service->save();
        }

        // Log activity
        $this->logActivity(
            $admin->id,
            $service->id,
            'admin_create_service',
            "Admin created service '{$service->name}' for user #{$userId}"
        );

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Service created successfully',
            'service_id' => $service->id,
            'status' => $service->status
        ]);
    }

    /**
     * Sanitize name for docker and domain
     */
    private function sanitizeName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        return trim($name, '-');
    }

    /**
     * Generate a unique service name
     */
    private function generateServiceName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\-]/', '-', $name);
        $name = preg_replace('/-+/', '-', $name);
        $name = trim($name, '-');
        return substr($name, 0, 50) ?: 'service-' . time();
    }

    /**
     * Provision container for service
     * Made public for cross-controller re-provisioning
     */
    public function provisionContainer(Service $service, \LogicPanel\Models\Package $package): array
    {
        $containerName = 'lp-' . $service->name . '-' . $service->id;
        $volumeName = "lp_data_{$service->id}";

        // Ensure volume exists for data persistence
        $this->docker->createVolume($volumeName, [
            'logicpanel.service_id' => (string) $service->id,
            'logicpanel.user_id' => (string) $service->user_id
        ]);

        // Get primary domain for SSL labels
        $primaryDomain = \LogicPanel\Models\Domain::where('service_id', $service->id)
            ->where('is_primary', true)
            ->first();

        // Get all domains for VIRTUAL_HOST
        $allDomains = \LogicPanel\Models\Domain::where('service_id', $service->id)
            ->pluck('domain')
            ->toArray();

        $domainList = implode(',', $allDomains);

        // Determine image based on runtime
        $images = [
            'nodejs' => 'node:18-alpine',
            'python' => 'python:3.11-alpine',
            'java' => 'openjdk:17-slim',
            'go' => 'golang:1.21-alpine'
        ];
        $image = $images[$service->runtime] ?? 'node:18-alpine';

        // Base Environment Variables
        $env = [
            'SERVICE_ID=' . $service->id,
            'RUNTIME=' . $service->runtime,
            'PORT=' . ($service->port ?: 3000),
            'VIRTUAL_HOST=' . ($domainList ?: 'localhost'),
            'VIRTUAL_PORT=' . ($service->port ?: 3000),
            'LETSENCRYPT_HOST=' . ($domainList ?: ''),
            'LETSENCRYPT_EMAIL=' . ($service->user->email ?? 'admin@localhost')
        ];

        // Add custom env vars
        $customEnv = is_string($service->env_vars) ? json_decode($service->env_vars, true) : $service->env_vars;
        if (is_array($customEnv)) {
            foreach ($customEnv as $key => $value) {
                $env[] = "{$key}={$value}";
            }
        }

        // Build container config
        $config = [
            'Image' => $image,
            'name' => $containerName,
            'Hostname' => $containerName,
            'Env' => $env,
            'WorkingDir' => '/app',
            'Tty' => true,
            'HostConfig' => [
                'Binds' => ["{$volumeName}:/app"],
                'Memory' => $package->memory_limit * 1024 * 1024,
                'NanoCPUs' => (int) ($package->cpu_limit * 1000000000),
                'RestartPolicy' => ['Name' => 'unless-stopped'],
                'NetworkMode' => $_ENV['NGINX_PROXY_NETWORK'] ?? 'nginx-proxy_web'
            ],
            'Labels' => [
                'logicpanel.managed' => 'true',
                'logicpanel.service_id' => (string) $service->id,
                'logicpanel.user_id' => (string) $service->user_id
            ]
        ];

        // Create and start container
        $createResult = $this->docker->createContainer($config, $containerName);
        if (!$createResult || empty($createResult['Id'])) {
            return ['success' => false, 'error' => 'Failed to create container: ' . $this->docker->getLastError()];
        }

        $containerId = $createResult['Id'];

        // Start container
        $startResult = $this->docker->startContainer($containerId);

        if ($startResult === false) {
            return ['success' => false, 'error' => 'Failed to start container'];
        }

        return ['success' => true, 'container_id' => $containerId];
    }
}
