<?php
/**
 * LogicPanel - Security Service
 * Provides CSRF protection, rate limiting, and security utilities
 */

namespace LogicPanel\Services;

class SecurityService
{
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();

        return $token;
    }

    /**
     * Verify CSRF token
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }

        // Token expires after 2 hours
        $tokenAge = time() - ($_SESSION['csrf_token_time'] ?? 0);
        if ($tokenAge > 7200) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Get current CSRF token
     */
    public static function getCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            return self::generateCsrfToken();
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Generate CSRF hidden input field
     */
    public static function csrfField(): string
    {
        $token = self::getCsrfToken();
        return sprintf('<input type="hidden" name="_csrf_token" value="%s">', htmlspecialchars($token));
    }

    /**
     * Check rate limit
     * Returns true if within limit, false if exceeded
     */
    public static function checkRateLimit(string $key, int $maxAttempts = 5, int $windowSeconds = 60): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rateLimitKey = 'rate_limit_' . md5($key);
        $now = time();

        if (!isset($_SESSION[$rateLimitKey])) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 1,
                'window_start' => $now
            ];
            return true;
        }

        $data = $_SESSION[$rateLimitKey];

        // Reset window if expired
        if ($now - $data['window_start'] >= $windowSeconds) {
            $_SESSION[$rateLimitKey] = [
                'attempts' => 1,
                'window_start' => $now
            ];
            return true;
        }

        // Check if limit exceeded
        if ($data['attempts'] >= $maxAttempts) {
            return false;
        }

        // Increment attempts
        $_SESSION[$rateLimitKey]['attempts']++;
        return true;
    }

    /**
     * Get remaining rate limit attempts
     */
    public static function getRateLimitRemaining(string $key, int $maxAttempts = 5): int
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rateLimitKey = 'rate_limit_' . md5($key);

        if (!isset($_SESSION[$rateLimitKey])) {
            return $maxAttempts;
        }

        return max(0, $maxAttempts - $_SESSION[$rateLimitKey]['attempts']);
    }

    /**
     * Sanitize input string
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Validate email format
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate strong password
     * Requires: 8+ chars, uppercase, lowercase, number
     */
    public static function isStrongPassword(string $password): array
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a number';
        }

        return $errors;
    }

    /**
     * Hash password securely
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if password hash needs rehash
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Generate secure random token
     */
    public static function generateToken(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Get client IP (handles proxies)
     */
    public static function getClientIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Proxy
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs
                if (strpos($ip, ',') !== false) {
                    $ips = explode(',', $ip);
                    $ip = trim($ips[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '127.0.0.1';
    }

    /**
     * Check for suspicious request patterns
     */
    public static function isSuspiciousRequest(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Check for common attack patterns in user agent
        $suspiciousPatterns = [
            'sqlmap',
            'nikto',
            'nmap',
            'masscan',
            'zgrab',
            'python-requests',
            'curl/',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log security event to database
     */
    public static function logSecurityEvent(string $event, array $data = []): void
    {
        try {
            \Illuminate\Database\Capsule\Manager::table('lp_activity_log')->insert([
                'user_id' => $_SESSION['user_id'] ?? null,
                'action' => 'security.' . $event,
                'description' => json_encode($data),
                'ip_address' => self::getClientIp(),
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log('Security log error: ' . $e->getMessage());
        }
    }
}
