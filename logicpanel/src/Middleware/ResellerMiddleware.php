<?php
/**
 * Reseller Role Middleware
 */

namespace LogicPanel\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class ResellerMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $user = $request->getAttribute('user');

        // Check if user is admin or reseller
        if ($user && ($user->role === 'admin' || $user->role === 'reseller')) {
            return $handler->handle($request);
        }

        // Redirect to dashboard
        $response = new \Slim\Psr7\Response();
        return $response->withHeader('Location', '/')->withStatus(302);
    }
}
