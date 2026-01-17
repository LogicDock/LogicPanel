<?php
/**
 * LogicPanel - Reseller Controller
 * Handles reseller-specific operations: user management, package management
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\User;
use LogicPanel\Models\Package;
use LogicPanel\Models\Service;
use LogicPanel\Models\ResellerPackage;

class ResellerController extends BaseController
{
    /**
     * Reseller Dashboard
     */
    public function dashboard(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        // Get reseller's users
        $users = User::where('parent_id', $user->id)->get();

        // Get total services count
        $totalServices = 0;
        $activeServices = 0;
        foreach ($users as $u) {
            $totalServices += $u->services()->count();
            $activeServices += $u->services()->where('status', 'running')->count();
        }

        // Get reseller limits
        $resellerPackage = $user->resellerPackage;
        $maxUsers = $resellerPackage ? $resellerPackage->max_users : 0;

        return $this->render($response, 'reseller/dashboard', [
            'title' => 'Reseller Dashboard',
            'current_page' => 'reseller',
            'users' => $users,
            'totalUsers' => $users->count(),
            'maxUsers' => $maxUsers,
            'totalServices' => $totalServices,
            'activeServices' => $activeServices,
            'resellerPackage' => $resellerPackage
        ]);
    }

    /**
     * List reseller's users
     */
    public function listUsers(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $users = User::where('parent_id', $user->id)
            ->with('services')
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->render($response, 'reseller/users', [
            'title' => 'My Users',
            'current_page' => 'reseller_users',
            'users' => $users,
            'canCreateMore' => $user->canCreateMoreUsers()
        ]);
    }

    /**
     * Show create user form
     */
    public function createUserForm(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        if (!$user->canCreateMoreUsers()) {
            return $this->redirect($response, '/reseller/users?error=limit_reached');
        }

        // Get packages created by this reseller
        $packages = Package::where('reseller_id', $user->id)
            ->where('is_active', true)
            ->get();

        return $this->render($response, 'reseller/user-create', [
            'title' => 'Create User',
            'current_page' => 'reseller_users',
            'packages' => $packages
        ]);
    }

    /**
     * Create a new user under this reseller
     */
    public function createUser(Request $request, Response $response): Response
    {
        $reseller = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Validate
        $username = trim($data['username'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';
        $name = trim($data['name'] ?? '');
        $packageId = (int) ($data['package_id'] ?? 0);

        if (empty($username) || empty($email) || empty($password)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Username, email, and password are required'
            ], 400);
        }

        // Validate username format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Username can only contain letters, numbers, and underscores'
            ], 400);
        }

        // Check reseller limits
        if (!$reseller->canCreateMoreUsers()) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'You have reached your user limit'
            ], 403);
        }

        // Check if username or email already exists
        if (User::where('username', $username)->exists()) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Username already exists'
            ], 400);
        }

        if (User::where('email', $email)->exists()) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Email already exists'
            ], 400);
        }

        // Verify package belongs to this reseller (if specified)
        if ($packageId > 0) {
            $package = Package::where('id', $packageId)
                ->where('reseller_id', $reseller->id)
                ->first();
            if (!$package) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'Invalid package selected'
                ], 400);
            }
        }

        // Create user
        $newUser = new User();
        $newUser->username = $username;
        $newUser->email = $email;
        $newUser->password = $password; // Will be hashed by setPasswordAttribute
        $newUser->name = $name ?: $username;
        $newUser->role = 'user';
        $newUser->parent_id = $reseller->id;
        $newUser->is_active = true;
        $newUser->save();

        // Log activity
        $this->logActivity($reseller->id, null, 'user_create', "Created user: {$username}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $newUser->id,
                'username' => $newUser->username,
                'email' => $newUser->email
            ]
        ]);
    }

    /**
     * Edit user form
     */
    public function editUserForm(Request $request, Response $response, array $args): Response
    {
        $reseller = $request->getAttribute('user');
        $userId = (int) $args['id'];

        $targetUser = User::find($userId);

        if (!$targetUser || !$reseller->canManageUser($targetUser)) {
            return $this->redirect($response, '/reseller/users?error=not_found');
        }

        $packages = Package::where('reseller_id', $reseller->id)
            ->where('is_active', true)
            ->get();

        return $this->render($response, 'reseller/user-edit', [
            'title' => 'Edit User - ' . $targetUser->username,
            'current_page' => 'reseller_users',
            'targetUser' => $targetUser,
            'packages' => $packages
        ]);
    }

    /**
     * Update user
     */
    public function updateUser(Request $request, Response $response, array $args): Response
    {
        $reseller = $request->getAttribute('user');
        $userId = (int) $args['id'];
        $data = $request->getParsedBody();

        $targetUser = User::find($userId);

        if (!$targetUser || !$reseller->canManageUser($targetUser)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'User not found or access denied'
            ], 404);
        }

        // Update fields
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if ($email !== $targetUser->email) {
                if (User::where('email', $email)->where('id', '!=', $userId)->exists()) {
                    return $this->jsonResponse($response, [
                        'success' => false,
                        'error' => 'Email already in use'
                    ], 400);
                }
                $targetUser->email = $email;
            }
        }

        if (isset($data['name'])) {
            $targetUser->name = trim($data['name']);
        }

        if (!empty($data['password'])) {
            $targetUser->password = $data['password'];
        }

        if (isset($data['is_active'])) {
            $targetUser->is_active = (bool) $data['is_active'];
        }

        $targetUser->save();

        $this->logActivity($reseller->id, null, 'user_update', "Updated user: {$targetUser->username}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'User updated successfully'
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser(Request $request, Response $response, array $args): Response
    {
        $reseller = $request->getAttribute('user');
        $userId = (int) $args['id'];

        $targetUser = User::find($userId);

        if (!$targetUser || !$reseller->canManageUser($targetUser)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'User not found or access denied'
            ], 404);
        }

        $username = $targetUser->username;

        // This will cascade delete services, databases, etc. (via foreign keys)
        $targetUser->delete();

        $this->logActivity($reseller->id, null, 'user_delete', "Deleted user: {$username}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Suspend/Unsuspend user
     */
    public function toggleUserStatus(Request $request, Response $response, array $args): Response
    {
        $reseller = $request->getAttribute('user');
        $userId = (int) $args['id'];

        $targetUser = User::find($userId);

        if (!$targetUser || !$reseller->canManageUser($targetUser)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'User not found or access denied'
            ], 404);
        }

        $targetUser->is_active = !$targetUser->is_active;
        $targetUser->save();

        $action = $targetUser->is_active ? 'activated' : 'suspended';
        $this->logActivity($reseller->id, null, 'user_' . $action, "{$action} user: {$targetUser->username}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => "User {$action} successfully",
            'is_active' => $targetUser->is_active
        ]);
    }

    /**
     * List reseller's packages
     */
    public function listPackages(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $packages = Package::where('reseller_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->render($response, 'reseller/packages', [
            'title' => 'My Packages',
            'current_page' => 'reseller_packages',
            'packages' => $packages
        ]);
    }

    /**
     * Create package form
     */
    public function createPackageForm(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        // Check if reseller can create packages
        $resellerPackage = $user->resellerPackage;
        if ($resellerPackage && !$resellerPackage->can_create_packages) {
            return $this->redirect($response, '/reseller/packages?error=not_allowed');
        }

        return $this->render($response, 'reseller/package-create', [
            'title' => 'Create Package',
            'current_page' => 'reseller_packages',
            'maxResources' => [
                'disk_gb' => $resellerPackage ? $resellerPackage->max_total_disk_gb : 0,
                'ram_gb' => $resellerPackage ? $resellerPackage->max_total_ram_gb : 0
            ]
        ]);
    }

    /**
     * Create a new package
     */
    public function createPackage(Request $request, Response $response): Response
    {
        $reseller = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $name = trim($data['name'] ?? '');
        $displayName = trim($data['display_name'] ?? $name);

        if (empty($name)) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Package name is required'
            ], 400);
        }

        // Check unique name for this reseller
        if (Package::where('reseller_id', $reseller->id)->where('name', $name)->exists()) {
            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Package with this name already exists'
            ], 400);
        }

        $package = new Package();
        $package->reseller_id = $reseller->id;
        $package->name = $name;
        $package->display_name = $displayName;
        $package->description = $data['description'] ?? '';
        $package->memory_limit = (int) ($data['memory_limit'] ?? 512);
        $package->cpu_limit = (float) ($data['cpu_limit'] ?? 0.5);
        $package->disk_limit = (int) ($data['disk_limit'] ?? 5120);
        $package->max_domains = (int) ($data['max_domains'] ?? 3);
        $package->max_databases = (int) ($data['max_databases'] ?? 1);
        $package->max_backups = (int) ($data['max_backups'] ?? 3);
        $package->is_active = true;
        $package->is_public = false; // Reseller packages are private
        $package->save();

        $this->logActivity($reseller->id, null, 'package_create', "Created package: {$name}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Package created successfully',
            'package' => ['id' => $package->id, 'name' => $package->name]
        ]);
    }

    /**
     * Log activity helper
     */
    private function logActivity(int $userId, ?int $serviceId, string $action, string $description): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        \Illuminate\Database\Capsule\Manager::table('lp_activity_log')->insert([
            'user_id' => $userId,
            'service_id' => $serviceId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
