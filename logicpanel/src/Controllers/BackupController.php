<?php
/**
 * LogicPanel - Backup Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Models\Backup;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class BackupController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * List all backups
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $services = Service::where('user_id', $user->id)
            ->with([
                'backups' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->get();

        $allBackups = [];
        foreach ($services as $service) {
            foreach ($service->backups as $backup) {
                $backup->service_name = $service->name;
                $allBackups[] = $backup;
            }
        }

        // Sort by date desc
        usort($allBackups, function ($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });

        return $this->render($response, 'backups/index', [
            'title' => 'Backups - LogicPanel',
            'backups' => $allBackups,
            'services' => $services
        ]);
    }

    /**
     * Show backups for a specific service
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->with([
                'backups' => function ($q) {
                    $q->orderBy('created_at', 'desc');
                }
            ])
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        return $this->render($response, 'backups/show', [
            'title' => 'Backups - ' . $service->name,
            'service' => $service,
            'backups' => $service->backups
        ]);
    }

    /**
     * Create a new backup
     */
    public function create(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];
        $data = $request->getParsedBody();

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service not found'], 404);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service container not found'], 400);
        }

        // Check backup limit
        $maxBackups = $this->getSetting('max_backups_per_service', 5);
        $currentCount = Backup::where('service_id', $serviceId)->count();
        if ($currentCount >= $maxBackups) {
            // Delete oldest backup
            $oldest = Backup::where('service_id', $serviceId)
                ->orderBy('created_at', 'asc')
                ->first();
            if ($oldest) {
                $this->deleteBackupFile($oldest);
                $oldest->delete();
            }
        }

        // Create backup
        $type = $data['type'] ?? 'full';
        $notes = $data['notes'] ?? '';
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "backup_{$service->name}_{$timestamp}.tar.gz";
        $backupPath = $this->getBackupPath($service->id);

        // Create backup record
        $backup = new Backup();
        $backup->service_id = $serviceId;
        $backup->filename = $filename;
        $backup->path = $backupPath . '/' . $filename;
        $backup->type = $type;
        $backup->status = 'in_progress';
        $backup->notes = $notes;
        $backup->save();

        try {
            // Perform backup
            $this->performBackup($service, $backup);

            $backup->status = 'completed';
            $backup->completed_at = date('Y-m-d H:i:s');

            // Get file size
            if (file_exists($backup->path)) {
                $backup->size = filesize($backup->path);
            }

            $backup->save();

            $this->logActivity($user->id, $serviceId, 'backup_create', "Created backup: {$filename}");

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Backup created successfully',
                'backup' => [
                    'id' => $backup->id,
                    'filename' => $backup->filename,
                    'size' => $backup->getHumanSize(),
                    'created_at' => $backup->created_at
                ]
            ]);
        } catch (\Exception $e) {
            $backup->status = 'failed';
            $backup->notes = ($backup->notes ? $backup->notes . "\n" : '') . "Error: " . $e->getMessage();
            $backup->save();

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $backupId = (int) $args['id'];

        $backup = Backup::with('service')->find($backupId);

        if (!$backup || $backup->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Backup not found'], 404);
        }

        if ($backup->status !== 'completed') {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Backup is not complete'], 400);
        }

        if (!file_exists($backup->path)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Backup file not found'], 404);
        }

        $service = $backup->service;

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        try {
            // Stop container
            if ($service->container_id) {
                $this->docker->stopContainer($service->container_id);
            }

            // Extract backup
            $this->performRestore($service, $backup);

            // Start container
            if ($service->container_id) {
                $this->docker->startContainer($service->container_id);
            }

            $this->logActivity($user->id, $service->id, 'backup_restore', "Restored from backup: {$backup->filename}");

            return $this->jsonResponse($response, [
                'success' => true,
                'message' => 'Backup restored successfully'
            ]);
        } catch (\Exception $e) {
            // Try to restart container
            if ($service->container_id) {
                $this->docker->startContainer($service->container_id);
            }

            return $this->jsonResponse($response, [
                'success' => false,
                'error' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup
     */
    public function delete(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $backupId = (int) $args['id'];

        $backup = Backup::with('service')->find($backupId);

        if (!$backup || $backup->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Backup not found'], 404);
        }

        $filename = $backup->filename;
        $serviceId = $backup->service_id;

        // Delete file
        $this->deleteBackupFile($backup);

        // Delete record
        $backup->delete();

        $this->logActivity($user->id, $serviceId, 'backup_delete', "Deleted backup: {$filename}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Backup deleted successfully'
        ]);
    }

    /**
     * Download backup file
     */
    public function download(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $backupId = (int) $args['id'];

        $backup = Backup::with('service')->find($backupId);

        if (!$backup || $backup->service->user_id !== $user->id) {
            return $this->jsonResponse($response, ['error' => 'Backup not found'], 404);
        }

        if (!file_exists($backup->path)) {
            return $this->jsonResponse($response, ['error' => 'Backup file not found'], 404);
        }

        // Log download
        $this->logActivity($user->id, $backup->service_id, 'backup_download', "Downloaded backup: {$backup->filename}");

        // Stream file
        $fh = fopen($backup->path, 'rb');
        $stream = new \Slim\Psr7\Stream($fh);

        return $response
            ->withHeader('Content-Type', 'application/gzip')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $backup->filename . '"')
            ->withHeader('Content-Length', (string) $backup->size)
            ->withBody($stream);
    }

    /**
     * Perform the backup operation
     */
    private function performBackup(Service $service, Backup $backup): void
    {
        // Ensure backup directory exists
        $dir = dirname($backup->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create tar archive from container
        $volumeName = "lp_{$service->name}_data";

        // Execute tar command inside container
        $tarCmd = ['tar', '-czf', '-', '-C', '/app', '.'];
        $tarOutput = $this->docker->execInContainer($service->container_id, $tarCmd);

        if ($tarOutput !== null) {
            file_put_contents($backup->path, $tarOutput);
        } else {
            throw new \Exception('Failed to create backup archive');
        }
    }

    /**
     * Perform the restore operation
     */
    private function performRestore(Service $service, Backup $backup): void
    {
        if (!$service->container_id) {
            throw new \Exception('Container not found');
        }

        // Read backup file
        $tarContent = file_get_contents($backup->path);
        if (!$tarContent) {
            throw new \Exception('Failed to read backup file');
        }

        // Clear existing files and extract
        $this->docker->execInContainer($service->container_id, ['sh', '-c', 'rm -rf /app/*']);

        // Write tar to temp file inside container and extract
        // This is a simplified version - in production, use Docker API properly
        $tempFile = '/tmp/restore_' . time() . '.tar.gz';
        $this->docker->execInContainer($service->container_id, [
            'sh',
            '-c',
            "cat > {$tempFile} && tar -xzf {$tempFile} -C /app && rm {$tempFile}"
        ]);
    }

    /**
     * Delete backup file
     */
    private function deleteBackupFile(Backup $backup): void
    {
        if ($backup->path && file_exists($backup->path)) {
            unlink($backup->path);
        }
    }

    /**
     * Get backup storage path
     */
    private function getBackupPath(int $serviceId): string
    {
        $basePath = $_ENV['BACKUP_PATH'] ?? BASE_PATH . '/storage/backups';
        return $basePath . '/service_' . $serviceId;
    }

    /**
     * Get setting value
     */
    private function getSetting(string $key, $default = null)
    {
        $setting = DB::table('settings')->where('key', $key)->first();
        return $setting ? $setting->value : $default;
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
