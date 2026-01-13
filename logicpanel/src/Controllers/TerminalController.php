<?php
/**
 * LogicPanel - Terminal Controller
 * Handles terminal/exec operations in containers
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class TerminalController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * Show terminal page
     */
    public function index(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$service) {
            return $this->render($response, 'errors/404', [
                'title' => 'Not Found - LogicPanel',
                'message' => 'Service not found'
            ], 404);
        }

        if (!$service->container_id) {
            return $this->render($response, 'errors/error', [
                'title' => 'Error - LogicPanel',
                'message' => 'Container not created for this service'
            ]);
        }

        return $this->render($response, 'terminal/index', [
            'title' => 'Terminal - ' . $service->name,
            'service' => $service
        ]);
    }

    /**
     * Execute command in container
     */
    public function exec(Request $request, Response $response, array $args): Response
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

        if ($service->isSuspended()) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Service is suspended'], 403);
        }

        if (!$service->container_id) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Container not found'], 400);
        }

        $command = trim($data['command'] ?? '');

        if (empty($command)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Command is required'], 400);
        }

        // Security: Block dangerous commands
        $blockedCommands = [
            'rm -rf /',
            'dd if=',
            'mkfs',
            ':(){:|:&};:',
            'chmod 777 /',
            'chown -R',
            'shutdown',
            'reboot',
            'halt',
            'poweroff'
        ];

        foreach ($blockedCommands as $blocked) {
            if (stripos($command, $blocked) !== false) {
                return $this->jsonResponse($response, [
                    'success' => false,
                    'error' => 'This command is not allowed for security reasons'
                ], 403);
            }
        }

        // Execute command
        $cmd = ['sh', '-c', "cd /app && {$command}"];
        $output = $this->docker->execInContainer($service->container_id, $cmd, true);

        if ($output !== null) {
            // Log command execution
            $this->logActivity($user->id, $serviceId, 'terminal_exec', "Executed: {$command}");

            return $this->jsonResponse($response, [
                'success' => true,
                'output' => $output
            ]);
        }

        return $this->jsonResponse($response, [
            'success' => false,
            'error' => 'Command execution failed',
            'output' => $this->docker->getLastError()
        ], 500);
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
