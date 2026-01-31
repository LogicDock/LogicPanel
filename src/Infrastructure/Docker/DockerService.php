<?php

declare(strict_types=1);

namespace LogicPanel\Infrastructure\Docker;

use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DockerService
{
    private string $network;
    private string $userAppsPath;
    private string $userAppsVolume;

    public function __construct(array $config)
    {
        $this->network = $config['network'];
        $this->userAppsPath = $config['user_apps_path'];
        // Docker Compose prepends project name to volume names
        $this->userAppsVolume = $config['user_apps_volume'] ?? 'logicpanel_logicpanel_user_apps';
    }

    public function createContainer(
        string $name,
        string $image,
        string $domain,  // Changed from int $port to string $domain for Traefik routing
        array $envVars = [],
        float $cpuLimit = 0.5,
        string $memoryLimit = '512M',
        string $appType = 'nodejs',
        string $githubRepo = '',
        string $githubBranch = 'main',
        string $installCommand = '',
        string $buildCommand = '',
        string $startCommand = '',
        string $diskLimit = '1G',
        bool $enableSsl = false,
        string $sslEmail = ''
    ): array {
        $containerName = "logicpanel_app_{$name}";
        $appPath = $this->userAppsPath . "/{$name}";

        // Create app directory
        if (!is_dir($appPath)) {

            $mkdirResult = @mkdir($appPath, 0777, true);

            if (!$mkdirResult) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                throw new \RuntimeException("Failed to create app directory: $appPath - $errorMsg");
            }
        }

        // Initialize App Content
        if (!empty($githubRepo)) {
            $this->cloneGitHubRepo($appPath, $githubRepo, $githubBranch);
        } else {
            // Create starter app (no longer needs port)
            $this->createStarterApp($appPath, $appType, $domain);
        }

        // Get host path from environment variable, or fallback to default relative
        $hostPath = $_ENV['USER_APPS_HOST_PATH'] ?? realpath(__DIR__ . '/../../../storage/user-apps') ?: '/var/www/html/storage/user-apps';

        // Sanitize name for Traefik router name (only alphanumeric and hyphens)
        $routerName = preg_replace('/[^a-zA-Z0-9-]/', '-', $name);

        // Build docker run command with Traefik labels (NO PORT BINDING)
        $command = [
            'docker',
            'run',
            '-d',
            '--name',
            $containerName,
            '--network',
            $this->network,
            // NO -p port binding - Traefik handles routing via labels
            '-v',
            "{$hostPath}:/storage",
            '-w',
            "/storage/{$name}",
            '--cpus',
            (string) $cpuLimit,
            '--memory',
            $memoryLimit,
            '--restart',
            'unless-stopped',
            // NOTE: --storage-opt removed - requires XFS with pquota mount which most servers don't have
            // Nginx Proxy Environment Variables
            "-e",
            "VIRTUAL_HOST={$domain}",
            "-e",
            "VIRTUAL_PORT=3000",
        ];

        if ($enableSsl) {
            $command[] = "-e";
            $command[] = "LETSENCRYPT_HOST={$domain}";
            $command[] = "-e";
            $command[] = "LETSENCRYPT_EMAIL={$sslEmail}";
        }

        // Add environment variables
        $envVars['PORT'] = '3000';  // Standard port inside container
        $envVars['APP_DOMAIN'] = $domain;  // App's assigned domain
        foreach ($envVars as $key => $value) {
            $command[] = '-e';
            $command[] = "{$key}={$value}";
        }

        // Add image and start command based on type
        $command[] = $image;

        if ($appType === 'nodejs') {
            $command[] = 'sh';
            $command[] = '-c';
            // Use custom commands if provided (for GitHub deploys), otherwise default
            // Add --unsafe-perm to fix permission issues with Docker mounted volumes
            $install = !empty($installCommand) ? $installCommand : 'npm install --no-bin-links --no-audit --no-fund --unsafe-perm';
            $build = !empty($buildCommand) ? "{$buildCommand} && " : '';
            $start = !empty($startCommand) ? $startCommand : 'node server.js';
            // Fix permissions first (for Docker mounted volumes), then run install, build, start
            // chmod -R 777 ensures npm can write to node_modules
            $command[] = "chmod -R 777 . 2>/dev/null; {$install}; {$build}{$start} 2>&1 || tail -f /dev/null";
        } else {
            $command[] = 'sh';
            $command[] = '-c';
            // Use custom commands if provided (for GitHub deploys), otherwise default
            $install = !empty($installCommand) ? $installCommand : 'pip install flask';
            $build = !empty($buildCommand) ? "{$buildCommand} && " : '';
            $start = !empty($startCommand) ? $startCommand : 'python app.py';
            // Fix permissions first for mounted volumes
            $command[] = "chmod -R 777 . 2>/dev/null; {$install}; {$build}{$start} 2>&1 || tail -f /dev/null";
        }

        $process = new Process($command);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $containerId = trim($process->getOutput());

        return [
            'container_id' => $containerId,
            'container_name' => $containerName,
            'domain' => $domain,
            'app_path' => $appPath,
        ];
    }

    /**
     * Create starter Hello World app
     */
    private function createStarterApp(string $appPath, string $type, string $domain): void
    {
        // Verify directory exists
        if (!is_dir($appPath)) {
            throw new \RuntimeException("App directory does not exist: $appPath");
        }

        if ($type === 'nodejs') {
            // Create package.json
            $packageJson = [
                'name' => 'logicpanel-app',
                'version' => '1.0.0',
                'main' => 'server.js',
                'scripts' => [
                    'start' => 'node server.js'
                ],
                'dependencies' => new \stdClass()
            ];
            file_put_contents($appPath . '/package.json', json_encode($packageJson, JSON_PRETTY_PRINT));

            // Create server.js - updated to show domain instead of port
            $serverJs = <<<JS
const http = require('http');
const PORT = process.env.PORT || 3000;
const DOMAIN = process.env.APP_DOMAIN || 'localhost';

const server = http.createServer((req, res) => {
    res.writeHead(200, { 'Content-Type': 'text/html' });
    res.end(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Welcome to LogicPanel</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                .container { text-align: center; background: white; padding: 40px 60px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
                h1 { color: #333; margin-bottom: 10px; }
                p { color: #666; }
                .success { color: #28a745; font-weight: bold; }
                .domain { color: #007bff; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🎉 Welcome to LogicPanel!</h1>
                <p class="success">Your Node.js app is running successfully!</p>
                <p>Upload your code via File Manager to replace this page.</p>
                <p><small>Accessible at: <span class="domain">http://\${DOMAIN}</span></small></p>
            </div>
        </body>
        </html>
    `);
});

server.listen(PORT, '0.0.0.0', () => {
    console.log(`Server running at http://0.0.0.0:\${PORT}/ (Domain: \${DOMAIN})`);
});
JS;
            file_put_contents($appPath . '/server.js', $serverJs);

        } else {
            // Python Flask app - updated to show domain instead of port
            $appPy = <<<PY
from flask import Flask
import os

app = Flask(__name__)
PORT = int(os.environ.get('PORT', 3000))
DOMAIN = os.environ.get('APP_DOMAIN', 'localhost')

@app.route('/')
def hello():
    return '''
        <!DOCTYPE html>
        <html>
        <head>
            <title>Welcome to LogicPanel</title>
            <style>
                body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
                .container { text-align: center; background: white; padding: 40px 60px; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); }
                h1 { color: #333; margin-bottom: 10px; }
                p { color: #666; }
                .success { color: #28a745; font-weight: bold; }
                .domain { color: #007bff; font-family: monospace; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🎉 Welcome to LogicPanel!</h1>
                <p class="success">Your Python app is running successfully!</p>
                <p>Upload your code via File Manager to replace this page.</p>
                <p><small>Accessible at: <span class="domain">http://''' + DOMAIN + '''</span></small></p>
            </div>
        </body>
        </html>
    '''

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=PORT)
PY;
            file_put_contents($appPath . '/app.py', $appPy);

            // Create requirements.txt
            file_put_contents($appPath . '/requirements.txt', "flask\n");
        }

        // Fix permissions using PHP native functions
        $this->recursiveChmod($appPath, 0777);
    }

    private function cloneGitHubRepo(string $appPath, string $repoUrl, string $branch = 'main'): void
    {
        // Ensure directory allows cloning (must be empty or we clone to temp and move)
        // Check if directory is empty
        if (is_dir($appPath) && count(scandir($appPath)) > 2) {
            // Directory not empty - clean it
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($appPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                if ($fileinfo->isDir()) {
                    rmdir($fileinfo->getRealPath());
                } else {
                    unlink($fileinfo->getRealPath());
                }
            }
        }

        $command = ['git', 'clone', '-b', $branch, $repoUrl, '.'];

        $process = new Process($command, $appPath);
        $process->setTimeout(300); // 5 minutes for clone
        $process->run();

        if (!$process->isSuccessful()) {
            $output = $process->getErrorOutput();
            throw new \RuntimeException("Failed to clone GitHub repository: $output");
        }

        // Remove .git directory to avoid issues? Or keep it? Keeping it enables updates later.
        // For now keep it.
        $this->recursiveChmod($appPath, 0777);
    }

    private function recursiveChmod($path, $mode)
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $item) {
                chmod($item->getPathname(), $mode);
            }
            chmod($path, $mode);
        } else {
            chmod($path, $mode);
        }
    }

    public function startContainer(string $containerId): void
    {
        $this->runCommand(['docker', 'start', $containerId]);
    }

    public function stopContainer(string $containerId): void
    {
        $this->runCommand(['docker', 'stop', $containerId]);
    }

    public function restartContainer(string $containerId): void
    {
        $this->runCommand(['docker', 'restart', $containerId]);
    }

    public function removeContainer(string $containerId): void
    {
        $this->runCommand(['docker', 'rm', '-f', $containerId]);
    }

    public function getContainerLogs(string $containerId, int $lines = 100): string
    {
        $process = new Process(['docker', 'logs', '--tail', (string) $lines, $containerId]);
        $process->run();

        return $process->getOutput();
    }

    public function getContainerStats(string $containerId): array
    {
        $stats = [
            'cpu' => '0%',
            'memory' => '0 / 0',
            'net' => '0 / 0', // Net IO is hard to get efficiently via exec without docker stats, skipping or leaving 0
            'disk' => 'Unknown'
        ];

        try {
            // 1. Memory Usage
            // Try Cgroup V2 first, then V1
            // Run shell command to trying both paths
            $memCmd = "cat /sys/fs/cgroup/memory.current 2>/dev/null || cat /sys/fs/cgroup/memory/memory.usage_in_bytes 2>/dev/null";
            $memProcess = new Process(['docker', 'exec', $containerId, 'sh', '-c', $memCmd]);
            $memProcess->run();

            $memUsage = 0;
            if ($memProcess->isSuccessful()) {
                $memUsage = (int) trim($memProcess->getOutput());
            }

            // Memory Limit
            $limitCmd = "cat /sys/fs/cgroup/memory.max 2>/dev/null || cat /sys/fs/cgroup/memory/memory.limit_in_bytes 2>/dev/null";
            $limitProcess = new Process(['docker', 'exec', $containerId, 'sh', '-c', $limitCmd]);
            $limitProcess->run();
            $memLimit = 0;
            if ($limitProcess->isSuccessful()) {
                $memLimit = (int) trim($limitProcess->getOutput());
            }

            // Format Memory
            $usageFmt = $this->formatBytes($memUsage);
            // If limit is incredibly huge (unlimited), just show usage
            if ($memLimit > 1000000000000) {
                $limitFmt = '∞';
            } // > 1TB
            else {
                $limitFmt = $this->formatBytes($memLimit);
            }

            $stats['memory'] = "{$usageFmt} / {$limitFmt}";

            // 2. CPU Usage
            // Using 'top' in batch mode to get a snapshot
            // Alpine/BusyBox top format: "CPU:   0% usr   0% sys..."
            $cpuProcess = new Process(['docker', 'exec', $containerId, 'top', '-b', '-n', '1']);
            $cpuProcess->run();

            if ($cpuProcess->isSuccessful()) {
                $output = $cpuProcess->getOutput();
                // Look for line starting with CPU:
                if (preg_match('/CPU:\s+([0-9.]+)%\s+usr\s+([0-9.]+)%\s+sys/', $output, $matches)) {
                    $usr = (float) $matches[1];
                    $sys = (float) $matches[2];
                    $totalCpu = $usr + $sys;
                    $stats['cpu'] = round($totalCpu, 1) . '%';
                }
            }

            // 3. Disk Usage (/storage)
            $diskProcess = new Process(['docker', 'exec', $containerId, 'du', '-sh', '/storage']);
            $diskProcess->run();
            if ($diskProcess->isSuccessful()) {
                $diskOut = trim($diskProcess->getOutput());
                $parts = preg_split('/\s+/', $diskOut);
                $stats['disk'] = $parts[0] ?? '0B';
            }

        } catch (\Exception $e) {
            // Log error if needed, but return zero/safe stats to prevent UI crash
        }

        return $stats;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . '' . $units[$pow];
    }

    public function executeCommand(string $containerId, string $command): string
    {
        $process = new Process(['docker', 'exec', $containerId, 'sh', '-c', $command]);
        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($process->getErrorOutput());
        }

        return $process->getOutput();
    }

    public function isContainerRunning(string $containerId): bool
    {
        $process = new Process(['docker', 'inspect', '-f', '{{.State.Running}}', $containerId]);
        $process->run();

        return trim($process->getOutput()) === 'true';
    }

    public function getAvailablePort($start = 3000, $end = 4000): int
    {
        $logFile = sys_get_temp_dir() . '/logicpanel_docker_debug.log';

        // Get all currently used ports by running containers
        // We need to check what ports are bound on the host
        $process = new Process(['docker', 'ps', '-a', '--format', '{{.Ports}}']);
        $process->run();
        $output = $process->getOutput();

        file_put_contents($logFile, date('Y-m-d H:i:s') . " - getAvailablePort: docker ps output: $output\n", FILE_APPEND);

        $usedPorts = [];
        // Output format examples:
        // 0.0.0.0:8000->80/tcp, [::]:8000->80/tcp
        // 0.0.0.0:3000->3000/tcp

        // Match ports bound to 0.0.0.0
        preg_match_all('/0\.0\.0\.0:(\d+)/', $output, $matches);
        if (!empty($matches[1])) {
            $usedPorts = array_merge($usedPorts, array_map('intval', $matches[1]));
        }

        // Also check database for assigned ports (more reliable)
        try {
            $dbPorts = \LogicPanel\Domain\Service\Service::pluck('port')->toArray();
            $usedPorts = array_merge($usedPorts, array_map('intval', $dbPorts));
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - getAvailablePort: DB check failed: " . $e->getMessage() . "\n", FILE_APPEND);
        }

        $usedPorts = array_unique($usedPorts);
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - getAvailablePort: usedPorts: " . implode(',', $usedPorts) . "\n", FILE_APPEND);

        for ($port = $start; $port < $end; $port++) {
            if (!in_array($port, $usedPorts)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " - getAvailablePort: returning port $port\n", FILE_APPEND);
                return $port;
            }
        }

        throw new \RuntimeException('No available ports found in range ' . $start . '-' . $end);
    }

    private function runCommand(array $command): void
    {
        $process = new Process($command);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }
}
