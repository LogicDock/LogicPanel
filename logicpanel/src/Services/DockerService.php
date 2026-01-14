<?php
/**
 * LogicPanel Docker Service
 * Handles all Docker API communications
 */

namespace LogicPanel\Services;

class DockerService
{
    private string $baseUrl;
    private bool $useTLS = false;
    private string $certPath = '';
    private int $timeout = 30;
    private string $lastError = '';
    private string $proxyNetwork;

    public function __construct()
    {
        $host = $_ENV['DOCKER_HOST'] ?? 'unix';
        $port = $_ENV['DOCKER_PORT'] ?? '2375';
        $this->useTLS = filter_var($_ENV['DOCKER_TLS'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->certPath = $_ENV['DOCKER_CERT_PATH'] ?? '';
        $this->proxyNetwork = $_ENV['NGINX_PROXY_NETWORK'] ?? 'nginx-proxy_web';

        if ($host === 'unix' || $host === 'localhost') {
            $this->baseUrl = 'unix:///var/run/docker.sock';
        } else {
            $protocol = $this->useTLS ? 'https' : 'http';
            $this->baseUrl = "{$protocol}://{$host}:{$port}";
        }
    }

    /**
     * Test connection to Docker daemon
     */
    public function ping(): bool
    {
        try {
            $response = $this->request('GET', '/_ping');
            return $response !== null && isset($response['status']) && $response['status'] === 200;
        } catch (\Exception $e) {
            $this->lastError = 'Connection failed: ' . $e->getMessage();
            return false;
        }
    }

    /**
     * Get last error message
     */
    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Get Docker system info
     */
    public function getInfo(): ?array
    {
        $response = $this->request('GET', '/info');
        if ($response && $response['status'] === 200) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * List all containers
     */
    public function listContainers(bool $all = false, array $filters = []): ?array
    {
        $params = [];
        if ($all) {
            $params[] = 'all=true';
        }
        if (!empty($filters)) {
            $params[] = 'filters=' . urlencode(json_encode($filters));
        }
        $query = !empty($params) ? '?' . implode('&', $params) : '';

        $response = $this->request('GET', "/containers/json{$query}");
        if ($response && $response['status'] === 200) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * Inspect a container
     */
    public function inspectContainer(string $containerId): ?array
    {
        $response = $this->request('GET', "/containers/{$containerId}/json");
        if ($response && $response['status'] === 200) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * Get container stats
     */
    public function getContainerStats(string $containerId): ?array
    {
        $response = $this->request('GET', "/containers/{$containerId}/stats?stream=false");
        if ($response && $response['status'] === 200) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * Get container logs
     */
    public function getContainerLogs(string $containerId, int $tail = 100): ?string
    {
        $response = $this->request('GET', "/containers/{$containerId}/logs?stdout=true&stderr=true&tail={$tail}");
        if ($response && $response['status'] === 200) {
            return $this->cleanDockerLogs($response['body']);
        }
        return null;
    }

    /**
     * Start a container
     */
    public function startContainer(string $containerId): bool
    {
        $response = $this->request('POST', "/containers/{$containerId}/start");
        return $response && ($response['status'] === 204 || $response['status'] === 304);
    }

    /**
     * Stop a container
     */
    public function stopContainer(string $containerId, int $timeout = 10): bool
    {
        $response = $this->request('POST', "/containers/{$containerId}/stop?t={$timeout}");
        return $response && ($response['status'] === 204 || $response['status'] === 304);
    }

    /**
     * Restart a container
     */
    public function restartContainer(string $containerId, int $timeout = 10): bool
    {
        $response = $this->request('POST', "/containers/{$containerId}/restart?t={$timeout}");
        return $response && $response['status'] === 204;
    }

    /**
     * Remove a container
     */
    public function removeContainer(string $containerId, bool $force = false, bool $volumes = false): bool
    {
        $params = [];
        if ($force)
            $params[] = 'force=true';
        if ($volumes)
            $params[] = 'v=true';
        $query = !empty($params) ? '?' . implode('&', $params) : '';

        $response = $this->request('DELETE', "/containers/{$containerId}{$query}");
        return $response && ($response['status'] === 204 || $response['status'] === 404);
    }

    /**
     * Create a container
     */
    public function createContainer(array $config, string $name = ''): ?array
    {
        $query = $name ? "?name={$name}" : '';
        $response = $this->request('POST', "/containers/create{$query}", $config);

        if ($response && $response['status'] === 201) {
            return json_decode($response['body'], true);
        }
        $this->lastError = $response ? $response['body'] : 'Failed to create container';
        return null;
    }

    /**
     * Execute command in container
     */
    public function execInContainer(string $containerId, array $cmd, bool $tty = false): ?string
    {
        // Create exec instance
        $execConfig = [
            'AttachStdout' => true,
            'AttachStderr' => true,
            'Tty' => $tty,
            'Cmd' => $cmd
        ];

        $response = $this->request('POST', "/containers/{$containerId}/exec", $execConfig);
        if (!$response || $response['status'] !== 201) {
            $this->lastError = 'Failed to create exec instance';
            return null;
        }

        $execData = json_decode($response['body'], true);
        $execId = $execData['Id'];

        // Start exec
        $startConfig = ['Detach' => false, 'Tty' => $tty];
        $response = $this->request('POST', "/exec/{$execId}/start", $startConfig);

        if ($response && $response['status'] === 200) {
            return $this->cleanDockerLogs($response['body']);
        }
        return null;
    }

    /**
     * Pull an image
     */
    public function pullImage(string $image): bool
    {
        $parts = explode(':', $image);
        $repo = $parts[0];
        $tag = $parts[1] ?? 'latest';

        $response = $this->request('POST', "/images/create?fromImage={$repo}&tag={$tag}", null, 300);
        return $response && $response['status'] === 200;
    }

    /**
     * Create network
     */
    public function createNetwork(string $name, array $config = []): ?array
    {
        $data = array_merge(['Name' => $name], $config);
        $response = $this->request('POST', '/networks/create', $data);

        if ($response && $response['status'] === 201) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * Connect container to network
     */
    public function connectToNetwork(string $networkId, string $containerId): bool
    {
        $data = ['Container' => $containerId];
        $response = $this->request('POST', "/networks/{$networkId}/connect", $data);
        return $response && $response['status'] === 200;
    }

    /**
     * Create volume
     */
    public function createVolume(string $name, array $labels = []): ?array
    {
        $data = ['Name' => $name, 'Labels' => $labels];
        $response = $this->request('POST', '/volumes/create', $data);

        if ($response && $response['status'] === 201) {
            return json_decode($response['body'], true);
        }
        return null;
    }

    /**
     * Remove volume
     */
    public function removeVolume(string $name, bool $force = false): bool
    {
        $query = $force ? '?force=true' : '';
        $response = $this->request('DELETE', "/volumes/{$name}{$query}");
        return $response && ($response['status'] === 204 || $response['status'] === 404);
    }

    /**
     * Create a Node.js application container with NGINX proxy
     */
    public function createNodeJsApp(array $config): ?array
    {
        $appName = $config['name'];
        $domain = $config['domain'];
        $nodeVersion = $config['node_version'] ?? '20';
        $port = $config['port'] ?? 3000;
        $envVars = $config['env'] ?? [];

        // Pull Node.js image first
        $image = "node:{$nodeVersion}-alpine";
        $this->pullImage($image);

        // Create volume for app data
        $volumeName = "lp_{$appName}_data";
        $this->createVolume($volumeName, ['logicpanel.app' => $appName]);

        // Build environment variables
        $env = array_merge([
            "VIRTUAL_HOST={$domain}",
            "LETSENCRYPT_HOST={$domain}",
            "VIRTUAL_PORT={$port}",
            "NODE_ENV=production"
        ], $envVars);

        // Container configuration
        $containerConfig = [
            'Image' => "node:{$nodeVersion}-alpine",
            'Env' => $env,
            'Cmd' => ['sh', '-c', 'tail -f /dev/null'],  // Keep container running
            'Tty' => true,
            'ExposedPorts' => [
                "{$port}/tcp" => (object) []
            ],
            'HostConfig' => [
                'Binds' => [
                    "{$volumeName}:/app"
                ],
                'RestartPolicy' => [
                    'Name' => 'unless-stopped'
                ],
                'NetworkMode' => $this->proxyNetwork
            ],
            'WorkingDir' => '/app',
            'Labels' => [
                'logicpanel.managed' => 'true',
                'logicpanel.app' => $appName,
                'logicpanel.domain' => $domain
            ]
        ];

        // Create container
        $result = $this->createContainer($containerConfig, "lp_{$appName}");

        if ($result) {
            // Start the container
            $this->startContainer($result['Id']);
        }

        return $result;
    }

    /**
     * Create database container (MariaDB, PostgreSQL, or MongoDB)
     */
    public function createDatabase(string $type, string $appName, array $config): ?array
    {
        $dbName = $config['db_name'] ?? 'app_db';
        $dbUser = $config['db_user'] ?? 'app_user';
        $dbPass = $config['db_pass'] ?? bin2hex(random_bytes(16));
        $rootPass = $config['root_pass'] ?? bin2hex(random_bytes(16));

        $containerName = "lp_{$appName}_{$type}";
        $volumeName = "lp_{$appName}_{$type}_data";

        // Determine image based on type and pull it
        $imageMap = [
            'mariadb' => 'mariadb:10.11',
            'postgresql' => 'postgres:16-alpine',
            'mongodb' => 'mongo:7'
        ];

        $image = $imageMap[$type] ?? null;
        if (!$image) {
            $this->lastError = 'Invalid database type';
            return null;
        }

        // Pull image first
        $this->pullImage($image);

        // Create volume
        $this->createVolume($volumeName);

        switch ($type) {
            case 'mariadb':
                $containerConfig = [
                    'Image' => 'mariadb:10.11',
                    'Env' => [
                        "MYSQL_ROOT_PASSWORD={$rootPass}",
                        "MYSQL_DATABASE={$dbName}",
                        "MYSQL_USER={$dbUser}",
                        "MYSQL_PASSWORD={$dbPass}"
                    ],
                    'HostConfig' => [
                        'Binds' => ["{$volumeName}:/var/lib/mysql"],
                        'RestartPolicy' => ['Name' => 'unless-stopped']
                    ],
                    'Labels' => [
                        'logicpanel.managed' => 'true',
                        'logicpanel.db' => $type,
                        'logicpanel.app' => $appName
                    ]
                ];
                break;

            case 'postgresql':
                $containerConfig = [
                    'Image' => 'postgres:16-alpine',
                    'Env' => [
                        "POSTGRES_DB={$dbName}",
                        "POSTGRES_USER={$dbUser}",
                        "POSTGRES_PASSWORD={$dbPass}"
                    ],
                    'HostConfig' => [
                        'Binds' => ["{$volumeName}:/var/lib/postgresql/data"],
                        'RestartPolicy' => ['Name' => 'unless-stopped']
                    ],
                    'Labels' => [
                        'logicpanel.managed' => 'true',
                        'logicpanel.db' => $type,
                        'logicpanel.app' => $appName
                    ]
                ];
                break;

            case 'mongodb':
                $containerConfig = [
                    'Image' => 'mongo:7',
                    'Env' => [
                        "MONGO_INITDB_ROOT_USERNAME={$dbUser}",
                        "MONGO_INITDB_ROOT_PASSWORD={$dbPass}",
                        "MONGO_INITDB_DATABASE={$dbName}"
                    ],
                    'HostConfig' => [
                        'Binds' => ["{$volumeName}:/data/db"],
                        'RestartPolicy' => ['Name' => 'unless-stopped']
                    ],
                    'Labels' => [
                        'logicpanel.managed' => 'true',
                        'logicpanel.db' => $type,
                        'logicpanel.app' => $appName
                    ]
                ];
                break;

            default:
                $this->lastError = 'Invalid database type';
                return null;
        }

        $result = $this->createContainer($containerConfig, $containerName);

        if ($result) {
            $this->startContainer($result['Id']);
            $result['credentials'] = [
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_pass' => $dbPass,
                'root_pass' => $rootPass ?? null
            ];
        }

        return $result;
    }

    /**
     * Copy files to container
     */
    public function copyToContainer(string $containerId, string $localPath, string $containerPath): bool
    {
        // Create tar archive
        $tarPath = sys_get_temp_dir() . '/' . uniqid('docker_') . '.tar';
        $phar = new \PharData($tarPath);
        $phar->addFile($localPath, basename($containerPath));

        $tarContent = file_get_contents($tarPath);
        unlink($tarPath);

        $response = $this->request(
            'PUT',
            "/containers/{$containerId}/archive?path=" . dirname($containerPath),
            null,
            30,
            $tarContent,
            'application/x-tar'
        );

        return $response && $response['status'] === 200;
    }

    /**
     * Copy files from container
     */
    public function copyFromContainer(string $containerId, string $containerPath): ?string
    {
        $response = $this->request('GET', "/containers/{$containerId}/archive?path={$containerPath}");

        if ($response && $response['status'] === 200) {
            return $response['body'];
        }
        return null;
    }

    /**
     * Make HTTP request to Docker API
     */
    private function request(
        string $method,
        string $endpoint,
        ?array $data = null,
        int $timeout = null,
        ?string $rawBody = null,
        string $contentType = 'application/json'
    ): ?array {
        $timeout = $timeout ?? $this->timeout;

        if (strpos($this->baseUrl, 'unix://') === 0) {
            return $this->requestUnixSocket($method, $endpoint, $data, $timeout, $rawBody, $contentType);
        } else {
            return $this->requestTCP($method, $endpoint, $data, $timeout, $rawBody, $contentType);
        }
    }

    /**
     * Request via Unix socket using curl
     */
    private function requestUnixSocket(
        string $method,
        string $endpoint,
        ?array $data,
        int $timeout,
        ?string $rawBody,
        string $contentType
    ): ?array {
        $socket = '/var/run/docker.sock';
        if (!file_exists($socket)) {
            $this->lastError = "Docker socket not found: {$socket}";
            return null;
        }

        $ch = curl_init();

        // Use Unix socket
        curl_setopt($ch, CURLOPT_UNIX_SOCKET_PATH, $socket);
        curl_setopt($ch, CURLOPT_URL, "http://localhost" . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $body = $rawBody ?? ($data ? json_encode($data) : null);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: {$contentType}",
                'Content-Length: ' . strlen($body)
            ]);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = "cURL error: {$error}";
            return null;
        }

        return ['status' => $httpCode, 'body' => $response];
    }

    /**
     * Request via TCP (curl)
     */
    private function requestTCP(
        string $method,
        string $endpoint,
        ?array $data,
        int $timeout,
        ?string $rawBody,
        string $contentType
    ): ?array {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $body = $rawBody ?? ($data ? json_encode($data) : null);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: {$contentType}",
                'Content-Length: ' . strlen($body)
            ]);
        }

        if ($this->useTLS && !empty($this->certPath)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->certPath . '/ca.pem');
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath . '/cert.pem');
            curl_setopt($ch, CURLOPT_SSLKEY, $this->certPath . '/key.pem');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            $this->lastError = "cURL error: {$error}";
            return null;
        }

        return ['status' => $httpCode, 'body' => $response];
    }

    /**
     * Parse HTTP response
     */
    private function parseHttpResponse(string $response): ?array
    {
        $parts = explode("\r\n\r\n", $response, 2);
        if (count($parts) < 2) {
            return null;
        }

        $headers = $parts[0];
        $body = $parts[1];

        preg_match("/HTTP\/[\d.]+\s+(\d+)/", $headers, $matches);
        $status = isset($matches[1]) ? (int) $matches[1] : 0;

        return ['status' => $status, 'body' => $body];
    }

    /**
     * Clean Docker log output
     */
    private function cleanDockerLogs(string $raw): string
    {
        $clean = preg_replace("/[\x00-\x08]/", '', $raw);
        return trim($clean);
    }
}
