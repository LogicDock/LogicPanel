<?php

declare(strict_types=1);

namespace LogicPanel\Application\Controllers\Master;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use LogicPanel\Domain\Setting\Setting;
use Firebase\JWT\JWT;

class SettingsController
{
    private $configFile = __DIR__ . '/../../../../config/settings.json';

    public function get(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $settings = $this->loadSettings();
        return $this->jsonResponse($response, $settings);
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        $current = $this->loadSettings();

        // Merge allowed keys
        $allowed = [
            'company_name',
            'hostname',
            'server_ip', // Added server_ip
            'master_port',
            'user_port',
            'contact_email',
            'default_language',
            'timezone',
            'ns1',
            'ns2',
            'allow_registration',
            'shared_domain',
            'enable_ssl',
            'letsencrypt_email'
        ];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $val = trim((string) $data[$key]);

                // Basic Validation
                if ($key === 'hostname' && !empty($val)) {
                    if (!preg_match('/^[a-zA-Z0-9.-]+$/', $val)) {
                        return $this->jsonResponse($response, ['message' => 'Invalid hostname format'], 400);
                    }
                }

                if ($key === 'server_ip' && !empty($val)) {
                    if (!filter_var($val, FILTER_VALIDATE_IP)) {
                        return $this->jsonResponse($response, ['message' => 'Invalid IP address format'], 400);
                    }
                }

                $current[$key] = $val;
            }
        }

        // Check if ports changed compared to ACTUAL environment
        $restartRequired = false;
        $updates = [];

        $masterPort = (int) ($data['master_port'] ?? 967);
        $userPort = (int) ($data['user_port'] ?? 767);

        $currentMaster = (int) ($_ENV['MASTER_PORT'] ?? 967);
        $currentUser = (int) ($_ENV['USER_PORT'] ?? 767);

        if ($masterPort !== $currentMaster) {
            $updates['MASTER_PORT'] = $masterPort;
            $restartRequired = true;
        }

        if ($userPort !== $currentUser) {
            $updates['USER_PORT'] = $userPort;
            $restartRequired = true;
        }

        if (!empty($updates)) {
            $this->updateEnvFile($updates);
        }

        file_put_contents($this->configFile, json_encode($current, JSON_PRETTY_PRINT));

        if ($restartRequired) {
            // Trigger background restart with absolute path and logging
            $cmd = "nohup sh -c \"sleep 1 && cd /var/www/html && docker compose up -d --force-recreate app\" > /var/www/html/storage/logs/restart.log 2>&1 &";
            exec($cmd);

            return $this->jsonResponse($response, [
                'message' => 'Settings updated. Panel is restarting on new port(s)...',
                'settings' => $current,
                'restart' => true
            ]);
        }

        return $this->jsonResponse($response, ['message' => 'Settings updated successfully', 'settings' => $current]);
    }

    private function updateEnvFile(array $updates)
    {
        $envPath = '/var/www/html/.env';
        if (!file_exists($envPath)) {
            $envPath = dirname(__DIR__, 4) . '/.env';
            if (!file_exists($envPath))
                return;
        }

        $content = file_get_contents($envPath);

        foreach ($updates as $key => $value) {
            $keyEscaped = preg_quote($key, '/');
            // Support both standard and quoted values if they exist
            $pattern = "/^({$keyEscaped}=).*$/m";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, "{$key}={$value}", $content);
            } else {
                $content = rtrim($content) . "\n{$key}={$value}\n";
            }
        }

        file_put_contents($envPath, $content);
    }

    public function detectIp(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $providers = [
            "https://api.ipify.org",
            "https://ifconfig.me/ip",
            "https://ipinfo.io/ip",
            "https://checkip.amazonaws.com"
        ];

        $ip = '127.0.0.1';
        $success = false;

        foreach ($providers as $url) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Faster timeout per provider
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $result = curl_exec($ch);
                curl_close($ch);

                if ($result && filter_var(trim($result), FILTER_VALIDATE_IP)) {
                    $ip = trim($result);
                    $success = true;
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $this->jsonResponse($response, [
            'ip' => $ip,
            'success' => $success
        ]);
    }

    public function getRootTerminalToken(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Generate Short-lived JWT for Terminal Gateway
        $payload = [
            'iss' => 'logicpanel-backend',
            'aud' => 'logicpanel-gateway',
            'iat' => time(),
            'exp' => time() + 60,
            'sub' => 'root',
            'mode' => 'root',
            'container_id' => 'GATEWAY_LOCAL'
        ];

        $secret = $_ENV['JWT_SECRET'] ?? 'secret';
        $token = JWT::encode($payload, $secret, 'HS256');

        return $this->jsonResponse($response, [
            'token' => $token,
            'gateway_url' => 'ws://localhost:3002'
        ]);
    }

    private function loadSettings(): array
    {
        if (!file_exists($this->configFile)) {
            return [
                'company_name' => 'LogicPanel',
                'hostname' => 'server.cyberit.cloud',
                'server_ip' => '127.0.0.1', // Default IP
                'master_port' => 967,
                'user_port' => 767,
                'contact_email' => 'admin@cyberit.cloud',
                'default_language' => 'en',
                'timezone' => 'UTC',
                'ns1' => 'ns1.cyberit.cloud',
                'ns2' => 'ns2.cyberit.cloud',
                'allow_registration' => true,
                'shared_domain' => '', // Default shared domain
                'enable_ssl' => false,
                'letsencrypt_email' => 'admin@cyberit.cloud'
            ];
        }
        return json_decode(file_get_contents($this->configFile), true) ?? [];
    }

    private function jsonResponse(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
