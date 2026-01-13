<?php
/**
 * LogicPanel - Git Controller
 * Handles Git deployment with private repo support
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Models\Deployment;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class GitController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * Git settings page
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->with([
                'deployments' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(10);
                }
            ])
            ->first();

        if (!$service) {
            return $this->render($response, 'errors/404', ['message' => 'Service not found'], 404);
        }

        return $this->render($response, 'git/index', [
            'title' => 'Git Deployment - ' . $service->name,
            'service' => $service,
            'deployments' => $service->deployments
        ]);
    }

    /**
     * Deploy from Git repository
     */
    public function deploy(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
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

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not created'], 400);
        }

        if (empty($service->github_repo)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Git repository not configured'], 400);
        }

        // Create deployment record
        $deployment = new Deployment();
        $deployment->service_id = $serviceId;
        $deployment->branch = $service->github_branch ?: 'main';
        $deployment->status = 'pending';
        $deployment->started_at = date('Y-m-d H:i:s');
        $deployment->save();

        try {
            $this->performDeployment($service, $deployment);

            $this->logActivity($user->id, $serviceId, 'git_deploy', "Deployed from branch: {$deployment->branch}");

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Deployment completed successfully',
                'deployment' => [
                    'id' => $deployment->id,
                    'status' => $deployment->status,
                    'commit_hash' => $deployment->getShortHash()
                ]
            ]);
        } catch (\Exception $e) {
            $deployment->status = 'failed';
            $deployment->log = ($deployment->log ?? '') . "\nError: " . $e->getMessage();
            $deployment->completed_at = date('Y-m-d H:i:s');
            $deployment->save();

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Deployment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Save Git configuration
     */
    public function saveConfig(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        // Validate repository URL
        $repoUrl = trim($data['github_repo'] ?? '');
        if (!empty($repoUrl) && !$this->isValidGitUrl($repoUrl)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid Git repository URL'], 400);
        }

        // Update service
        $service->github_repo = $repoUrl;
        $service->github_branch = trim($data['github_branch'] ?? 'main') ?: 'main';

        // Handle PAT (Personal Access Token)
        if (isset($data['github_pat'])) {
            $pat = trim($data['github_pat']);
            if (!empty($pat)) {
                // Encrypt PAT before storing
                $service->github_pat = $this->encrypt($pat);
            } elseif ($data['clear_pat'] ?? false) {
                $service->github_pat = null;
            }
        }

        // Update commands
        $service->install_cmd = trim($data['install_cmd'] ?? 'npm install') ?: 'npm install';
        $service->build_cmd = trim($data['build_cmd'] ?? '');
        $service->start_cmd = trim($data['start_cmd'] ?? 'npm start') ?: 'npm start';

        $service->save();

        $this->logActivity($user->id, $serviceId, 'git_config', 'Git configuration updated');

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Git configuration saved successfully'
        ]);
    }

    /**
     * Get deployment history
     */
    public function history(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $params = $request->getQueryParams();
        $limit = min((int) ($params['limit'] ?? 20), 100);

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        $deployments = Deployment::where('service_id', $serviceId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->jsonResponse($response, [
            'success' => true,
            'deployments' => $deployments->map(function ($d) {
                return [
                    'id' => $d->id,
                    'commit_hash' => $d->getShortHash(),
                    'commit_message' => $d->commit_message,
                    'branch' => $d->branch,
                    'status' => $d->status,
                    'duration' => $d->getDuration(),
                    'started_at' => $d->started_at,
                    'completed_at' => $d->completed_at
                ];
            })
        ]);
    }

    /**
     * Perform the deployment
     */
    private function performDeployment(Service $service, Deployment $deployment): void
    {
        $log = [];
        $updateLog = function ($message) use (&$log, $deployment) {
            $log[] = date('H:i:s') . ' - ' . $message;
            $deployment->log = implode("\n", $log);
            $deployment->save();
        };

        // Build Git URL with PAT if available
        $repoUrl = $service->github_repo;
        if ($service->github_pat) {
            $pat = $this->decrypt($service->github_pat);
            if ($pat) {
                // Insert PAT into URL: https://TOKEN@github.com/user/repo.git
                $repoUrl = preg_replace('/https:\/\//', "https://{$pat}@", $repoUrl);
            }
        }

        // Step 1: Update status
        $deployment->status = 'cloning';
        $updateLog('Starting deployment...');

        // Step 2: Clone/Pull repository
        $updateLog('Cloning repository from ' . $service->github_repo . ' (branch: ' . $service->github_branch . ')');

        // Remove existing files and clone
        $cloneCmd = [
            'sh',
            '-c',
            "cd /app && rm -rf .* * 2>/dev/null; git clone --branch {$service->github_branch} --single-branch --depth 1 '{$repoUrl}' . 2>&1"
        ];

        $cloneOutput = $this->docker->execInContainer($service->container_id, $cloneCmd);
        $updateLog('Clone output: ' . ($cloneOutput ?? 'No output'));

        // Get commit info
        $commitCmd = ['sh', '-c', 'cd /app && git log -1 --format="%H|%s" 2>/dev/null'];
        $commitInfo = $this->docker->execInContainer($service->container_id, $commitCmd);

        if ($commitInfo) {
            $parts = explode('|', trim($commitInfo), 2);
            $deployment->commit_hash = $parts[0] ?? null;
            $deployment->commit_message = $parts[1] ?? null;
        }

        // Step 3: Install dependencies
        $deployment->status = 'installing';
        $updateLog('Installing dependencies: ' . $service->install_cmd);

        $installCmd = ['sh', '-c', "cd /app && {$service->install_cmd} 2>&1"];
        $installOutput = $this->docker->execInContainer($service->container_id, $installCmd);
        $updateLog('Install completed');

        // Step 4: Build (if configured)
        if (!empty($service->build_cmd)) {
            $deployment->status = 'building';
            $updateLog('Building application: ' . $service->build_cmd);

            $buildCmd = ['sh', '-c', "cd /app && {$service->build_cmd} 2>&1"];
            $buildOutput = $this->docker->execInContainer($service->container_id, $buildCmd);
            $updateLog('Build completed');
        }

        // Step 5: Restart container to apply changes
        $deployment->status = 'starting';
        $updateLog('Restarting application...');

        $this->docker->restartContainer($service->container_id);

        // Wait a bit for container to start
        sleep(3);

        // Verify container is running
        $info = $this->docker->inspectContainer($service->container_id);
        if ($info && ($info['State']['Running'] ?? false)) {
            $deployment->status = 'completed';
            $updateLog('Deployment completed successfully!');
            $service->status = 'running';
            $service->save();
        } else {
            throw new \Exception('Container failed to start after deployment');
        }

        $deployment->completed_at = date('Y-m-d H:i:s');
        $deployment->save();
    }

    /**
     * Validate Git URL
     */
    private function isValidGitUrl(string $url): bool
    {
        // Support HTTPS and SSH Git URLs
        $patterns = [
            '/^https:\/\/github\.com\/[\w\-]+\/[\w\-\.]+\.git$/',
            '/^https:\/\/github\.com\/[\w\-]+\/[\w\-\.]+$/',
            '/^https:\/\/gitlab\.com\/[\w\-]+\/[\w\-\.]+\.git$/',
            '/^https:\/\/gitlab\.com\/[\w\-]+\/[\w\-\.]+$/',
            '/^https:\/\/bitbucket\.org\/[\w\-]+\/[\w\-\.]+\.git$/',
            '/^git@github\.com:[\w\-]+\/[\w\-\.]+\.git$/',
            '/^git@gitlab\.com:[\w\-]+\/[\w\-\.]+\.git$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        // Generic HTTPS git URL
        if (filter_var($url, FILTER_VALIDATE_URL) && strpos($url, 'https://') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Encrypt sensitive data
     */
    private function encrypt(string $data): string
    {
        $key = $_ENV['APP_SECRET'] ?? 'logicpanel-default-key';
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt sensitive data
     */
    private function decrypt(string $data): ?string
    {
        try {
            $key = $_ENV['APP_SECRET'] ?? 'logicpanel-default-key';
            $decoded = base64_decode($data);
            $iv = substr($decoded, 0, 16);
            $encrypted = substr($decoded, 16);
            return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        } catch (\Exception $e) {
            return null;
        }
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
