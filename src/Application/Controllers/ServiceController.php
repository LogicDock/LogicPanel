<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\Service\Service;
use LogicPanel\Domain\User\User;
use LogicPanel\Domain\Domain\Domain;
use LogicPanel\Infrastructure\Docker\DockerService;
use Firebase\JWT\JWT;

class ServiceController
{
    private DockerService $dockerService;

    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $services = Service::where('user_id', $userId)->get();

        // Sync status with Docker
        $services->transform(function ($service) {
            if ($service->container_id) {
                // Check if we should hold the 'deploying' status
                // Logic removed: Real-time detection requested.
                // if ($service->status === 'deploying') { ... }

                $isRunning = $this->dockerService->isContainerRunning($service->container_id);
                $newStatus = $isRunning ? 'running' : 'stopped';

                // Only update DB if status changed
                if ($service->status !== $newStatus && $service->status !== 'creating' && $service->status !== 'error') {
                    $service->status = $newStatus;
                    $service->save();
                }
            }
            return $service;
        });

        return $this->jsonResponse($response, [
            'services' => $services->map(function ($service) {
                // Handle comma-separated domains
                $domains = explode(',', $service->domain);
                $primaryDomain = trim($domains[0] ?? '');

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'domain' => $service->domain,
                    'url' => 'http://' . $primaryDomain,
                    'type' => $service->type,
                    'status' => $service->status,
                    'port' => $service->port,
                    'container_id' => $service->container_id,
                    'version' => $service->runtime_version,
                    'created_at' => $service->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Start output buffering to catch any unwanted output (warnings, notices)
        ob_start();

        $logFile = sys_get_temp_dir() . '/logicpanel_debug.log';

        try {
            $userId = $request->getAttribute('userId');
            $data = $request->getParsedBody();

            // Log Request
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Request: " . json_encode($data) . "\n", FILE_APPEND);

            $name = $data['name'] ?? '';
            $type = $data['runtime'] ?? $data['type'] ?? 'nodejs';

            // Load settings from settings.json
            $configFile = __DIR__ . '/../../../config/settings.json';
            $settings = [];
            if (file_exists($configFile)) {
                $settings = json_decode(file_get_contents($configFile), true) ?? [];
            }

            // Generate domain placeholders
            $appDomain = $settings['shared_domain'] ?? $settings['hostname'] ?? $_ENV['APP_DOMAIN'] ?? 'cyberit.cloud';
            $customDomain = !empty($data['domain']) ? $data['domain'] : null;
            $domain = $customDomain; // Can be null initially

            $plan = $data['plan'] ?? 'starter';
            $version = $data['version'] ?? '';

            $installCmd = $data['install_command'] ?? '';
            $buildCmd = $data['build_command'] ?? '';
            $startCmd = $data['start_command'] ?? '';

            // Process Env Vars
            $envVars = [];
            if (isset($data['env_keys']) && isset($data['env_values']) && is_array($data['env_keys'])) {
                foreach ($data['env_keys'] as $index => $key) {
                    if (!empty($key)) {
                        $envVars[$key] = $data['env_values'][$index] ?? '';
                    }
                }
            }

            if (empty($name)) {
                ob_clean();
                return $this->jsonResponse($response, ['message' => 'Name is required'], 400);
            }

            if (!preg_match('/^[a-z0-9-]+$/', $name)) {
                ob_clean();
                return $this->jsonResponse($response, ['message' => 'Name must contain only lowercase letters, numbers, and hyphens'], 400);
            }

            // Resource Limits (Enforce LogicPanel Package Limits)
            $user = User::with('package')->find($userId);
            $package = $user->package ?? null;

            if ($package) {
                // Check Max Services Limit
                $currentServices = Service::where('user_id', $userId)->count();
                // 0 means unlimited? Or check specific value. Assuming > 0 is limit.
                if ($package->max_services > 0 && $currentServices >= $package->max_services) {
                    ob_clean();
                    return $this->jsonResponse($response, ['message' => "Service limit reached. Your plan allows max {$package->max_services} services."], 403);
                }

                $cpu = (float) $package->cpu_limit;
                $mem = $package->memory_limit . 'M';
                $disk = $package->storage_limit . 'M';
            } else {
                // Fallback defaults if no package assigned
                $cpu = 0.5;
                $mem = '512M';
                $disk = '1G';

                // Allow manual override override via 'plan' specific logic only if no package
                if ($plan === 'basic') {
                    $cpu = 1.0;
                    $mem = '1G';
                    $disk = '5G';
                } elseif ($plan === 'pro') {
                    $cpu = 2.0;
                    $mem = '2G';
                    $disk = '10G';
                }
            }

            // Github Repo Logic
            $githubRepo = $data['github_repo'] ?? '';
            $githubBranch = $data['github_branch'] ?? 'main';

            // Docker Image Selection
            $image = 'node:18-alpine';
            if ($type === 'nodejs') {
                if (empty($installCmd))
                    $installCmd = 'npm install';
                if (empty($startCmd))
                    $startCmd = 'npm start';
                if (strpos($version, '20') !== false)
                    $image = 'node:20-alpine';
                elseif (strpos($version, '16') !== false)
                    $image = 'node:16-alpine';
                else
                    $image = 'node:18-alpine';
            } elseif ($type === 'python') {
                if (empty($installCmd))
                    $installCmd = 'pip install -r requirements.txt';
                if (empty($startCmd))
                    $startCmd = 'python app.py';
                if (strpos($version, '3.10') !== false)
                    $image = 'python:3.10-alpine';
                elseif (strpos($version, '3.9') !== false)
                    $image = 'python:3.9-alpine';
                else
                    $image = 'python:3.11-alpine';
            }

            // Create service record first (no port needed with Traefik)
            $service = new Service();
            $service->user_id = $userId;
            $service->name = $name;
            $service->domain = $domain ?: ''; // temporary
            $service->type = $type;
            $service->status = 'creating';
            $service->port = 0;
            $service->cpu_limit = $cpu;
            $service->memory_limit = $mem;
            $service->disk_limit = $disk;
            $service->runtime_version = $version;
            $service->install_command = $installCmd;
            $service->build_command = $buildCmd;
            $service->start_command = $startCmd;
            $service->env_vars = $envVars;

            $service->save();

            // If no custom domain, generate unique one based on ID
            if (empty($domain)) {
                // Format: ServiceID-UserID.AppDomain (e.g., 1-43.cyberit.cloud)
                $domain = "{$service->id}-{$userId}.{$appDomain}";
                $service->domain = $domain;
                $service->save();
            }

            // Log Docker Attempt
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - Creating Container: $image with domain $domain\n", FILE_APPEND);

            // Create Docker container with Nginx Proxy routing
            $containerInfo = $this->dockerService->createContainer(
                "service_{$service->id}",
                $image,
                $domain,  // Use the generated or custom domain
                $envVars,
                $cpu,
                $mem,
                $type,
                $githubRepo,
                $githubBranch,
                $installCmd,
                $buildCmd,
                $startCmd,
                $disk, // Pass Disk Limit
                (bool) ($settings['enable_ssl'] ?? false),
                (string) ($settings['letsencrypt_email'] ?? '')
            );

            // Give container a few seconds to initialize and check if it's still running
            sleep(5);

            // If SSL is enabled, wait extra time for Let's Encrypt to issue certificate
            if (!empty($settings['enable_ssl'])) {
                sleep(12); // Additional wait for SSL certificate issuance
            }

            $isRunning = $this->dockerService->isContainerRunning($containerInfo['container_id']);

            if (!$isRunning) {
                // If not running, fetch logs to understand why
                $logs = $this->dockerService->getContainerLogs($containerInfo['container_id'], 50);
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - Container crashed immediately. Logs:\n$logs\n", FILE_APPEND);

                $service->container_id = $containerInfo['container_id'];
                $service->status = 'error';
                $service->save();

                ob_clean(); // Ensure clean response
                return $this->jsonResponse($response, [
                    'message' => 'App deployed but crashed immediately. Check logs.',
                    'service' => $service,
                    'logs' => $logs
                ], 500); // Or 201 with error status, but 500 alerts user better
            }


            // Update service
            $service->container_id = $containerInfo['container_id'];
            $service->status = 'running';
            $service->save();

            // Write .env file if env vars provided
            if (!empty($envVars)) {
                $this->writeEnvFile($service, $envVars);
            }

            // SYNC TO DOMAINS TABLE
            try {
                $domainList = explode(',', $service->domain);
                foreach ($domainList as $dName) {
                    $dName = trim($dName);
                    if ($dName && !Domain::where('name', $dName)->exists()) {
                        $newDomain = new Domain();
                        $newDomain->name = $dName;
                        $newDomain->user_id = $userId;
                        $newDomain->type = 'subdomain';
                        $newDomain->path = '/';
                        $newDomain->save();
                    }
                }
            } catch (\Exception $e) {
            }

            ob_clean();
            return $this->jsonResponse($response, [
                'message' => 'Service created successfully',
                'service' => $service,
                'container_info' => $containerInfo
            ], 201);

        } catch (\Exception $e) {
            ob_clean();
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n", FILE_APPEND);

            return $this->jsonResponse($response, [
                'error' => 'An error occurred while creating the service.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        $stats = [];
        if ($service->container_id) {
            try {
                // Sync status first
                $isRunning = $this->dockerService->isContainerRunning($service->container_id);
                $newStatus = $isRunning ? 'running' : 'stopped';
                if ($service->status !== $newStatus) {
                    $service->status = $newStatus;
                    $service->save();
                }

                if ($isRunning) {
                    $stats = $this->dockerService->getContainerStats($service->container_id);
                }
            } catch (\Exception $e) {
                $stats = ['error' => 'Failed to get stats'];
            }
        }

        return $this->jsonResponse($response, [
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'domain' => $service->domain,
                'type' => $service->type,
                'status' => $service->status,
                'port' => $service->port,
                'container_id' => $service->container_id,
                'cpu_limit' => $service->cpu_limit,
                'memory_limit' => $service->memory_limit,
                'disk_limit' => $service->disk_limit,
                'created_at' => $service->created_at->toIso8601String(),
                'stats' => $stats,
            ],
        ]);
    }

    public function start(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        try {
            $this->dockerService->startContainer($service->container_id);
            $service->status = 'running';
            $service->save();

            return $this->jsonResponse($response, ['message' => 'Service started successfully']);
        } catch (\Exception $e) {
            // Handle case where container might be missing
            if (strpos($e->getMessage(), 'No such container') !== false) {
                $service->status = 'error'; // Or 'stopped', but error indicates it needs attention (recreation)
                $service->save();
                return $this->jsonResponse($response, [
                    'error' => 'Container missing. Please delete and recreate the app.',
                    'message' => 'Container missing'
                ], 404);
            }

            return $this->jsonResponse($response, [
                'error' => 'Failed to start service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function stop(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        try {
            $this->dockerService->stopContainer($service->container_id);
            $service->status = 'stopped';
            $service->save();

            return $this->jsonResponse($response, ['message' => 'Service stopped successfully']);
        } catch (\Exception $e) {
            // If container not found, just mark as stopped
            if (strpos($e->getMessage(), 'No such container') !== false) {
                $service->status = 'stopped';
                $service->save();
                return $this->jsonResponse($response, ['message' => 'Service marked as stopped (Container missing)']);
            }

            return $this->jsonResponse($response, [
                'error' => 'Failed to stop service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function restart(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        try {
            $this->dockerService->restartContainer($service->container_id);
            $service->status = 'running';
            $service->save();

            return $this->jsonResponse($response, ['message' => 'Service restarted successfully']);
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'No such container') !== false) {
                $service->status = 'stopped';
                $service->save();
                return $this->jsonResponse($response, ['error' => 'Container missing'], 404);
            }

            return $this->jsonResponse($response, [
                'error' => 'Failed to restart service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function logs(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        try {
            $logs = $this->dockerService->getContainerLogs($service->container_id);

            return $this->jsonResponse($response, [
                'logs' => $logs,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to get logs',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function stats(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        try {
            $stats = $this->dockerService->getContainerStats($service->container_id);

            return $this->jsonResponse($response, [
                'stats' => $stats,
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to get stats',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        $data = json_decode((string) $request->getBody(), true) ?: [];

        // Update allowed fields
        $allowedFields = ['name', 'domain', 'install_command', 'build_command', 'start_command', 'env_vars', 'runtime_version'];
        $domainChanged = false;

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'domain') {
                    // Validate Domain (allow comma separated, alphanumeric, dots, hyphens)
                    $domains = explode(',', $data[$field]);
                    $cleanDomains = [];
                    foreach ($domains as $d) {
                        $d = trim($d);
                        if (!empty($d)) {
                            // Basic validation
                            if (!preg_match('/^[a-zA-Z0-9.-]+$/', $d)) {
                                return $this->jsonResponse($response, ['error' => 'Invalid domain format: ' . $d], 400);
                            }
                            $cleanDomains[] = $d;
                        }
                    }
                    $newDomainStr = implode(',', $cleanDomains);

                    if ($service->domain !== $newDomainStr) {
                        $service->domain = $newDomainStr;
                        $domainChanged = true;
                    }
                } else {
                    $service->$field = $data[$field];
                }
            }
        }

        $service->save();

        // If env_vars were updated, also write them to a .env file in the app directory
        if (isset($data['env_vars']) && is_array($data['env_vars'])) {
            $this->writeEnvFile($service, $data['env_vars']);
        }

        // RECREATE CONTAINER IF DOMAIN CHANGED
        // VIRTUAL_HOST, LETSENCRYPT_HOST etc are baked into container labels/env at creation.
        // We must destroy and recreate.
        if ($domainChanged && $service->container_id) {
            try {
                // 1. Remove old container
                try {
                    $this->dockerService->removeContainer($service->container_id);
                } catch (\Exception $e) {
                    // Ignore if missing
                }

                // 2. Prepare params for new container
                // We need to fetch necessary params that might not be in $data but are in DB

                // Image Determination (Re-used logic from create - should ideally be refactored to a helper)
                $image = 'node:18-alpine'; // Default
                if ($service->type === 'nodejs') {
                    if (strpos($service->runtime_version, '20') !== false)
                        $image = 'node:20-alpine';
                    elseif (strpos($service->runtime_version, '16') !== false)
                        $image = 'node:16-alpine';
                    else
                        $image = 'node:18-alpine';
                } elseif ($service->type === 'python') {
                    if (strpos($service->runtime_version, '3.10') !== false)
                        $image = 'python:3.10-alpine';
                    elseif (strpos($service->runtime_version, '3.9') !== false)
                        $image = 'python:3.9-alpine';
                    else
                        $image = 'python:3.11-alpine';
                }

                // Create Container
                $containerInfo = $this->dockerService->createContainer(
                    "service_{$service->id}",
                    $image,
                    $service->domain,
                    $service->env_vars ?? [],
                    $service->cpu_limit,
                    $service->memory_limit,
                    $service->type,
                    '', // Repo not needed for recreation, code already exists
                    'main',
                    $service->install_command ?: '',
                    $service->build_command ?: '',
                    $service->start_command ?: ''
                );

                // Update Service with new Container ID
                $service->container_id = $containerInfo['container_id'];
                $service->status = 'running';
                $service->save();

            } catch (\Exception $e) {
                return $this->jsonResponse($response, [
                    'message' => 'Domain updated but failed to restart app: ' . $e->getMessage(),
                    'service' => $service,
                ], 500);
            }
        }

        return $this->jsonResponse($response, [
            'message' => 'Service updated successfully' . ($domainChanged ? ' and restarted.' : ''),
            'service' => $service,
        ]);
    }

    /**
     * Write environment variables to a .env file in the app's directory
     */
    private function writeEnvFile(Service $service, array $envVars): void
    {
        // Use the same path as DockerService - /var/www/html/storage/user-apps in container
        // or relative path that works in both environments
        $appPath = $_ENV['USER_APPS_PATH'] ?? '/var/www/html/storage/user-apps';

        // Fallback: if we're on Windows (local dev), use storage path relative to current script
        if (!is_dir($appPath) && PHP_OS_FAMILY === 'Windows') {
            $appPath = dirname(__DIR__, 3) . '/storage/user-apps';
        }

        $envFilePath = $appPath . "/service_{$service->id}/.env";

        $content = "# Auto-generated by LogicPanel - Do not edit manually\n";
        $content .= "# Last updated: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($envVars as $key => $value) {
            // Escape values that contain special characters
            $escapedValue = $this->escapeEnvValue($value);
            $content .= "{$key}={$escapedValue}\n";
        }

        // Ensure the directory exists
        $dir = dirname($envFilePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($envFilePath, $content);
        @chmod($envFilePath, 0644);
    }

    /**
     * Escape special characters in .env values
     */
    private function escapeEnvValue(string $value): string
    {
        // If value contains spaces, quotes, or special chars, wrap in quotes
        if (preg_match('/[\s"\'\\\\$`!]/', $value)) {
            // Escape existing quotes and wrap in double quotes
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return "\"{$escaped}\"";
        }
        return $value;
    }

    public function command(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];
        $data = $request->getParsedBody();
        $command = $data['command'] ?? '';
        $cwd = $data['cwd'] ?? '';

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'No container associated'], 400);
        }

        if (!$this->dockerService->isContainerRunning($service->container_id)) {
            return $this->jsonResponse($response, [
                'error' => 'Application is not running. Please start it first.',
                'message' => 'Container is stopped.'
            ], 400);
        }

        if (empty($command)) {
            return $this->jsonResponse($response, ['error' => 'Command required'], 400);
        }

        // Determine working directory
        // If cwd is empty, default to container workdir which corresponds to /app in user view but /storage/{name} in reality
        // LogicPanel maps volume to /storage. Container is started with WORKDIR /storage/{name}
        // User wants to see /app.

        $internalCwd = $cwd;
        if (empty($cwd) || $cwd === '/app' || $cwd === '~') {
            $internalCwd = "/storage/service_{$service->id}";
        } else if (str_starts_with($cwd, '/app')) {
            // Map /app to /storage/{name} logic?
            // Or just trust the relative path?
            // Let's assume user is smart or we handle it.
            // If user does `cd ..`, they might escape to /storage.
            // We'll stick to running the command. `docker exec -w` takes absolute path.
        }

        try {
            // Execute command
            // We append " && pwd" to get the new directory if it changed (e.g. cd)
            // But "docker exec -w" sets the starting directory.
            // If command contains "cd", it only affects that shell instance unless we chain it.
            // Since this is a one-off exec, "cd" won't persist across requests.
            // UI needs to handle this by tracking CWD and sending it back.
            // BUT "cd newdir" produces no output.
            // Users want stateful terminal feel.
            // We can wrap it: sh -c "cd {cwd} && {command} && echo '___PWD___' && pwd"

            // Fix for "cd" command: if user types "cd ..", we run "cd {cwd} && cd .. && pwd" to get new path.

            $safeCommand = str_replace('"', '\"', $command); // Basic escape

            // Check if user is trying to CD
            if (preg_match('/^cd\s+(.+)$/', trim($command), $matches)) {
                // Return new path only? No, run checks.
                $wrappedCommand = "cd \"{$internalCwd}\" && {$command} && echo \"__PWD:$(pwd)\"";
            } else {
                $wrappedCommand = "cd \"{$internalCwd}\" && {$command}";
            }

            // We use executeCommand from DockerService (needs to be exposed/public)
            $output = $this->dockerService->executeCommand($service->container_id, $wrappedCommand);

            $newCwd = $internalCwd;

            // Parse output for PWD
            if (preg_match('/__PWD:(.+)/', $output, $matches)) {
                $newCwd = trim($matches[1]);
                $output = str_replace($matches[0], '', $output); // Remove PWD marker from output
            }

            // Map internal paths back to /app for display?
            // /storage/name -> /app
            // For now, let's keep it real path to avoid confusion or do simple replacement
            $displayCwd = $newCwd;
            // if (str_starts_with($newCwd, "/storage/{$service->name}")) {
            //    $displayCwd = str_replace("/storage/{$service->name}", "/app", $newCwd);
            // }

            return $this->jsonResponse($response, [
                'output' => $output,
                'cwd' => $displayCwd
            ]);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Command failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        try {
            // Remove Docker container
            if ($service->container_id) {
                try {
                    $this->dockerService->removeContainer($service->container_id);
                } catch (\Exception $e) {
                    // Ignore "No such container" error and proceed to delete from DB
                    if (strpos($e->getMessage(), 'No such container') === false) {
                        throw $e;
                    }
                }
            }

            // SYNC: Remove from Domains table
            try {
                $domainList = explode(',', $service->domain);
                foreach ($domainList as $dName) {
                    $dName = trim($dName);
                    if ($dName) {
                        Domain::where('name', $dName)->delete();
                    }
                }
            } catch (\Exception $e) {
            }

            // Delete storage directory
            $this->deleteServiceDirectory($service);

            // Delete service record
            $service->delete();

            return $this->jsonResponse($response, ['message' => 'Service deleted successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to delete service',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function deleteServiceDirectory(Service $service): void
    {
        $appPath = $_ENV['USER_APPS_PATH'] ?? '/var/www/html/storage/user-apps';

        // Windows fallback logic (same as writeEnvFile)
        if (!is_dir($appPath) && PHP_OS_FAMILY === 'Windows') {
            $appPath = dirname(__DIR__, 3) . '/storage/user-apps';
        }

        $dir = $appPath . "/service_{$service->id}";

        if (is_dir($dir)) {
            $this->recursiveDelete($dir);
        }
    }

    private function recursiveDelete(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveDelete("$dir/$file") : unlink("$dir/$file");
        }
        rmdir($dir);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
    public function getTerminalToken(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = (int) $args['id'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $userId)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'Service or container not found'], 404);
        }

        // Generate Short-lived JWT for Terminal Gateway
        // Expiry: 1 minute (Client must connect immediately)
        $payload = [
            'iss' => 'logicpanel-backend',
            'aud' => 'logicpanel-gateway',
            'iat' => time(),
            'exp' => time() + 60,
            'sub' => $userId,
            'service_id' => $service->id,
            'container_id' => $service->container_id
        ];

        // Use the shared secret from environment
        $secret = $_ENV['JWT_SECRET'] ?? 'secret';
        $token = JWT::encode($payload, $secret, 'HS256');

        // Build dynamic gateway URL based on request host
        // We route through Apache proxy at /ws/terminal to handle SSL properly
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        // Determine protocol (wss for HTTPS, ws for HTTP)
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
        $wsProtocol = $isSecure ? 'wss' : 'ws';

        // Use the proxy path /ws/terminal instead of direct port
        $gatewayUrl = "{$wsProtocol}://{$host}/ws/terminal";

        return $this->jsonResponse($response, [
            'token' => $token,
            'gateway_url' => $gatewayUrl
        ]);
    }
}
