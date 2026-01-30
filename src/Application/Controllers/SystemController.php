<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SystemController
{
    public function stats(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stats = $this->getSystemStats();
        return $this->jsonResponse($response, $stats);
    }

    private function getSystemStats(): array
    {
        $cpu = 0;

        $memory = [
            'total' => '0 B',
            'free' => '0 B',
            'used' => '0 B',
            'percent' => 0
        ];
        $disk = [
            'total' => '0 B',
            'free' => '0 B',
            'used' => '0 B',
            'percent' => 0
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            // CPU
            try {
                $psCpu = shell_exec('powershell -Command "(Get-CimInstance Win32_Processor | Measure-Object -Property LoadPercentage -Average).Average"');
                if ($psCpu !== null) {
                    $cpu = (int) trim($psCpu);
                }
            } catch (\Exception $e) {
            }

            // Memory
            try {
                $psMem = shell_exec('powershell -Command "(Get-CimInstance Win32_OperatingSystem | Select-Object FreePhysicalMemory,TotalVisibleMemorySize | ConvertTo-Json)"');
                $memData = json_decode($psMem, true);

                if (is_array($memData)) {
                    $totalBytes = ((int) $memData['TotalVisibleMemorySize'] ?? 0) * 1024;
                    $freeBytes = ((int) $memData['FreePhysicalMemory'] ?? 0) * 1024;
                    $usedBytes = $totalBytes - $freeBytes;
                    $percent = ($totalBytes > 0) ? round(($usedBytes / $totalBytes) * 100) : 0;

                    $memory = [
                        'total' => $this->formatBytes($totalBytes),
                        'free' => $this->formatBytes($freeBytes),
                        'used' => $this->formatBytes($usedBytes),
                        'percent' => $percent
                    ];
                }
            } catch (\Exception $e) {
            }

            // Disk
            try {
                $drive = substr(__DIR__, 0, 3); // e.g. "C:\"
                $totalDisk = disk_total_space($drive);
                $freeDisk = disk_free_space($drive);
                if ($totalDisk !== false) {
                    $usedDisk = $totalDisk - $freeDisk;
                    $diskPercent = ($totalDisk > 0) ? round(($usedDisk / $totalDisk) * 100) : 0;

                    $disk = [
                        'total' => $this->formatBytes($totalDisk),
                        'free' => $this->formatBytes($freeDisk),
                        'used' => $this->formatBytes($usedDisk),
                        'percent' => $diskPercent
                    ];
                }
            } catch (\Exception $e) {
            }

        } else {
            // Linux Implementation (Container Aware)
            $load = sys_getloadavg();
            if ($load) {
                $cpu = (int) ($load[0] * 100);
                if ($cpu > 100)
                    $cpu = 100;
            }

            $memTotal = 0;
            $memUsed = 0;

            if (file_exists('/sys/fs/cgroup/memory.current')) {
                $memUsed = (int) file_get_contents('/sys/fs/cgroup/memory.current');
                $memMax = trim(file_get_contents('/sys/fs/cgroup/memory.max'));
                if ($memMax !== 'max')
                    $memTotal = (int) $memMax;
            } else if (file_exists('/sys/fs/cgroup/memory/memory.usage_in_bytes')) {
                $memUsed = (int) file_get_contents('/sys/fs/cgroup/memory/memory.usage_in_bytes');
                if (file_exists('/sys/fs/cgroup/memory/memory.limit_in_bytes')) {
                    $memValid = file_get_contents('/sys/fs/cgroup/memory/memory.limit_in_bytes');
                    if ((float) $memValid < 1e15)
                        $memTotal = (int) $memValid;
                }
            }

            if ($memTotal <= 0 && is_readable('/proc/meminfo')) {
                $memInfo = file_get_contents('/proc/meminfo');
                if (preg_match('/MemTotal:\s+(\d+) kB/', $memInfo, $matches))
                    $memTotal = (int) $matches[1] * 1024;
                if ($memUsed <= 0 && preg_match('/MemAvailable:\s+(\d+) kB/', $memInfo, $matches)) {
                    $memAvailable = (int) $matches[1] * 1024;
                    $memUsed = $memTotal - $memAvailable;
                }
            }

            $memPercent = ($memTotal > 0) ? round(($memUsed / $memTotal) * 100) : 0;
            $memory = [
                'total' => $this->formatBytes($memTotal),
                'free' => $this->formatBytes(max(0, $memTotal - $memUsed)),
                'used' => $this->formatBytes(max(0, $memUsed)),
                'percent' => $memPercent
            ];

            $totalDisk = disk_total_space('/');
            $freeDisk = disk_free_space('/');
            $usedDisk = $totalDisk - $freeDisk;
            $diskPercent = ($totalDisk > 0) ? round(($usedDisk / $totalDisk) * 100) : 0;
            $disk = [
                'total' => $this->formatBytes($totalDisk),
                'free' => $this->formatBytes($freeDisk),
                'used' => $this->formatBytes($usedDisk),
                'percent' => $diskPercent
            ];
        }

        return ['cpu' => $cpu, 'memory' => $memory, 'disk' => $disk];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
