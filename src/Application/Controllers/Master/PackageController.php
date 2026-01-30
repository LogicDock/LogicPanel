<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers\Master;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\Package\Package;

class PackageController
{
    // List packages
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $packages = Package::all();
        return $this->jsonResponse($response, ['packages' => $packages]);
    }

    // Get a single package
    public function get(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $package = Package::find($id);

        if (!$package) {
            return $this->jsonResponse($response, ['error' => 'Package not found'], 404);
        }

        return $this->jsonResponse($response, ['package' => $package]);
    }

    // Create package
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();

        $package = new Package();
        $package->fill($data);

        // Set defaults if missing (optional, as DB has defaults, but good for explicit API response)
        // Actually, let DB handle defaults if null 

        try {
            $package->save();
            return $this->jsonResponse($response, ['message' => 'Package created successfully', 'package' => $package], 201);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 500);
        }
    }

    // Update package
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $package = Package::find($id);

        if (!$package) {
            return $this->jsonResponse($response, ['error' => 'Package not found'], 404);
        }

        $data = $request->getParsedBody();
        $package->fill($data);

        try {
            $package->save();
            return $this->jsonResponse($response, ['message' => 'Package updated successfully', 'package' => $package]);
        } catch (\Exception $e) {
            return $this->jsonResponse($response, ['error' => 'Update failed: ' . $e->getMessage()], 500);
        }
    }

    // Delete package
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        $package = Package::find($id);

        if (!$package) {
            return $this->jsonResponse($response, ['error' => 'Package not found'], 404);
        }

        $package->delete();

        return $this->jsonResponse($response, ['message' => 'Package deleted successfully']);
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
