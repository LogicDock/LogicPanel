<?php

declare(strict_types=1);

namespace LogicPanel\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LogicPanel\Application\Services\JwtService;
use LogicPanel\Application\Services\TokenBlacklistService;
use LogicPanel\Domain\User\User;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;
    private TokenBlacklistService $blacklistService;

    public function __construct(JwtService $jwtService, TokenBlacklistService $blacklistService)
    {
        $this->jwtService = $jwtService;
        $this->blacklistService = $blacklistService;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;

        // Try Authorization header first
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        }

        // SECURITY: Removed query parameter token support
        // Tokens in URLs leak via browser history, server logs, and proxy logs
        // Use Authorization: Bearer <token> header instead

        // --- NEW: API KEY SUPPORT ---
        // If no JWT, try API Key from header
        if (empty($token)) {
            $apiKeyHeader = $request->getHeaderLine('X-API-Key');
            if (!empty($apiKeyHeader)) {
                $apiKey = \LogicPanel\Domain\User\ApiKey::where('key_hash', $apiKeyHeader)->first();
                if ($apiKey) {
                    // Update Last Used
                    $apiKey->last_used_at = date('Y-m-d H:i:s');
                    $apiKey->save();

                    $user = $apiKey->user;
                    if ($user && $user->isActive()) {
                        $request = $request->withAttribute('user', $user);
                        $request = $request->withAttribute('userId', $user->id);
                        $request = $request->withAttribute('auth_method', 'api_key');
                        return $handler->handle($request);
                    }
                }
                // If invalid key, fail
                return $this->unauthorized('Invalid API Key');
            }
        }

        if (empty($token)) {
            return $this->unauthorized('Missing authorization header');
        }

        // Check if token is blacklisted
        if ($this->blacklistService->isBlacklisted($token)) {
            return $this->unauthorized('Token has been revoked');
        }

        try {
            $decoded = $this->jwtService->verifyToken($token);
        } catch (\Exception $e) {
            return $this->unauthorized('Invalid token: ' . $e->getMessage());
        }

        if (!$decoded) {
            return $this->unauthorized('Invalid or expired token');
        }

        // Load user
        try {
            $user = User::find($decoded->sub);
        } catch (\Exception $e) {
            return $this->unauthorized('User lookup failed');
        }

        if (!$user || !$user->isActive()) {
            return $this->unauthorized('User not found or inactive');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);
        $request = $request->withAttribute('userId', $user->id);
        $request = $request->withAttribute('token_decoded', $decoded);
        $request = $request->withAttribute('token_string', $token);

        return $handler->handle($request);
    }

    private function unauthorized(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => 'Unauthorized',
            'message' => $message,
        ]));

        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
