<?php
/**
 * LogicPanel - Rate Limit Middleware
 * Protects against brute force and DoS attacks
 */

namespace LogicPanel\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LogicPanel\Services\SecurityService;
use Slim\Psr7\Response as SlimResponse;
use Illuminate\Database\Capsule\Manager as DB;

class RateLimitMiddleware implements MiddlewareInterface
{
    private int $maxAttempts;
    private int $decayMinutes;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $ip = SecurityService::getClientIp();
        $key = $this->getKey($request);

        // Check if rate limit exceeded
        if (!$this->checkRateLimit($key)) {
            SecurityService::logSecurityEvent('rate_limit_exceeded', [
                'key' => $key,
                'ip' => $ip,
                'path' => $request->getUri()->getPath()
            ]);

            return $this->tooManyRequests($request);
        }

        // Increment attempt counter
        $this->hit($key);

        // Add rate limit headers to response
        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', $this->maxAttempts)
            ->withHeader('X-RateLimit-Remaining', max(0, $this->maxAttempts - $this->getAttempts($key)));
    }

    /**
     * Generate rate limit key
     */
    private function getKey(Request $request): string
    {
        $ip = SecurityService::getClientIp();
        $path = $request->getUri()->getPath();
        return md5($ip . '|' . $path);
    }

    /**
     * Check if within rate limit
     */
    private function checkRateLimit(string $key): bool
    {
        $this->cleanupOld();
        return $this->getAttempts($key) < $this->maxAttempts;
    }

    /**
     * Record a hit
     */
    private function hit(string $key): void
    {
        try {
            DB::table('lp_login_attempts')->insert([
                'identifier' => $key,
                'ip_address' => SecurityService::getClientIp(),
                'attempted_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silently fail - don't block request due to logging failure
        }
    }

    /**
     * Get current attempts
     */
    private function getAttempts(string $key): int
    {
        try {
            $cutoff = date('Y-m-d H:i:s', time() - ($this->decayMinutes * 60));
            return (int) DB::table('lp_login_attempts')
                ->where('identifier', $key)
                ->where('attempted_at', '>', $cutoff)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Cleanup old entries
     */
    private function cleanupOld(): void
    {
        try {
            $cutoff = date('Y-m-d H:i:s', time() - 3600); // Clean entries older than 1 hour
            DB::table('lp_login_attempts')
                ->where('attempted_at', '<', $cutoff)
                ->delete();
        } catch (\Exception $e) {
            // Silently fail
        }
    }

    /**
     * Return 429 Too Many Requests response
     */
    private function tooManyRequests(Request $request): Response
    {
        $response = new SlimResponse();
        $response = $response->withStatus(429);
        $response = $response->withHeader('Retry-After', $this->decayMinutes * 60);

        $isAjax = $request->hasHeader('X-Requested-With') ||
            strpos($request->getHeaderLine('Accept'), 'application/json') !== false;

        if ($isAjax) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $this->decayMinutes * 60
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $html = '<!DOCTYPE html>
<html>
<head><title>429 Too Many Requests</title></head>
<body style="font-family: sans-serif; padding: 50px; text-align: center;">
    <h1>🚫 Too Many Requests</h1>
    <p>You have made too many requests. Please wait ' . $this->decayMinutes . ' minute(s) before trying again.</p>
    <p><a href="/">Return Home</a></p>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }

    /**
     * Factory for login rate limiting (stricter)
     */
    public static function forLogin(): self
    {
        return new self(5, 15); // 5 attempts per 15 minutes
    }

    /**
     * Factory for API rate limiting
     */
    public static function forApi(): self
    {
        return new self(100, 1); // 100 requests per minute
    }
}
