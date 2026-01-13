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
     * Create a new database
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

        // Generate credentials
        $dbName = $data['db_name'] ?? strtolower($service->name) . '_db';
        $dbUser = $data['db_user'] ?? strtolower($service->name) . '_user';
        $dbPassword = $this->generatePassword();
        $rootPassword = $this->generatePassword();

        // Create container
        $containerName = "lp_{$service->name}_{$type}";
        $result = $this->docker->createDatabase($type, $service->name, [
            'db_name' => $dbName,
            'db_user' => $dbUser,
            'db_pass' => $dbPassword,
            'root_pass' => $rootPassword
        ]);

        if (!$result) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Failed to create database container: ' . $this->docker->getLastError()
            ], 500);
        }

        // Get port mapping
        $port = $this->getDefaultPort($type);

        // Save to database
        $database = new Database();
        $database->service_id = $serviceId;
        $database->container_id = $result['Id'];
        $database->container_name = $containerName;
        $database->type = $type;
        $database->db_name = $dbName;
        $database->db_user = $dbUser;
        $database->db_password = $this->encrypt($dbPassword);
        $database->root_password = $this->encrypt($rootPassword);
        $database->port = $port;
        $database->status = 'running';
        $database->save();

        $this->logActivity($user->id, $serviceId, 'database_create', "Created {$type} database: {$dbName}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Database created successfully',
            'database' => [
                'id' => $database->id,
                'type' => $type,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPassword, // Only returned once!
                'root_password' => $type !== 'mongodb' ? $rootPassword : null,
                'container_name' => $containerName,
                'connection_string' => $this->getConnectionString($type, $containerName, $dbName, $dbUser, $dbPassword)
            ]
        ]);
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

        // Remove container
        if ($database->container_id) {
            $this->docker->stopContainer($database->container_id);
            $this->docker->removeContainer($database->container_id, true, true);
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

        // Build SQL command based on database type
        if ($database->type === 'mariadb') {
            $sql = "CREATE USER IF NOT EXISTS '{$username}'@'%' IDENTIFIED BY '{$password}'; ";
            $sql .= "GRANT {$privileges} ON {$database->db_name}.* TO '{$username}'@'%'; FLUSH PRIVILEGES;";
            $cmd = ['mysql', '-u', 'root', '-p' . $this->decrypt($database->root_password), '-e', $sql];
        } elseif ($database->type === 'postgresql') {
            $sql = "CREATE USER {$username} WITH PASSWORD '{$password}'; GRANT {$privileges} ON DATABASE {$database->db_name} TO {$username};";
            $cmd = ['psql', '-U', $database->db_user, '-d', $database->db_name, '-c', $sql];
        } else {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'User creation not supported for ' . $database->type], 400);
        }

        $result = $this->docker->execInContainer($containerName, $cmd);

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

        // Update password in database container
        if ($database->type === 'mariadb') {
            $sql = "ALTER USER '{$database->db_user}'@'%' IDENTIFIED BY '{$newPassword}'; FLUSH PRIVILEGES;";
            $cmd = ['mysql', '-u', 'root', '-p' . $this->decrypt($database->root_password), '-e', $sql];
        } elseif ($database->type === 'postgresql') {
            $sql = "ALTER USER {$database->db_user} WITH PASSWORD '{$newPassword}';";
            $cmd = ['psql', '-U', 'postgres', '-c', $sql];
        } elseif ($database->type === 'mongodb') {
            $cmd = ['mongosh', '--eval', "db.changeUserPassword('{$database->db_user}', '{$newPassword}')"];
        } else {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Password reset not supported'], 400);
        }

        $result = $this->docker->execInContainer($containerName, $cmd);

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
        switch ($type) {
            case 'mariadb':
                return "mysql://{$user}:{$pass}@{$host}:3306/{$db}";
            case 'postgresql':
                return "postgresql://{$user}:{$pass}@{$host}:5432/{$db}";
            case 'mongodb':
                return "mongodb://{$user}:{$pass}@{$host}:27017/{$db}";
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
