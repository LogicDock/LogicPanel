<?php
/**
 * LogicPanel - API Authentication Middleware
 * For WHMCS and external API access
 */

namespace LogicPanel\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Illuminate\Database\Capsule\Manager as DB;

class ApiAuthMiddleware implements MiddlewareInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Get API credentials from headers
        $apiKey = $request->getHeaderLine('X-API-Key');
        $apiSecret = $request->getHeaderLine('X-API-Secret');

        // Also check query params for legacy support
        if (empty($apiKey)) {
            $params = $request->getQueryParams();
            $apiKey = $params['api_key'] ?? '';
            $apiSecret = $params['api_secret'] ?? '';
        }

        // Also check POST body
        if (empty($apiKey)) {
            $body = $request->getParsedBody();
            $apiKey = $body['api_key'] ?? '';
            $apiSecret = $body['api_secret'] ?? '';
        }

        // Validate credentials
        if (empty($apiKey) || empty($apiSecret)) {
            return $this->unauthorized('API credentials required');
        }

        // Get table prefix from ENV
        $prefix = $_ENV['DB_PREFIX'] ?? 'lp_';

        // Look up API key in database
        $apiKeyRecord = DB::table($prefix . 'api_keys')
            ->where('api_key', $apiKey)
            ->where('status', 'active')
            ->first();

        if (!$apiKeyRecord) {
            return $this->unauthorized('Invalid API key');
        }

        // Verify secret (stored as hashed or plain)
        $secretValid = false;

        // Check if secret is hashed (starts with $2y$ for bcrypt)
        if (strpos($apiKeyRecord->api_secret, '$2y$') === 0) {
            $secretValid = password_verify($apiSecret, $apiKeyRecord->api_secret);
        } else {
            // Plain text comparison
            $secretValid = hash_equals($apiKeyRecord->api_secret, $apiSecret);
        }

        if (!$secretValid) {
            return $this->unauthorized('Invalid API secret');
        }

        // Update last used timestamp
        DB::table($prefix . 'api_keys')
            ->where('id', $apiKeyRecord->id)
            ->update(['updated_at' => date('Y-m-d H:i:s')]);

        // Add API key info to request
        $request = $request->withAttribute('api_key', $apiKeyRecord);
        $request = $request->withAttribute('permissions', json_decode($apiKeyRecord->permissions ?? '{}', true));

        return $handler->handle($request);
    }

    private function unauthorized(string $message): Response
    {
        $response = new SlimResponse();
        $response->getBody()->write(json_encode([
            'success' => false,
            'error' => $message
        ]));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
