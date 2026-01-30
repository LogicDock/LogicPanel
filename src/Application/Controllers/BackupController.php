<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class BackupController
{
    private string $backupDir;
    private string $sourceDir;

    public function __construct()
    {
        // Define paths relative to the project root
        // Assuming this file is at src/Application/Controllers
        $projectRoot = dirname(__DIR__, 3);
        $this->backupDir = $projectRoot . '/storage/backups';
        $this->sourceDir = $projectRoot . '/storage/user-apps';

        // Create backup directory if it doesn't exist
        if (!is_dir($this->backupDir)) {
            if (!mkdir($this->backupDir, 0755, true)) {
                error_log("Failed to create backup directory: " . $this->backupDir);
            }
        }

        // Ensure source dir exists (even if empty) to avoid errors
        if (!is_dir($this->sourceDir)) {
            if (!mkdir($this->sourceDir, 0755, true)) {
                error_log("Failed to create user-apps directory: " . $this->sourceDir);
            }
        }
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $backups = [];

        // Scan for completed backups (.zip)
        $files = glob($this->backupDir . '/*.zip');
        if ($files) {
            foreach ($files as $file) {
                // If a pending file exists for this zip, remove it as the zip is done
                $pendingFile = $file . '.pending';
                if (file_exists($pendingFile)) {
                    @unlink($pendingFile);
                }

                $backups[] = [
                    'name' => basename($file),
                    'size' => $this->formatSize(filesize($file)),
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'type' => 'application',
                    'status' => 'ready'
                ];
            }
        }

        // Scan for pending backups (.zip.pending)
        $pendingFiles = glob($this->backupDir . '/*.pending');
        if ($pendingFiles) {
            foreach ($pendingFiles as $file) {
                // Remove .pending to get base name check
                $baseName = basename($file, '.pending'); // e.g. app_backup_xyz.zip

                // Double check if the target zip exists now
                if (file_exists($this->backupDir . '/' . $baseName)) {
                    @unlink($file); // Cleanup
                    continue; // Will be picked up by the zip scan on next refresh
                }

                // Check for stale pending files (> 10 mins)
                if (time() - filemtime($file) > 600) {
                    @unlink($file); // Remove stale lock
                    continue;
                }

                $backups[] = [
                    'name' => $baseName, // show the target zip name
                    'size' => '-',
                    'date' => date('Y-m-d H:i:s', filemtime($file)),
                    'type' => 'application',
                    'status' => 'creating'
                ];
            }
        }

        // Sort by date desc
        usort($backups, function ($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $this->jsonResponse($response, ['backups' => $backups]);
    }

    public function createAppBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (!is_writable($this->backupDir)) {
            return $this->jsonResponse($response, ['error' => 'Backup directory is not writable'], 500);
        }

        $baseFilename = 'app_backup_' . date('Y-m-d_H-i-s'); // No extension here
        $pendingFile = $this->backupDir . '/' . $baseFilename . '.zip.pending';

        // Create pending marker
        file_put_contents($pendingFile, 'creating');

        // Calculate paths for the worker
        $scriptPath = dirname(__DIR__) . '/Scripts/backup_worker.php';
        $sourceDir = $this->sourceDir;
        $backupDir = $this->backupDir;

        // Escape arguments
        $cmdArgs = sprintf('"%s" "%s" "%s"', $baseFilename, $sourceDir, $backupDir);

        // Spawn Background Process
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B php \"$scriptPath\" $cmdArgs", "r"));
        } else {
            exec("php \"$scriptPath\" $cmdArgs > /dev/null 2>&1 &");
        }

        return $this->jsonResponse($response, [
            'message' => 'Backup started in background',
            'file' => $baseFilename . '.zip',
            'status' => 'creating'
        ]);
    }

    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $filename = $args['filename'];
        // Security check to prevent directory traversal
        $filename = basename($filename);
        $filepath = $this->backupDir . '/' . $filename;

        if (file_exists($filepath)) {
            unlink($filepath);
            return $this->jsonResponse($response, ['message' => 'Backup deleted']);
        }

        return $this->jsonResponse($response, ['error' => 'File not found'], 404);
    }

    public function download(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $filename = $args['filename'];
        $filename = basename($filename);
        $filepath = $this->backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            return $this->jsonResponse($response, ['error' => 'File not found'], 404);
        }

        // Output file directly
        // Note: For large files, X-Sendfile or generic stream copy is better.
        // Since we are in Slim, we use proper stream response.

        $fh = fopen($filepath, 'rb');
        $stream = new \Slim\Psr7\Stream($fh);

        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string) filesize($filepath))
            ->withBody($stream);
    }

    public function restore(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $filename = $data['filename'] ?? '';
        $filename = basename($filename);
        $filepath = $this->backupDir . '/' . $filename;

        if (!file_exists($filepath)) {
            return $this->jsonResponse($response, ['error' => 'File not found'], 404);
        }

        $zip = new \ZipArchive();
        if ($zip->open($filepath) === TRUE) {
            $zip->extractTo($this->sourceDir);
            $zip->close();
            return $this->jsonResponse($response, ['message' => 'Restored successfully']);
        }

        return $this->jsonResponse($response, ['error' => 'Failed to extract zip'], 500);
    }

    // Stub for createDbBackup since we removed the frontend button, but keeping controller ready if needed
    public function createDbBackup(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->jsonResponse($response, ['error' => 'Use Adminer for DB backups'], 400);
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
