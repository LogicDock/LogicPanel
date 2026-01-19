<?php
/**
 * LogicPanel - Dashboard Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\User;
use LogicPanel\Models\Service;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class DashboardController extends BaseController
{
    /**
     * Main dashboard
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        // Get user's services
        $services = Service::where('user_id', $user->id)
            ->with(['primaryDomain', 'databases'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get container stats for each running service
        $docker = new DockerService();
        $serviceStats = [];

        foreach ($services as $service) {
            if ($service->container_id && $service->status === 'running') {
                $stats = $docker->getContainerStats($service->container_id);
                if ($stats) {
                    $serviceStats[$service->id] = $this->calculateStats($stats);
                }
            }
        }

        // Calculate totals
        $totalStats = [
            'cpu' => 0,
            'memory_used' => 0,
            'memory_limit' => 0,
            'disk_used' => 0,
            'disk_limit' => 0,
            'network_rx' => 0,
            'network_tx' => 0
        ];

        foreach ($serviceStats as $stats) {
            $totalStats['cpu'] += $stats['cpu'] ?? 0;
            $totalStats['memory_used'] += $stats['memory_used'] ?? 0;
            $totalStats['memory_limit'] += $stats['memory_limit'] ?? 0;
        }

        return $this->render($response, 'dashboard/index', [
            'title' => 'Dashboard - LogicPanel',
            'services' => $services,
            'serviceStats' => $serviceStats,
            'totalStats' => $totalStats,
            'serviceCount' => $services->count(),
            'runningCount' => $services->where('status', 'running')->count(),
            'stoppedCount' => $services->where('status', 'stopped')->count()
        ]);
    }

    /**
     * User settings page
     */
    public function settings(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        return $this->render($response, 'dashboard/settings', [
            'title' => 'Settings - LogicPanel',
            'user' => $user
        ]);
    }

    /**
     * Update user settings
     */
    public function updateSettings(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Update name
        if (!empty($data['name'])) {
            $user->name = $data['name'];
        }

        // Update email
        if (!empty($data['email']) && $data['email'] !== $user->email) {
            // Check if email is unique
            $exists = User::where('email', $data['email'])->where('id', '!=', $user->id)->exists();
            if ($exists) {
                return $this->render($response, 'dashboard/settings', [
                    'title' => 'Settings - LogicPanel',
                    'user' => $user,
                    'error' => 'Email already in use'
                ]);
            }
            $user->email = $data['email'];
        }

        // Update password
        if (!empty($data['new_password'])) {
            if (strlen($data['new_password']) < 8) {
                return $this->render($response, 'dashboard/settings', [
                    'title' => 'Settings - LogicPanel',
                    'user' => $user,
                    'error' => 'Password must be at least 8 characters'
                ]);
            }

            // Verify current password
            if (!$user->verifyPassword($data['current_password'] ?? '')) {
                return $this->render($response, 'dashboard/settings', [
                    'title' => 'Settings - LogicPanel',
                    'user' => $user,
                    'error' => 'Current password is incorrect'
                ]);
            }

            $user->password = $data['new_password'];
        }

        $user->save();

        // Update session
        $_SESSION['user_name'] = $user->name;

        return $this->render($response, 'dashboard/settings', [
            'title' => 'Settings - LogicPanel',
            'user' => $user,
            'success' => 'Settings updated successfully'
        ]);
    }

    /**
     * Update theme preference
     */
    public function updateTheme(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $theme = $data['theme'] ?? 'auto';
        if (!in_array($theme, ['light', 'dark', 'auto'])) {
            $theme = 'auto';
        }

        $user->theme = $theme;
        $user->save();

        $_SESSION['theme'] = $theme;

        return $this->jsonResponse($response, [
            'success' => true,
            'theme' => $theme
        ]);
    }

    /**
     * Admin dashboard
     */
    public function adminDashboard(Request $request, Response $response): Response
    {
        // System stats
        $docker = new DockerService();
        $dockerInfo = $docker->getInfo();
        $dockerConnected = $docker->ping();

        // Count statistics
        $stats = [
            'users' => User::count(),
            'services' => Service::count(),
            'databases' => DB::table('databases')->count(),
            'domains' => DB::table('domains')->count()
        ];

        // Recent activity
        $recentActivity = DB::table('activity_log')
            ->leftJoin('users', 'activity_log.user_id', '=', 'users.id')
            ->select('activity_log.*', 'users.username', 'users.name as user_name')
            ->orderBy('activity_log.created_at', 'desc')
            ->limit(20)
            ->get();

        return $this->render($response, 'admin/index', [
            'title' => 'Admin Dashboard - LogicPanel',
            'stats' => $stats,
            'dockerConnected' => $dockerConnected,
            'dockerInfo' => $dockerInfo,
            'recentActivity' => $recentActivity
        ]);
    }

    /**
     * Admin users list
     */
    public function adminUsers(Request $request, Response $response): Response
    {
        $users = User::withCount('services')->orderBy('created_at', 'desc')->get();

        return $this->render($response, 'admin/users', [
            'title' => 'Users - LogicPanel Admin',
            'users' => $users
        ]);
    }

    /**
     * Admin services list
     */
    public function adminServices(Request $request, Response $response): Response
    {
        $services = Service::with(['user', 'primaryDomain'])
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->render($response, 'admin/services', [
            'title' => 'Services - LogicPanel Admin',
            'services' => $services
        ]);
    }

    /**
     * Admin settings
     */
    public function adminSettings(Request $request, Response $response): Response
    {
        $settings = DB::table('settings')->get()->keyBy('key');

        // Get API keys
        $apiKeys = DB::table('api_keys')->get();

        return $this->render($response, 'admin/settings', [
            'title' => 'Settings - LogicPanel Admin',
            'settings' => $settings,
            'apiKeys' => $apiKeys
        ]);
    }

    /**
     * Save admin settings
     */
    public function saveAdminSettings(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();

        foreach ($data as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8);
                DB::table('settings')
                    ->updateOrInsert(
                        ['key' => $settingKey],
                        ['value' => $value, 'updated_at' => date('Y-m-d H:i:s')]
                    );
            }
        }

        return $this->redirect($response, '/admin/settings?saved=1');
    }

    /**
     * Calculate container stats from Docker API response
     */
    private function calculateStats(array $stats): array
    {
        // CPU calculation
        $cpuDelta = ($stats['cpu_stats']['cpu_usage']['total_usage'] ?? 0) -
            ($stats['precpu_stats']['cpu_usage']['total_usage'] ?? 0);
        $systemDelta = ($stats['cpu_stats']['system_cpu_usage'] ?? 0) -
            ($stats['precpu_stats']['system_cpu_usage'] ?? 0);
        $cpuCount = $stats['cpu_stats']['online_cpus'] ?? 1;

        $cpuPercent = 0;
        if ($systemDelta > 0 && $cpuDelta > 0) {
            $cpuPercent = ($cpuDelta / $systemDelta) * $cpuCount * 100;
        }

        // Memory calculation
        $memoryUsage = $stats['memory_stats']['usage'] ?? 0;
        $memoryLimit = $stats['memory_stats']['limit'] ?? 0;
        $memoryPercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;

        // Network calculation
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
            'network_rx' => $networkRx,
            'network_tx' => $networkTx
        ];
    }

    // ============================================
    // Package Management Methods
    // ============================================

    /**
     * Admin Packages List
     */
    public function adminPackages(Request $request, Response $response): Response
    {
        $packages = \LogicPanel\Models\Package::withCount('services')
            ->orderBy('sort_order')
            ->get();

        return $this->render($response, 'admin/packages', [
            'title' => 'Packages - Admin',
            'packages' => $packages
        ]);
    }

    /**
     * Create Package
     */
    public function createPackage(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate name is unique
        $existing = \LogicPanel\Models\Package::where('name', $data['name'])->first();
        if ($existing) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package name already exists'], 400);
        }

        $package = new \LogicPanel\Models\Package();
        $package->name = $data['name'];
        $package->display_name = $data['display_name'];
        $package->description = $data['description'] ?? null;
        $package->memory_limit = $data['memory_limit'] ?? 512;
        $package->cpu_limit = $data['cpu_limit'] ?? 0.5;
        $package->disk_limit = $data['disk_limit'] ?? 5120;
        $package->bandwidth_limit = $data['bandwidth_limit'] ?? 0;
        $package->max_domains = $data['max_domains'] ?? 3;
        $package->max_databases = $data['max_databases'] ?? 1;
        $package->max_backups = $data['max_backups'] ?? 3;
        $package->max_deployments_per_day = $data['max_deployments_per_day'] ?? 10;
        $package->allow_ssh = $data['allow_ssh'] ?? true;
        $package->allow_git_deploy = $data['allow_git_deploy'] ?? true;
        $package->is_active = $data['is_active'] ?? true;
        $package->sort_order = \LogicPanel\Models\Package::max('sort_order') + 1;
        $package->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Package created successfully',
            'package' => $package
        ]);
    }

    /**
     * Update Package
     */
    public function updatePackage(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $packageId = (int) $args['id'];

        $package = \LogicPanel\Models\Package::find($packageId);
        if (!$package) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package not found'], 404);
        }

        // Check if name changed and is unique
        if ($data['name'] !== $package->name) {
            $existing = \LogicPanel\Models\Package::where('name', $data['name'])->first();
            if ($existing) {
                return $this->jsonResponse($response, ['success' => false, 'error' => 'Package name already exists'], 400);
            }
        }

        $package->name = $data['name'];
        $package->display_name = $data['display_name'];
        $package->description = $data['description'] ?? null;
        $package->memory_limit = $data['memory_limit'] ?? 512;
        $package->cpu_limit = $data['cpu_limit'] ?? 0.5;
        $package->disk_limit = $data['disk_limit'] ?? 5120;
        $package->bandwidth_limit = $data['bandwidth_limit'] ?? 0;
        $package->max_domains = $data['max_domains'] ?? 3;
        $package->max_databases = $data['max_databases'] ?? 1;
        $package->max_backups = $data['max_backups'] ?? 3;
        $package->max_deployments_per_day = $data['max_deployments_per_day'] ?? 10;
        $package->allow_ssh = $data['allow_ssh'] ?? true;
        $package->allow_git_deploy = $data['allow_git_deploy'] ?? true;
        $package->is_active = $data['is_active'] ?? true;
        $package->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Package updated successfully',
            'package' => $package
        ]);
    }

    /**
     * Delete Package
     */
    public function deletePackage(Request $request, Response $response, array $args): Response
    {
        $packageId = (int) $args['id'];

        $package = \LogicPanel\Models\Package::find($packageId);
        if (!$package) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package not found'], 404);
        }

        // Don't delete if services are using it
        $servicesCount = Service::where('package_id', $packageId)->count();
        if ($servicesCount > 0) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => "Cannot delete: {$servicesCount} services are using this package"
            ], 400);
        }

        $package->delete();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Package deleted successfully'
        ]);
    }

    // ============================================
    // API Keys Management Methods
    // ============================================

    /**
     * Admin API Keys List
     */
    public function adminApiKeys(Request $request, Response $response): Response
    {
        $apiKeys = DB::table('api_keys')->orderBy('created_at', 'desc')->get();

        return $this->render($response, 'admin/api-keys', [
            'title' => 'API Keys - Admin',
            'current_page' => 'apikeys',
            'apiKeys' => $apiKeys
        ]);
    }

    /**
     * Create API Key
     */
    public function createApiKey(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Generate secure keys
        $apiKey = 'lp_' . bin2hex(random_bytes(16));
        $apiSecret = bin2hex(random_bytes(32));

        // Insert into database
        DB::table('api_keys')->insert([
            'name' => $data['name'] ?? 'Unnamed Key',
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'permissions' => json_encode($data['permissions'] ?? []),
            'is_active' => true,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'API Key created successfully',
            'api_key' => $apiKey,
            'api_secret' => $apiSecret
        ]);
    }

    /**
     * Toggle API Key active status
     */
    public function toggleApiKey(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $keyId = (int) $args['id'];

        $affected = DB::table('api_keys')
            ->where('id', $keyId)
            ->update([
                'is_active' => $data['is_active'] ?? false,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

        if ($affected) {
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'API Key updated'
            ]);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'API Key not found'
        ], 404);
    }

    /**
     * Delete API Key
     */
    public function deleteApiKey(Request $request, Response $response, array $args): Response
    {
        $keyId = (int) $args['id'];

        $affected = DB::table('api_keys')->where('id', $keyId)->delete();

        if ($affected) {
            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'API Key deleted'
            ]);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'API Key not found'
        ], 404);
    }

    // ============================================
    // Reseller Package Management Methods (Admin creates these for resellers)
    // ============================================

    /**
     * Admin Reseller Packages List
     */
    public function adminResellerPackages(Request $request, Response $response): Response
    {
        $packages = \LogicPanel\Models\ResellerPackage::withCount('users')
            ->orderBy('sort_order')
            ->get();

        return $this->render($response, 'admin/reseller-packages', [
            'title' => 'Reseller Plans - Admin',
            'current_page' => 'reseller_packages_admin',
            'packages' => $packages
        ]);
    }

    /**
     * Create Reseller Package
     */
    public function createResellerPackage(Request $request, Response $response): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);

        // Validate name is unique
        $existing = \LogicPanel\Models\ResellerPackage::where('name', $data['name'])->first();
        if ($existing) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package name already exists'], 400);
        }

        $package = new \LogicPanel\Models\ResellerPackage();
        $package->name = $data['name'];
        $package->display_name = $data['display_name'];
        $package->description = $data['description'] ?? null;
        $package->max_users = $data['max_users'] ?? 10;
        $package->max_services = $data['max_services'] ?? 50;
        $package->max_disk_gb = $data['max_disk_gb'] ?? 100;
        $package->max_bandwidth_gb = $data['max_bandwidth_gb'] ?? 1000;
        $package->can_create_packages = $data['can_create_packages'] ?? true;
        $package->is_active = $data['is_active'] ?? true;
        $package->sort_order = \LogicPanel\Models\ResellerPackage::max('sort_order') + 1;
        $package->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Reseller package created successfully',
            'package' => $package
        ]);
    }

    /**
     * Update Reseller Package
     */
    public function updateResellerPackage(Request $request, Response $response, array $args): Response
    {
        $data = json_decode($request->getBody()->getContents(), true);
        $packageId = (int) $args['id'];

        $package = \LogicPanel\Models\ResellerPackage::find($packageId);
        if (!$package) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package not found'], 404);
        }

        // Check if name changed and is unique
        if (isset($data['name']) && $data['name'] !== $package->name) {
            $existing = \LogicPanel\Models\ResellerPackage::where('name', $data['name'])->first();
            if ($existing) {
                return $this->jsonResponse($response, ['success' => false, 'error' => 'Package name already exists'], 400);
            }
            $package->name = $data['name'];
        }

        if (isset($data['display_name']))
            $package->display_name = $data['display_name'];
        if (isset($data['description']))
            $package->description = $data['description'];
        if (isset($data['max_users']))
            $package->max_users = $data['max_users'];
        if (isset($data['max_services']))
            $package->max_services = $data['max_services'];
        if (isset($data['max_disk_gb']))
            $package->max_disk_gb = $data['max_disk_gb'];
        if (isset($data['max_bandwidth_gb']))
            $package->max_bandwidth_gb = $data['max_bandwidth_gb'];
        if (isset($data['can_create_packages']))
            $package->can_create_packages = $data['can_create_packages'];
        if (isset($data['is_active']))
            $package->is_active = $data['is_active'];
        $package->save();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Reseller package updated successfully',
            'package' => $package
        ]);
    }

    /**
     * Delete Reseller Package
     */
    public function deleteResellerPackage(Request $request, Response $response, array $args): Response
    {
        $packageId = (int) $args['id'];

        $package = \LogicPanel\Models\ResellerPackage::find($packageId);
        if (!$package) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Package not found'], 404);
        }

        // Don't delete if resellers are using it
        $usersCount = \LogicPanel\Models\User::where('reseller_package_id', $packageId)->count();
        if ($usersCount > 0) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => "Cannot delete: {$usersCount} resellers are using this package"
            ], 400);
        }

        $package->delete();

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Reseller package deleted successfully'
        ]);
    }
}

