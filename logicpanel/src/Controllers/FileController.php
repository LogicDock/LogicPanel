<?php
/**
 * LogicPanel - File Manager Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class FileController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * File manager page
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->render($response, 'errors/404', ['message' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->render($response, 'errors/error', ['message' => 'Container not created']);
        }

        return $this->render($response, 'files/index', [
            'title' => 'File Manager - ' . $service->name,
            'service' => $service
        ]);
    }

    /**
     * Browse directory contents
     */
    public function browse(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $params = $request->getQueryParams();
        $path = $params['path'] ?? '/app';

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not found'], 400);
        }

        // Security: Ensure path is within /app
        $path = $this->normalizePath($path);
        if (strpos($path, '/app') !== 0 && $path !== '/') {
            $path = '/app' . $path;
        }

        // List directory contents
        $cmd = ['sh', '-c', "ls -la --time-style='+%Y-%m-%d %H:%M' {$path} 2>/dev/null"];
        $output = $this->docker->execInContainer($service->container_id, $cmd);

        if ($output === null) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Failed to list directory'], 500);
        }

        $files = $this->parseDirectoryListing($output, $path);

        return $this->jsonResponse($response, [
            'success' => true,
            'path' => $path,
            'files' => $files
        ]);
    }

    /**
     * Download a file
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $params = $request->getQueryParams();
        $path = $params['path'] ?? '';

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        $path = $this->normalizePath($path);
        if (strpos($path, '/app') !== 0) {
            return $this->jsonResponse($response, ['error' => 'Invalid path'], 400);
        }

        // Get file content
        $cmd = ['cat', $path];
        $content = $this->docker->execInContainer($service->container_id, $cmd);

        if ($content === null) {
            return $this->jsonResponse($response, ['error' => 'Failed to read file'], 500);
        }

        $filename = basename($path);

        $response->getBody()->write($content);
        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Upload a file
     */
    public function upload(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        $targetPath = $data['path'] ?? '/app';
        $targetPath = $this->normalizePath($targetPath);

        if (strpos($targetPath, '/app') !== 0) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid path'], 400);
        }

        if (empty($uploadedFiles['file'])) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'No file uploaded'], 400);
        }

        $uploadedFile = $uploadedFiles['file'];

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Upload failed'], 400);
        }

        // Check file size (max 50MB)
        if ($uploadedFile->getSize() > 50 * 1024 * 1024) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'File too large (max 50MB)'], 400);
        }

        $filename = $uploadedFile->getClientFilename();
        $content = base64_encode($uploadedFile->getStream()->getContents());

        // Write file via exec
        $destPath = rtrim($targetPath, '/') . '/' . $filename;
        $cmd = ['sh', '-c', "echo '{$content}' | base64 -d > '{$destPath}'"];
        $this->docker->execInContainer($service->container_id, $cmd);

        $this->logActivity($user->id, $serviceId, 'file_upload', "Uploaded file: {$destPath}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'File uploaded successfully',
            'path' => $destPath
        ]);
    }

    /**
     * Delete a file or directory
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        $path = $this->normalizePath($data['path'] ?? '');

        // Security checks
        if (strpos($path, '/app') !== 0) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid path'], 400);
        }

        // Prevent deleting root app directory
        if ($path === '/app' || $path === '/app/') {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Cannot delete app root'], 400);
        }

        $cmd = ['rm', '-rf', $path];
        $this->docker->execInContainer($service->container_id, $cmd);

        $this->logActivity($user->id, $serviceId, 'file_delete', "Deleted: {$path}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    /**
     * Edit/save file content
     */
    public function edit(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        $path = $this->normalizePath($data['path'] ?? '');
        $content = $data['content'] ?? '';

        if (strpos($path, '/app') !== 0) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid path'], 400);
        }

        // Write content via exec
        $encodedContent = base64_encode($content);
        $cmd = ['sh', '-c', "echo '{$encodedContent}' | base64 -d > '{$path}'"];
        $this->docker->execInContainer($service->container_id, $cmd);

        $this->logActivity($user->id, $serviceId, 'file_edit', "Edited file: {$path}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'File saved successfully'
        ]);
    }

    /**
     * Create directory
     */
    public function mkdir(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service || !$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        $path = $this->normalizePath($data['path'] ?? '');
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '', $data['name'] ?? '');

        if (empty($name)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Directory name is required'], 400);
        }

        if (strpos($path, '/app') !== 0) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid path'], 400);
        }

        $fullPath = rtrim($path, '/') . '/' . $name;
        $cmd = ['mkdir', '-p', $fullPath];
        $this->docker->execInContainer($service->container_id, $cmd);

        $this->logActivity($user->id, $serviceId, 'dir_create', "Created directory: {$fullPath}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Directory created successfully',
            'path' => $fullPath
        ]);
    }

    /**
     * Normalize path to prevent directory traversal
     */
    private function normalizePath(string $path): string
    {
        // Remove null bytes
        $path = str_replace("\0", '', $path);

        // Replace backslashes
        $path = str_replace('\\', '/', $path);

        // Remove multiple slashes
        $path = preg_replace('#/+#', '/', $path);

        // Remove .. 
        $parts = explode('/', $path);
        $clean = [];
        foreach ($parts as $part) {
            if ($part === '..') {
                array_pop($clean);
            } elseif ($part !== '.' && $part !== '') {
                $clean[] = $part;
            }
        }

        return '/' . implode('/', $clean);
    }

    /**
     * Parse ls -la output
     */
    private function parseDirectoryListing(string $output, string $basePath): array
    {
        $lines = explode("\n", trim($output));
        $files = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, 'total') === 0) {
                continue;
            }

            // Parse ls -la output
            // drwxr-xr-x 2 root root 4096 2024-01-10 15:30 dirname
            $pattern = '/^([drwx\-]+)\s+\d+\s+\w+\s+\w+\s+(\d+)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s+(.+)$/';

            if (preg_match($pattern, $line, $matches)) {
                $name = $matches[4];

                // Skip . and ..
                if ($name === '.' || $name === '..') {
                    continue;
                }

                $files[] = [
                    'name' => $name,
                    'path' => rtrim($basePath, '/') . '/' . $name,
                    'type' => $matches[1][0] === 'd' ? 'directory' : 'file',
                    'size' => (int) $matches[2],
                    'size_human' => $this->formatBytes((int) $matches[2]),
                    'modified' => $matches[3],
                    'permissions' => $matches[1]
                ];
            }
        }

        // Sort: directories first, then alphabetically
        usort($files, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $files;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Log activity
     */
    private function logActivity(int $userId, int $serviceId, string $action, string $description): void
    {
        DB::table('activity_log')->insert([
            'user_id' => $userId,
            'service_id' => $serviceId,
            'action' => $action,
            'description' => $description,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
