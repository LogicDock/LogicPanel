<?php
/**
 * LogicPanel - Authentication Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\User;
use Illuminate\Database\Capsule\Manager as DB;

class AuthController extends BaseController
{
    /**
     * Show login page
     */
    public function showLogin(Request $request, Response $response): Response
    {
        // Check if already logged in
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user_id'])) {
            return $response
                ->withHeader('Location', '/')
                ->withStatus(302);
        }

        return $this->render($response, 'auth/login', [
            'title' => 'Login - LogicPanel'
        ]);
    }

    /**
     * Process login
     */
    public function processLogin(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        // Validate input
        if (empty($username) || empty($password)) {
            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'Please enter username and password'
            ]);
        }

        // Find user by username or email
        $user = User::where('username', $username)
            ->orWhere('email', $username)
            ->first();

        if (!$user || !$user->verifyPassword($password)) {
            // Log failed attempt
            $this->logActivity(null, 'login_failed', "Failed login attempt for: {$username}");

            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'Invalid username or password'
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'Your account has been deactivated'
            ]);
        }

        // Start session and log in user
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['theme'] = $user->theme;

        // Update last login
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        // Log successful login
        $this->logActivity($user->id, 'login', 'User logged in');

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    /**
     * SSO Login from WHMCS
     */
    public function ssoLogin(Request $request, Response $response, array $args): Response
    {
        $token = $args['token'] ?? '';

        if (empty($token)) {
            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'Invalid SSO token'
            ]);
        }

        // Find token in database
        $ssoToken = DB::table('sso_tokens')
            ->where('token', $token)
            ->where('expires_at', '>', date('Y-m-d H:i:s'))
            ->whereNull('used_at')
            ->first();

        if (!$ssoToken) {
            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'SSO token is invalid or expired'
            ]);
        }

        // Get user
        $user = User::find($ssoToken->user_id);

        if (!$user || !$user->is_active) {
            return $this->render($response, 'auth/login', [
                'title' => 'Login - LogicPanel',
                'error' => 'User account not found or inactive'
            ]);
        }

        // Mark token as used
        DB::table('sso_tokens')
            ->where('id', $ssoToken->id)
            ->update([
                'used_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);

        // Start session and log in user
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_name'] = $user->name;
        $_SESSION['user_role'] = $user->role;
        $_SESSION['theme'] = $user->theme;
        $_SESSION['sso_login'] = true;

        // Update last login
        $user->last_login = date('Y-m-d H:i:s');
        $user->save();

        // Log SSO login
        $this->logActivity($user->id, 'sso_login', 'User logged in via SSO');

        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    /**
     * Logout
     */
    public function logout(Request $request, Response $response): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userId = $_SESSION['user_id'] ?? null;

        // Log logout
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logged out');
        }

        // Destroy session
        $_SESSION = [];
        session_destroy();

        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }

    /**
     * Show setup page (first time installation)
     */
    public function showSetup(Request $request, Response $response): Response
    {
        // Check if already installed
        try {
            $userCount = User::count();
            if ($userCount > 0) {
                return $response
                    ->withHeader('Location', '/login')
                    ->withStatus(302);
            }
        } catch (\Exception $e) {
            // Database not set up yet, show setup
        }

        return $this->render($response, 'auth/setup', [
            'title' => 'Setup - LogicPanel'
        ]);
    }

    /**
     * Process setup
     */
    public function processSetup(Request $request, Response $response): Response
    {
        // Check if already installed
        try {
            $userCount = User::count();
            if ($userCount > 0) {
                return $this->jsonResponse($response, ['error' => 'Already installed'], 400);
            }
        } catch (\Exception $e) {
            // Continue with setup
        }

        $data = $request->getParsedBody();

        // Validate input
        $errors = [];
        if (empty($data['admin_username']))
            $errors[] = 'Admin username is required';
        if (empty($data['admin_email']))
            $errors[] = 'Admin email is required';
        if (empty($data['admin_password']))
            $errors[] = 'Admin password is required';
        if (strlen($data['admin_password'] ?? '') < 8)
            $errors[] = 'Password must be at least 8 characters';

        if (!empty($errors)) {
            return $this->render($response, 'auth/setup', [
                'title' => 'Setup - LogicPanel',
                'errors' => $errors,
                'data' => $data
            ]);
        }

        // Create admin user
        $admin = new User();
        $admin->username = $data['admin_username'];
        $admin->email = $data['admin_email'];
        $admin->password = $data['admin_password'];
        $admin->name = $data['admin_name'] ?? 'Administrator';
        $admin->role = 'admin';
        $admin->is_active = true;
        $admin->save();

        // Log setup
        $this->logActivity($admin->id, 'setup', 'LogicPanel installed');

        return $response
            ->withHeader('Location', '/login?setup=complete')
            ->withStatus(302);
    }

    /**
     * Log activity
     */
    private function logActivity(?int $userId, string $action, string $description): void
    {
        try {
            DB::table('activity_log')->insert([
                'user_id' => $userId,
                'action' => $action,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
