<?php

declare(strict_types=1);

namespace LogicPanel\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LogicPanel\Application\Services\JwtService;
use LogicPanel\Domain\User\User;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface
{
    private JwtService $jwtService;

    public function __construct(JwtService $jwtService)
    {
        $this->jwtService = $jwtService;
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

        // Fallback: Check query parameter 'token' (for download/direct access)
        if (empty($token)) {
            $queryParams = $request->getQueryParams();
            if (!empty($queryParams['token'])) {
                $token = $queryParams['token'];
            }
        }

        // --- NEW: API KEY SUPPORT ---
        // If no JWT, try API Key from header
        if (empty($token)) {
            $apiKeyHeader = $request->getHeaderLine('X-API-Key');
            if (!empty($apiKeyHeader)) {
                $apiKey = \LogicPanel\Domain\User\ApiKey::where('p_key', $apiKeyHeader)->first();
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
