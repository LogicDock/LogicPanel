<?php
/**
 * LogicPanel - Base Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;

abstract class BaseController
{
    /**
     * Render a template
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        // Get theme from session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $theme = $_SESSION['theme'] ?? 'auto';
        $data['theme'] = $theme;
        $data['user_name'] = $_SESSION['user_name'] ?? null;
        $data['user_role'] = $_SESSION['user_role'] ?? null;
        $data['base_url'] = '/logicpanel/public';

        // Build template path
        $templatePath = BASE_PATH . '/templates/' . $template . '.php';

        if (!file_exists($templatePath)) {
            $response->getBody()->write("Template not found: {$template}");
            return $response->withStatus(500);
        }

        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        // Write to response
        $response->getBody()->write($content);
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    /**
     * JSON response helper
     */
    protected function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Redirect helper
     */
    protected function redirect(Response $response, string $url): Response
    {
        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }

    /**
     * Get current user from session
     */
    protected function getCurrentUser(): ?object
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        return (object) [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user'
        ];
    }

    /**
     * Check if current user is admin
     */
    protected function isAdmin(): bool
    {
        $user = $this->getCurrentUser();
        return $user && $user->role === 'admin';
    }
}
