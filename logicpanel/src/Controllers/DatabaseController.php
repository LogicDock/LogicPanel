<?php
/**
 * LogicPanel - Database Controller
 * Manages MariaDB, PostgreSQL, MongoDB containers
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Models\Database;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * List all databases
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $services = Service::where('user_id', $user->id)
            ->with('databases')
            ->get();

        $allDatabases = [];
        foreach ($services as $service) {
            foreach ($service->databases as $db) {
                $db->service_name = $service->name;
                // Decrypt password for display
                $db->db_password_decrypted = $this->decrypt($db->db_password);
                $allDatabases[] = $db;
            }
        }

        return $this->render($response, 'databases/index', [
            'title' => 'Databases - LogicPanel',
            'databases' => $allDatabases,
            'services' => $services
        ]);
    }

    /**
     * Show databases for a specific service
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->with('databases')
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        // Get live status for each database container
        foreach ($service->databases as $db) {
            if ($db->container_id) {
                $info = $this->docker->inspectContainer($db->container_id);
                if ($info) {
                    $db->live_status = $info['State']['Status'] ?? 'unknown';
                }
            }
        }

        return $this->render($response, 'databases/show', [
            'title' => 'Databases - ' . $service->name,
            'service' => $service,
            'databases' => $service->databases
        ]);
    }

    /**
     * Create a new database (using shared database containers)
     */
    public function create(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $serviceId = (int) ($data['service_id'] ?? 0);

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        // Validate type
        $type = $data['type'] ?? '';
        if (!in_array($type, ['mariadb', 'postgresql', 'mongodb'])) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid database type'], 400);
        }

        // Check limit
        $maxDatabases = $this->getSetting('max_databases_per_service', 3);
        $currentCount = Database::where('service_id', $serviceId)->count();
        if ($currentCount >= $maxDatabases) {
            return $this->jsonResponse($response, ['success' => false, 'error' => "Maximum {$maxDatabases} databases allowed per service"], 400);
        }

        // Check if same type already exists
        $existingType = Database::where('service_id', $serviceId)
            ->where('type', $type)
            ->exists();
        if ($existingType) {
            return $this->jsonResponse($response, ['success' => false, 'error' => "A {$type} database already exists for this service"], 400);
        }

        // Generate unique credentials
        $serviceName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($service->name));
        $uniqueId = substr(md5($service->id . time()), 0, 6);

        // Ensure db_name and db_user are always set
        $dbName = !empty($data['db_name']) ? $data['db_name'] : $serviceName . '_db';
        $dbUser = !empty($data['db_user']) ? $data['db_user'] : $serviceName . '_u' . $uniqueId;
        $dbPassword = $this->generatePassword();

        // Get shared container name based on type
        $sharedContainerName = $this->getSharedContainerName($type);

        // Check if shared container exists
        $containerInfo = $this->docker->inspectContainer($sharedContainerName);
        if (!$containerInfo) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => "Shared {$type} container not found. Please run setup first."
            ], 500);
        }

        // Create database and user in shared container
        $createResult = $this->createDatabaseInSharedContainer($type, $sharedContainerName, $dbName, $dbUser, $dbPassword);

        if (!$createResult['success']) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to create database: ' . $createResult['error']
            ], 500);
        }

        // Get port mapping
        $port = $this->getDefaultPort($type);

        // Save to database
        $database = new Database();
        $database->service_id = $serviceId;
        $database->container_id = $sharedContainerName; // Use container name as reference
        $database->container_name = $sharedContainerName;
        $database->type = $type;
        $database->db_name = $dbName;
        $database->db_user = $dbUser;
        $database->db_password = $this->encrypt($dbPassword);
        $database->root_password = ''; // No root password for shared, not needed per-user
        $database->port = $port;
        $database->status = 'running';
        $database->save();

        // Auto-inject database env vars to service container
        $this->injectDatabaseEnvVars($service, $database, $dbPassword);

        $this->logActivity($user->id, $serviceId, 'database_create', "Created {$type} database: {$dbName}");

        // Redirect back to databases page
        return $response
            ->withHeader('Location', '/databases?service=' . $serviceId)
            ->withStatus(302);
    }

    /**
     * Get shared container name for database type
     */
    private function getSharedContainerName(string $type): string
    {
        $containers = [
            'mariadb' => $_ENV['SHARED_MARIADB_CONTAINER'] ?? 'logicpanel-mariadb-shared',
            'postgresql' => $_ENV['SHARED_POSTGRES_CONTAINER'] ?? 'logicpanel-postgres-shared',
            'mongodb' => $_ENV['SHARED_MONGO_CONTAINER'] ?? 'logicpanel-mongo-shared',
        ];
        return $containers[$type] ?? 'logicpanel-mariadb-shared';
    }

    /**
     * Create database and user in shared container with proper isolation
     */
    private function createDatabaseInSharedContainer(string $type, string $containerName, string $dbName, string $dbUser, string $dbPassword): array
    {
        $rootPassword = $_ENV['SHARED_DB_ROOT_PASSWORD'] ?? 'logicpanel_root_secret';

        if ($type === 'mariadb') {
            // MariaDB: Create database, user, and grant with connection limit
            $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; ";
            $sql .= "CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'; ";
            $sql .= "GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'; ";
            $sql .= "ALTER USER '{$dbUser}'@'%' WITH MAX_USER_CONNECTIONS 10; "; // Bad neighbor protection
            $sql .= "FLUSH PRIVILEGES;";

            $cmd = ['mysql', '-u', 'root', "-p{$rootPassword}", '-e', $sql];

        } elseif ($type === 'postgresql') {
            // PostgreSQL: Create user and database
            $createUser = "CREATE USER {$dbUser} WITH PASSWORD '{$dbPassword}' CONNECTION LIMIT 10;";
            $createDb = "CREATE DATABASE {$dbName} OWNER {$dbUser};";
            $grantPrivs = "GRANT ALL PRIVILEGES ON DATABASE {$dbName} TO {$dbUser};";

            // Execute each command separately for PostgreSQL
            $this->docker->execInContainer($containerName, ['psql', '-U', 'postgres', '-c', $createUser]);
            $this->docker->execInContainer($containerName, ['psql', '-U', 'postgres', '-c', $createDb]);
            $result = $this->docker->execInContainer($containerName, ['psql', '-U', 'postgres', '-c', $grantPrivs]);

            return ['success' => true, 'output' => $result];

        } elseif ($type === 'mongodb') {
            // MongoDB: Create user with specific database access
            $mongoCmd = "db.getSiblingDB('{$dbName}').createUser({user: '{$dbUser}', pwd: '{$dbPassword}', roles: [{role: 'dbOwner', db: '{$dbName}'}]})";
            $cmd = ['mongosh', '-u', 'root', '-p', $rootPassword, '--authenticationDatabase', 'admin', '--eval', $mongoCmd];

        } else {
            return ['success' => false, 'error' => 'Unsupported database type'];
        }

        $result = $this->docker->execInContainer($containerName, $cmd);

        // Check for errors in output
        if ($result === null) {
            return ['success' => false, 'error' => 'Container command failed'];
        }

        if (is_string($result) && (stripos($result, 'error') !== false && stripos($result, 'already exists') === false)) {
            return ['success' => false, 'error' => $result];
        }

        return ['success' => true, 'output' => $result];
    }

    /**
     * Auto-inject database credentials into service's env vars
     */
    private function injectDatabaseEnvVars(Service $service, Database $database, string $password): void
    {
        // Get current env vars
        $envVars = [];
        if (!empty($service->env_vars)) {
            $envVars = is_string($service->env_vars) ? json_decode($service->env_vars, true) : $service->env_vars;
        }
        if (!is_array($envVars)) {
            $envVars = [];
        }

        $containerName = $database->container_name;
        $port = $this->getDefaultPort($database->type);

        // Add individual env vars based on database type
        $prefix = strtoupper($database->type);
        if ($database->type === 'mariadb') {
            $prefix = 'MYSQL';
        } elseif ($database->type === 'postgresql') {
            $prefix = 'POSTGRES';
        } elseif ($database->type === 'mongodb') {
            $prefix = 'MONGO';
        }

        // Individual variables format
        $envVars["DB_HOST"] = $containerName;
        $envVars["DB_PORT"] = (string) $port;
        $envVars["DB_NAME"] = $database->db_name;
        $envVars["DB_USER"] = $database->db_user;
        $envVars["DB_PASSWORD"] = $password;

        // Type-specific prefix
        $envVars["{$prefix}_HOST"] = $containerName;
        $envVars["{$prefix}_PORT"] = (string) $port;
        $envVars["{$prefix}_DATABASE"] = $database->db_name;
        $envVars["{$prefix}_USER"] = $database->db_user;
        $envVars["{$prefix}_PASSWORD"] = $password;

        // Connection string format (DATABASE_URL)
        $connectionString = $this->getConnectionString($database->type, $containerName, $database->db_name, $database->db_user, $password);
        $envVars["DATABASE_URL"] = $connectionString;

        // Type-specific connection string
        $envVars["{$prefix}_URL"] = $connectionString;

        // Update service env vars
        $service->env_vars = json_encode($envVars);
        $service->save();

        // Restart service container to apply new env vars (if running)
        if ($service->container_id && $service->status === 'running') {
            // Note: For env vars to take effect, container needs to be recreated
            // For now, just update the stored vars - user can restart manually
        }
    }

    /**
     * Delete a database
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $dbId = (int) $args['id'];

        $database = Database::with('service')->find($dbId);

        if (!$database || $database->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Database not found'], 404);
        }

        $containerName = $database->container_name ?? $database->container_id;
        $rootPassword = $_ENV['SHARED_DB_ROOT_PASSWORD'] ?? 'logicpanel_root_secret';

        // Drop database and user from shared container (don't remove container!)
        if ($database->type === 'mariadb') {
            $sql = "DROP DATABASE IF EXISTS `{$database->db_name}`; DROP USER IF EXISTS '{$database->db_user}'@'%'; FLUSH PRIVILEGES;";
            $cmd = ['mysql', '-u', 'root', "-p{$rootPassword}", '-e', $sql];
            $this->docker->execInContainer($containerName, $cmd);
        } elseif ($database->type === 'postgresql') {
            // PostgreSQL: Drop database first, then user
            $this->docker->execInContainer($containerName, ['psql', '-U', 'postgres', '-c', "DROP DATABASE IF EXISTS {$database->db_name};"]);
            $this->docker->execInContainer($containerName, ['psql', '-U', 'postgres', '-c', "DROP USER IF EXISTS {$database->db_user};"]);
        } elseif ($database->type === 'mongodb') {
            $mongoCmd = "db.getSiblingDB('{$database->db_name}').dropDatabase()";
            $cmd = ['mongosh', '-u', 'root', '-p', $rootPassword, '--authenticationDatabase', 'admin', '--eval', $mongoCmd];
            $this->docker->execInContainer($containerName, $cmd);
        }

        $this->logActivity($user->id, $database->service_id, 'database_delete', "Deleted {$database->type} database: {$database->db_name}");

        $database->delete();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Database deleted successfully'
        ]);
    }

    /**
     * Get Adminer URL for database management
     */
    public function adminer(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $dbId = (int) $args['id'];

        $database = Database::with('service')->find($dbId);

        if (!$database || $database->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['error' => 'Database not found'], 404);
        }

        // Decrypt password for display
        $database->db_password = $this->decrypt($database->db_password);

        // Redirect to Adminer proxy
        return $response
            ->withHeader('Location', '/adminer/?db=' . $dbId)
            ->withStatus(302);
    }

    /**
     * Add a new database user
     */
    public function addUser(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $dbId = (int) ($data['database_id'] ?? 0);
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $privileges = $data['privileges'] ?? 'ALL';

        if (empty($username) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Username and password are required'], 400);
        }

        $database = Database::with('service')->find($dbId);

        if (!$database || $database->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Database not found'], 404);
        }

        // For now, create user in database container using docker exec
        // This would execute SQL commands via the container
        $containerName = $database->container_name ?? $database->container_id;
        $rootPassword = $_ENV['SHARED_DB_ROOT_PASSWORD'] ?? 'logicpanel_root_secret';

        // Build SQL command based on database type
        if ($database->type === 'mariadb') {
            $sql = "CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY '{$password}'; ";
            $sql .= "GRANT {$privileges} ON `{$database->db_name}`.* TO '{$username}'@'%'; ";
            $sql .= "ALTER USER '{$username}'@'%' WITH MAX_USER_CONNECTIONS 10; "; // Bad neighbor protection
            $sql .= "FLUSH PRIVILEGES;";
            $cmd = ['mysql', '-u', 'root', "-p{$rootPassword}", '-e', $sql];
        } elseif ($database->type === 'postgresql') {
            $sql = "CREATE USER {$username} WITH PASSWORD '{$password}' CONNECTION LIMIT 10; GRANT ALL ON DATABASE {$database->db_name} TO {$username};";
            $cmd = ['psql', '-U', 'postgres', '-c', $sql];
        } else {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'User creation not supported for ' . $database->type], 400);
        }

        $result = $this->docker->execInContainer($containerName, $cmd);

        // Check if command failed
        if ($result === null || (is_string($result) && stripos($result, 'error') !== false)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to create user: ' . ($result ?? 'Container command failed')
            ], 500);
        }

        $this->logActivity($user->id, $database->service_id, 'database_user_add', "Added user {$username} to {$database->db_name}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'User created successfully'
        ]);
    }

    /**
     * Reset database password
     */
    public function resetPassword(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $dbId = (int) $args['id'];

        $database = Database::with('service')->find($dbId);

        if (!$database || $database->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Database not found'], 404);
        }

        // Generate new password
        $newPassword = $this->generatePassword();
        $containerName = $database->container_name ?? $database->container_id;
        $rootPassword = $_ENV['SHARED_DB_ROOT_PASSWORD'] ?? 'logicpanel_root_secret';

        // Update password in database container
        if ($database->type === 'mariadb') {
            $sql = "ALTER USER '{$database->db_user}'@'%' IDENTIFIED BY '{$newPassword}'; FLUSH PRIVILEGES;";
            $cmd = ['mysql', '-u', 'root', "-p{$rootPassword}", '-e', $sql];
        } elseif ($database->type === 'postgresql') {
            $sql = "ALTER USER {$database->db_user} WITH PASSWORD '{$newPassword}';";
            $cmd = ['psql', '-U', 'postgres', '-c', $sql];
        } elseif ($database->type === 'mongodb') {
            $mongoCmd = "db.getSiblingDB('{$database->db_name}').updateUser('{$database->db_user}', {pwd: '{$newPassword}'})";
            $cmd = ['mongosh', '-u', 'root', '-p', $rootPassword, '--authenticationDatabase', 'admin', '--eval', $mongoCmd];
        } else {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Password reset not supported'], 400);
        }

        $result = $this->docker->execInContainer($containerName, $cmd);

        // Check if command failed
        if ($result === null || (is_string($result) && stripos($result, 'error') !== false)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to reset password: ' . ($result ?? 'Container command failed')
            ], 500);
        }

        // Update in our database
        $database->db_password = $this->encrypt($newPassword);
        $database->save();

        $this->logActivity($user->id, $database->service_id, 'database_password_reset', "Reset password for {$database->db_name}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Password reset successfully',
            'password' => $newPassword
        ]);
    }

    /**
     * Get Adminer driver type
     */
    private function getAdminerType(string $type): string
    {
        switch ($type) {
            case 'mariadb':
                return 'mysql';
            case 'postgresql':
                return 'pgsql';
            case 'mongodb':
                return 'mongo';
            default:
                return 'mysql';
        }
    }

    /**
     * Get default port for database type
     */
    private function getDefaultPort(string $type): int
    {
        switch ($type) {
            case 'mariadb':
                return 3306;
            case 'postgresql':
                return 5432;
            case 'mongodb':
                return 27017;
            default:
                return 0;
        }
    }

    /**
     * Get connection string
     */
    private function getConnectionString(string $type, string $host, string $db, string $user, string $pass): string
    {
        // URL-encode credentials for special characters
        $encodedUser = rawurlencode($user);
        $encodedPass = rawurlencode($pass);

        switch ($type) {
            case 'mariadb':
                return "mysql://{$encodedUser}:{$encodedPass}@{$host}:3306/{$db}";
            case 'postgresql':
                return "postgresql://{$encodedUser}:{$encodedPass}@{$host}:5432/{$db}";
            case 'mongodb':
                // authSource is required - user is created in the specific database, not admin
                return "mongodb://{$encodedUser}:{$encodedPass}@{$host}:27017/{$db}?authSource={$db}";
            default:
                return '';
        }
    }

    /**
     * Generate secure password
     */
    private function generatePassword(int $length = 24): string
    {
        return bin2hex(random_bytes($length / 2));
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
    private function decrypt(string $data): string
    {
        $key = $_ENV['APP_SECRET'] ?? 'logicpanel-default-key';
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Get setting value
     */
    private function getSetting(string $key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        return $setting ? $setting->value : $default;
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
