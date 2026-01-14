<?php
/**
 * LogicPanel - Authentication Middleware
 */

namespace LogicPanel\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use LogicPanel\Models\User;

class AuthMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;
    private bool $adminOnly;

    public function __construct(ContainerInterface $container, bool $adminOnly = false)
    {
        $this->container = $container;
        $this->adminOnly = $adminOnly;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return $this->redirectToLogin($request);
        }

        // Get user from database
        $user = User::find($_SESSION['user_id']);

        if (!$user || !$user->is_active) {
            unset($_SESSION['user_id']);
            return $this->redirectToLogin($request);
        }

        // Check admin requirement
        if ($this->adminOnly && !$user->isAdmin()) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Access denied']));
            return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
        }

        // Add user to request attributes
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }

    private function redirectToLogin(Request $request): Response
    {
        $response = new SlimResponse();

        // Check if it's an AJAX/fetch request (check multiple indicators)
        $isAjax = $request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        $acceptsJson = strpos($request->getHeaderLine('Accept'), 'application/json') !== false;
        $contentTypeJson = strpos($request->getHeaderLine('Content-Type'), 'application/json') !== false;

        if ($isAjax || $acceptsJson || $contentTypeJson) {
            $response->getBody()->write(json_encode(['success' => false, 'error' => 'Unauthorized', 'redirect' => '/login']));
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }

        // Regular redirect
        return $response
            ->withHeader('Location', '/login')
            ->withStatus(302);
    }
}
