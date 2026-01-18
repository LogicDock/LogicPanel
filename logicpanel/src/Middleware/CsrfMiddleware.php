<?php
/**
 * LogicPanel - CSRF Middleware
 * Protects against Cross-Site Request Forgery attacks
 */

namespace LogicPanel\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LogicPanel\Services\SecurityService;
use Slim\Psr7\Response as SlimResponse;

class CsrfMiddleware implements MiddlewareInterface
{
    /**
     * HTTP methods that require CSRF validation
     */
    private array $protectedMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Routes to exclude from CSRF protection (e.g., API webhooks)
     */
    private array $excludedRoutes = [
        '/api/webhook',
        '/api/v1/external',
    ];

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        // Skip CSRF check for safe methods
        if (!in_array($method, $this->protectedMethods)) {
            return $handler->handle($request);
        }

        // Skip for excluded routes
        foreach ($this->excludedRoutes as $excluded) {
            if (strpos($path, $excluded) === 0) {
                return $handler->handle($request);
            }
        }

        // Skip for API token authentication
        if ($request->hasHeader('X-API-Key')) {
            return $handler->handle($request);
        }

        // Get token from request
        $token = $this->getTokenFromRequest($request);

        // Verify token
        if (!SecurityService::verifyCsrfToken($token)) {
            SecurityService::logSecurityEvent('csrf_failure', [
                'path' => $path,
                'method' => $method,
                'ip' => SecurityService::getClientIp()
            ]);

            return $this->forbidden($request);
        }

        return $handler->handle($request);
    }

    /**
     * Extract CSRF token from request
     */
    private function getTokenFromRequest(Request $request): ?string
    {
        // Check POST body
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody) && isset($parsedBody['_csrf_token'])) {
            return $parsedBody['_csrf_token'];
        }

        // Check X-CSRF-Token header (for AJAX)
        if ($request->hasHeader('X-CSRF-Token')) {
            return $request->getHeaderLine('X-CSRF-Token');
        }

        // Check for JSON body
        $contentType = $request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = $request->getBody()->getContents();
            $request->getBody()->rewind();
            $data = json_decode($body, true);
            if (isset($data['_csrf_token'])) {
                return $data['_csrf_token'];
            }
        }

        return null;
    }

    /**
     * Return 403 Forbidden response
     */
    private function forbidden(Request $request): Response
    {
        $response = new SlimResponse();
        $response = $response->withStatus(403);

        // Check if AJAX request
        $isAjax = $request->hasHeader('X-Requested-With') ||
            strpos($request->getHeaderLine('Accept'), 'application/json') !== false ||
            strpos($request->getHeaderLine('Content-Type'), 'application/json') !== false;

        if ($isAjax) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'CSRF token invalid or expired. Please refresh the page.'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // HTML response for regular requests
        $html = '<!DOCTYPE html>
<html>
<head><title>403 Forbidden</title></head>
<body>
    <h1>Security Error</h1>
    <p>Your session has expired or the request is invalid.</p>
    <p><a href="javascript:history.back()">Go Back</a> or <a href="/">Return Home</a></p>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    }
}
