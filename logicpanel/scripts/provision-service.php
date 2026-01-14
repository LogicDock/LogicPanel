<?php
/**
 * LogicPanel - Manual Service Provisioning Script
 * 
 * Run on VPS: docker exec logicpanel php /var/www/html/scripts/provision-service.php <service_id>
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment
$dotenv = new Symfony\Component\Dotenv\Dotenv();
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv->load(__DIR__ . '/../.env');
}

// Database connection
$capsule = new Illuminate\Database\Capsule\Manager();
$capsule->addConnection([
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? 'logicpanel-db',
    'database' => $_ENV['DB_DATABASE'] ?? 'logicpanel',
    'username' => $_ENV['DB_USERNAME'] ?? 'logicpanel',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => $_ENV['DB_PREFIX'] ?? 'lp_',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

use LogicPanel\Models\Service;
use LogicPanel\Services\DockerService;

// Check arguments
if ($argc < 2) {
    echo "Usage: php provision-service.php <service_id>\n";
    echo "Example: php provision-service.php 2\n";
    exit(1);
}

$serviceId = (int) $argv[1];

echo "=== LogicPanel Service Provisioner ===\n\n";

// Find service
$service = Service::find($serviceId);
if (!$service) {
    echo "ERROR: Service ID {$serviceId} not found.\n";
    exit(1);
}

echo "Service: {$service->name}\n";
echo "Status: {$service->status}\n";
echo "Container ID: " . ($service->container_id ?? 'None') . "\n\n";

// Get primary domain
$domain = $capsule::table('lp_domains')
    ->where('service_id', $serviceId)
    ->where('is_primary', true)
    ->first();

if (!$domain) {
    echo "ERROR: No primary domain found for service.\n";
    exit(1);
}

echo "Domain: {$domain->domain}\n\n";

// Initialize Docker
echo "Connecting to Docker...\n";
$docker = new DockerService();

// Test Docker connection
$info = $docker->getInfo();
if (!$info) {
    echo "ERROR: Failed to connect to Docker. Error: " . $docker->getLastError() . "\n";
    exit(1);
}
echo "Docker connected: {$info['Name']}\n";
echo "Containers: {$info['Containers']}\n\n";

// Check if container already exists
$containerName = "lp_" . $service->name;
$containers = $docker->listContainers(true, ['name' => [$containerName]]);

if ($containers && count($containers) > 0) {
    echo "Container '{$containerName}' already exists.\n";
    $container = $containers[0];
    echo "State: {$container['State']}\n";
    echo "Container ID: {$container['Id']}\n";

    // Update service record
    $service->container_id = $container['Id'];
    $service->container_name = $containerName;
    $service->status = $container['State'] === 'running' ? 'running' : 'stopped';
    $service->save();

    echo "\nService record updated.\n";
    exit(0);
}

echo "Creating new container...\n";

// Build environment variables
$envVars = [];
$packageData = $capsule::table('lp_packages')->where('id', $service->package_id)->first();

// Create container
try {
    $result = $docker->createNodeJsApp([
        'name' => $service->name,
        'domain' => $domain->domain,
        'node_version' => $service->node_version ?? '20',
        'port' => $service->port ?? 3000,
        'env' => $envVars
    ]);

    if ($result) {
        echo "SUCCESS! Container created.\n";
        echo "Container ID: {$result['Id']}\n";

        // Update service
        $service->container_id = $result['Id'];
        $service->container_name = "lp_{$service->name}";
        $service->status = 'running';
        $service->save();

        echo "\nService status updated to 'running'.\n";
    } else {
        echo "ERROR: Failed to create container.\n";
        echo "Docker Error: " . $docker->getLastError() . "\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\nDone.\n";
