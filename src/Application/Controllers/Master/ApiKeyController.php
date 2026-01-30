<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers\Master;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\User\ApiKey;
use LogicPanel\Domain\User\User;

class ApiKeyController
{
    /**
     * List all API Keys (optionally filter by user)
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // $params = $request->getQueryParams();
        // $userId = $params['user_id'] ?? null;

        $keys = ApiKey::with('user')->orderBy('created_at', 'desc')->get();

        return $this->jsonResponse($response, ['keys' => $keys]);
    }

    /**
     * Create a new API Key for a user
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $name = $data['name'] ?? 'Unlocked Key';

        // Handle case where user_id might be an empty string from the frontend
        $userId = !empty($data['user_id']) ? $data['user_id'] : $request->getAttribute('userId');

        if (!$userId) {
            return $this->jsonResponse($response, ['error' => 'User ID could not be determined'], 400);
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->jsonResponse($response, ['error' => 'User not found'], 404);
        }

        try {
            // Generate Key: lp_ + 48 chars (24 bytes in hex)
            $keyString = 'lp_' . bin2hex(random_bytes(24));

            $apiKey = new ApiKey();
            $apiKey->user_id = $userId;
            $apiKey->name = $name;
            $apiKey->p_key = $keyString;
            $apiKey->permissions = ['*']; // Full access for now
            $apiKey->save();

            return $this->jsonResponse($response, [
                'message' => 'API Key created successfully',
                'api_key' => $apiKey
            ], 201);
        } catch (\Exception $e) {
            // Log the error
            error_log("API Key Creation Error: " . $e->getMessage());
            return $this->jsonResponse($response, [
                'error' => 'Failed to save API key: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an API Key
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $key = ApiKey::find($id);

        if (!$key) {
            return $this->jsonResponse($response, ['error' => 'Key not found'], 404);
        }

        $key->delete();

        return $this->jsonResponse($response, ['message' => 'API Key deleted successfully']);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
