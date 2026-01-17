<?php
/**
 * LogicPanel - Role Middleware
 * Handles role-based access control for routes
 */

namespace LogicPanel\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class RoleMiddleware
{
    private array $allowedRoles;

    /**
     * @param array $allowedRoles Array of allowed roles: ['admin'], ['admin', 'reseller'], etc.
     */
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            return $this->unauthorizedResponse('Authentication required');
        }

        // Check if user's role is in allowed roles
        if (!in_array($user->role, $this->allowedRoles)) {
            return $this->forbiddenResponse('Access denied. Insufficient permissions.');
        }

        // Check if user is active
        if (!$user->is_active) {
            return $this->forbiddenResponse('Account is suspended or inactive.');
        }

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message
        ]));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }

    private function forbiddenResponse(string $message): Response
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message
        ]));
        return $response
            ->withStatus(403)
            ->withHeader('Content-Type', 'application/json');
    }
}

/**
 * Factory class for creating role middleware instances
 */
class RoleMiddlewareFactory
{
    /**
     * Only admin can access
     */
    public static function adminOnly(): RoleMiddleware
    {
        return new RoleMiddleware(['admin']);
    }

    /**
     * Admin and reseller can access
     */
    public static function resellerOrAbove(): RoleMiddleware
    {
        return new RoleMiddleware(['admin', 'reseller']);
    }

    /**
     * All authenticated users can access
     */
    public static function anyAuthenticated(): RoleMiddleware
    {
        return new RoleMiddleware(['admin', 'reseller', 'user']);
    }
}
