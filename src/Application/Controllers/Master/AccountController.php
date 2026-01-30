<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers\Master;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\User\User;
use LogicPanel\Domain\Package\Package;

class AccountController
{
    private $systemBridge;
    private $dockerService;
    private $jwtService;

    public function __construct(
        \LogicPanel\Application\Services\SystemBridgeService $systemBridge,
        \LogicPanel\Infrastructure\Docker\DockerService $dockerService,
        \LogicPanel\Application\Services\JwtService $jwtService
    ) {
        $this->systemBridge = $systemBridge;
        $this->dockerService = $dockerService;
        $this->jwtService = $jwtService;
    }

    // List all accounts
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            // Filter out admin users, show only regular users
            $users = User::with('services')->where('role', '!=', 'admin')->get();

            $data = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'domain' => $user->domain ?? 'N/A',
                    'package' => isset($user->package_id) ? 'Package #' . $user->package_id : 'Default',
                    'ip' => '127.0.0.1',
                    'created_at' => $user->created_at ? $user->created_at->toIso8601String() : date('c'),
                    'status' => $user->status ?? 'unknown',
                    'services_count' => $user->services->count(),
                ];
            });

            return $this->jsonResponse($response, ['accounts' => $data]);
        } catch (\Throwable $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    // Login as User (Impersonation)
    public function loginAsUser(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $user = User::find($id);

        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);
        }

        try {
            // Generate a short-lived one-time token for this user
            $token = $this->jwtService->generateOneTimeToken($user);

            // Determine User Panel URL: Use current host but dynamic port
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $domain = parse_url('http://' . $host, PHP_URL_HOST);
            $userPort = $_ENV['USER_PORT'] ?? 767;
            $userPanelUrlHost = $domain . ':' . $userPort;

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $userPanelUrl = $protocol . '://' . $userPanelUrlHost;

            return $this->jsonResponse($response, [
                'result' => 'success',
                'token' => $token,
                'redirect_url' => rtrim($userPanelUrl, '/') . '/?token=' . $token,
                'message' => "Logged in as {$user->username}"
            ]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Failed to generate token'], 500);
        }
    }

    // Create a new account
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $domain = $data['domain'] ?? '';
        $package = $data['package'] ?? 'default';

        if (empty($username) || empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['error' => 'Username, Email, and Password are required.'], 400);
        }

        // Check if user exists in DB
        if (User::where('username', $username)->exists() || User::where('email', $email)->exists()) {
            return $this->jsonResponse($response, ['error' => 'User already exists.'], 409);
        }

        try {
            // Resolve Package
            $packageId = $data['package_id'] ?? null;
            $packageName = $data['package_name'] ?? null;
            $package = null;

            if ($packageId) {
                $package = Package::find($packageId);
            } elseif ($packageName) {
                $package = Package::where('name', $packageName)->first();
            }

            // Fallback to first package if not found (or handle error)
            if (!$package) {
                $package = Package::first();
            }

            // Note: In Docker-based hosting, we don't need Linux system users.
            // Each user's apps run in their own containers.
            // We only need the database record.

            // Create User Entity in DB
            $user = new User();
            $user->username = $username;
            $user->email = $email;
            $user->setPassword($password);
            $user->role = 'user';
            $user->status = 'active';
            if ($package) {
                $user->package_id = $package->id;
            }
            $user->save();

            return $this->jsonResponse($response, [
                'result' => 'success', // WHMCS-friendly
                'message' => 'Account created successfully',
                'account' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'domain' => $domain
                ]
            ], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'Failed to create account: ' . $e->getMessage()], 500);
        }
    }

    // Suspend an account
    public function suspend(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        // Support both ID in URL (Panel) and username in Body (WHMCS)
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';

        if ($id) {
            $user = User::find($id);
        } else {
            $user = User::where('username', $username)->first();
        }

        if (!$user) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'User not found'], 404);
        }

        try {
            // System Lock
            $this->systemBridge->lockUser($user->username);

            // Stop Containers
            $services = $user->services;
            foreach ($services as $service) {
                if ($service->container_id) {
                    try {
                        $this->dockerService->stopContainer($service->container_id);
                    } catch (\Exception $e) {
                        // Log error but continue suspending other services
                    }
                }
            }

            $user->status = 'suspended';
            $user->save();

            return $this->jsonResponse($response, ['result' => 'success', 'message' => 'Account suspended and services stopped']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'Failed to suspend account: ' . $e->getMessage()], 500);
        }
    }

    // Unsuspend an account
    public function unsuspend(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';

        if ($id) {
            $user = User::find($id);
        } else {
            $user = User::where('username', $username)->first();
        }

        if (!$user) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'User not found'], 404);
        }

        try {
            // System Unlock
            $this->systemBridge->unlockUser($user->username);

            // Start Containers
            // Let's start them.
            $services = $user->services;
            foreach ($services as $service) {
                if ($service->container_id) {
                    try {
                        $this->dockerService->startContainer($service->container_id);
                    } catch (\Exception $e) {
                        // Log error
                    }
                }
            }

            $user->status = 'active';
            $user->save();

            return $this->jsonResponse($response, ['result' => 'success', 'message' => 'Account unsuspended and services started']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'Failed to unsuspend account: ' . $e->getMessage()], 500);
        }
    }

    // Terminate (Delete) an account
    public function terminate(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'] ?? null;
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';

        if ($id) {
            $user = User::find($id);
        } else {
            $user = User::where('username', $username)->first();
        }

        if (!$user) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'User not found'], 404);
        }

        try {
            // 1. Delete Services (Containers)
            $services = $user->services;
            foreach ($services as $service) {
                if ($service->container_id) {
                    try {
                        $this->dockerService->removeContainer($service->container_id);
                    } catch (\Exception $e) {
                        // Ignore if already deleted
                    }
                }
                $service->delete();
            }

            // 2. System Delete
            // Warning: This deletes home directory too!
            $this->systemBridge->deleteUser($user->username);

            // 3. Delete Databases
            $user->databases()->delete();
            $user->domains()->delete();

            $user->delete();

            return $this->jsonResponse($response, ['result' => 'success', 'message' => 'Account terminated']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'Failed to terminate account: ' . $e->getMessage()], 500);
        }
    }

    // Change Password
    public function changePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $user = User::where('username', $username)->first();
        if (!$user) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'User not found'], 404);
        }

        try {
            // System Password Change
            $this->systemBridge->changePassword($username, $password);

            $user->setPassword($password);
            $user->save();

            return $this->jsonResponse($response, ['result' => 'success', 'message' => 'Password changed successfully']);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['result' => 'error', 'message' => 'Failed to change password: ' . $e->getMessage()], 500);
        }
    }

    // WHMCS: Change Package
    public function changePackage(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $username = $data['username'] ?? null;
        $packageId = $data['package_id'] ?? null;
        $packageName = $data['package_name'] ?? null;

        if (!$username)
            return $this->jsonResponse($response, ['error' => 'Username required'], 400);

        $user = User::where('username', $username)->first();
        if (!$user)
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);

        $package = null;
        if ($packageId) {
            $package = Package::find($packageId);
        } elseif ($packageName) {
            $package = Package::where('name', $packageName)->first();
        }

        if (!$package)
            return $this->jsonResponse($response, ['error' => 'Package not found'], 404);

        $user->package_id = $package->id;
        $user->save();

        return $this->jsonResponse($response, ['message' => 'Package changed successfully', 'package' => $package->name]);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
