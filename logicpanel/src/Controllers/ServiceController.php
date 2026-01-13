<?php
/**
 * LogicPanel - Service Controller
 * Handles container management operations
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
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
            ], 404);
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
            return $this->render($response, 'errors/404', ['message' => 'Service not found'], 404);
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
}
