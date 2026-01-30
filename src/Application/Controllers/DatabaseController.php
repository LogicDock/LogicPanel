<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\Database\Database;
use LogicPanel\Domain\Service\Service;
use LogicPanel\Infrastructure\Database\DatabaseProvisionerService;

class DatabaseController
{
    private DatabaseProvisionerService $provisionerService;

    public function __construct(DatabaseProvisionerService $provisionerService)
    {
        $this->provisionerService = $provisionerService;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');

        $query = Database::where('user_id', $userId);

        // Filter by service if provided
        if (isset($args['serviceId'])) {
            $serviceId = (int) $args['serviceId'];
            $query->where('service_id', $serviceId);
        }

        $databases = $query->get();

        return $this->jsonResponse($response, [
            'databases' => $databases->map(function ($db) {
                return [
                    'id' => $db->id,
                    'service_id' => $db->service_id, // Added service_id to response
                    'type' => $db->db_type,
                    'name' => $db->db_name,
                    'user' => $db->db_user,
                    'password' => $db->db_password, // Exposing password for UI copy helpers
                    'host' => $db->db_host,
                    'port' => $db->db_port,
                    'created_at' => $db->created_at->toIso8601String(),
                ];
            }),
        ]);
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $serviceId = isset($args['serviceId']) ? (int) $args['serviceId'] : null;
        $data = $request->getParsedBody();

        $dbType = $data['type'] ?? 'mysql';

        if (!in_array($dbType, ['mysql', 'postgresql', 'mongodb'])) {
            return $this->jsonResponse($response, ['error' => 'Invalid database type'], 400);
        }

        // Verify service ownership if serviceId is provided
        if ($serviceId) {
            $service = Service::where('id', $serviceId)
                ->where('user_id', $userId)
                ->first();

            if (!$service) {
                return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
            }
        }

        try {
            // Create database record first to get ID
            $database = new Database();
            $database->service_id = $serviceId; // Can be null
            $database->user_id = $userId;
            $database->db_type = $dbType;
            // Set temporary placeholders to satisfy NOT NULL constraints
            $database->db_name = 'pending_' . uniqid();
            $database->db_user = 'pending';
            $database->db_password = ''; // Placeholder
            $database->db_host = 'localhost';
            $database->db_port = 3306; // Default port constraint
            $database->save();

            // Call provisioner service
            $provisionerResponse = match ($dbType) {
                'mysql' => $this->provisionerService->createMySQLDatabase($userId, $database->id),
                'postgresql' => $this->provisionerService->createPostgreSQLDatabase($userId, $database->id),
                'mongodb' => $this->provisionerService->createMongoDBDatabase($userId, $database->id),
            };

            if (!isset($provisionerResponse['database'])) {
                throw new \RuntimeException('Invalid response from provisioner');
            }

            $dbInfo = $provisionerResponse['database'];

            // Update database record with credentials
            $database->db_name = $dbInfo['name'];
            $database->db_user = $dbInfo['user'];
            $database->db_password = $this->encryptPassword($dbInfo['password']);
            $database->db_host = $dbInfo['host'];
            $database->db_port = $dbInfo['port'];
            $database->save();

            return $this->jsonResponse($response, [
                'message' => 'Database created successfully',
                'database' => [
                    'id' => $database->id,
                    'type' => $database->db_type,
                    'name' => $database->db_name,
                    'user' => $database->db_user,
                    'password' => $dbInfo['password'], // Return plain password only on creation
                    'host' => $database->db_host,
                    'port' => $database->db_port,
                    'connection_string' => $database->getConnectionString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            // Cleanup on error
            if (isset($database) && $database->id) {
                $database->delete();
            }

            return $this->jsonResponse($response, [
                'error' => 'Failed to create database',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $databaseId = (int) $args['id'];

        $database = Database::where('id', $databaseId)
            ->where('user_id', $userId)
            ->first();

        if (!$database) {
            return $this->jsonResponse($response, ['error' => 'Database not found'], 404);
        }

        return $this->jsonResponse($response, [
            'database' => [
                'id' => $database->id,
                'type' => $database->db_type,
                'name' => $database->db_name,
                'user' => $database->db_user,
                'host' => $database->db_host,
                'port' => $database->db_port,
                'connection_string' => $database->getConnectionString(),
                'created_at' => $database->created_at->toIso8601String(),
            ],
        ]);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $userId = $request->getAttribute('userId');
        $databaseId = (int) $args['id'];

        $database = Database::where('id', $databaseId)
            ->where('user_id', $userId)
            ->first();

        if (!$database) {
            return $this->jsonResponse($response, ['error' => 'Database not found'], 404);
        }

        try {
            // Call provisioner to delete database
            match ($database->db_type) {
                'mysql' => $this->provisionerService->deleteMySQLDatabase($userId, $database->id),
                'postgresql' => $this->provisionerService->deletePostgreSQLDatabase($userId, $database->id),
                'mongodb' => $this->provisionerService->deleteMongoDBDatabase($userId, $database->id),
            };

            // Delete database record
            $database->delete();

            return $this->jsonResponse($response, ['message' => 'Database deleted successfully']);

        } catch (\Exception $e) {
            return $this->jsonResponse($response, [
                'error' => 'Failed to delete database',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function encryptPassword(string $password): string
    {
        // Simple base64 encoding for now
        // TODO: Implement proper encryption with libsodium
        return base64_encode($password);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
