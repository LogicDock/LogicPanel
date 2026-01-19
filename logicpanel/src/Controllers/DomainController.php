<?php
/**
 * LogicPanel - Domain Controller
 */

namespace LogicPanel\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use LogicPanel\Models\Service;
use LogicPanel\Models\Domain;
use LogicPanel\Services\DockerService;
use Illuminate\Database\Capsule\Manager as DB;

class DomainController extends BaseController
{
    private DockerService $docker;

    public function __construct()
    {
        $this->docker = new DockerService();
    }

    /**
     * List all domains
     */
    public function index(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');

        $services = Service::where('user_id', $user->id)
            ->with('domains')
            ->get();

        $allDomains = [];
        foreach ($services as $service) {
            foreach ($service->domains as $domain) {
                $domain->service_name = $service->name;
                $allDomains[] = $domain;
            }
        }

        return $this->render($response, 'domains/index', [
            'title' => 'Domains - LogicPanel',
            'domains' => $allDomains,
            'services' => $services
        ]);
    }

    /**
     * Show domains for a specific service
     */
    public function show(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $serviceId = (int) $args['serviceId'];

        $service = Service::where('id', $serviceId)
            ->where('user_id', $user->id)
            ->with('domains')
            ->first();

        if (!$service) {
            return $this->jsonResponse($response, ['error' => 'Service not found'], 404);
        }

        return $this->render($response, 'domains/show', [
            'title' => 'Domains - ' . $service->name,
            'service' => $service,
            'domains' => $service->domains
        ]);
    }

    /**
     * Add domain to service
     */
    public function add(Request $request, Response $response, array $args): Response
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

        $domainName = trim(strtolower($data['domain'] ?? ''));

        // Validate domain
        if (empty($domainName)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Domain name is required'], 400);
        }

        if (!preg_match('/^[a-z0-9]+([\-\.][a-z0-9]+)*\.[a-z]{2,}$/i', $domainName)) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Invalid domain format'], 400);
        }

        // Check if domain already exists
        $exists = Domain::where('domain', $domainName)->exists();
        if ($exists) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Domain already in use'], 400);
        }

        // Check domain limit
        $maxDomains = $this->getSetting('max_domains_per_service', 5);
        $currentCount = Domain::where('service_id', $serviceId)->count();
        if ($currentCount >= $maxDomains) {
            return $this->jsonResponse($response, ['success' => false, 'error' => "Maximum {$maxDomains} domains allowed per service"], 400);
        }

        // Create domain record
        $isPrimary = $currentCount === 0; // First domain is primary
        $domain = new Domain();
        $domain->service_id = $serviceId;
        $domain->domain = $domainName;
        $domain->is_primary = $isPrimary;
        $domain->ssl_enabled = true;
        $domain->save();

        // Update container environment for NGINX proxy
        $this->updateContainerDomains($service);

        $this->logActivity($user->id, $serviceId, 'domain_add', "Added domain: {$domainName}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Domain added successfully',
            'domain' => $domain
        ]);
    }

    /**
     * Remove domain from service
     */
    public function remove(Request $request, Response $response, array $args): Response
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

        $domainId = (int) ($data['domain_id'] ?? 0);
        $domain = Domain::where('id', $domainId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$domain) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Domain not found'], 404);
        }

        // Cannot remove if it's the only domain
        $domainCount = Domain::where('service_id', $serviceId)->count();
        if ($domainCount <= 1) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Cannot remove the only domain'], 400);
        }

        $domainName = $domain->domain;
        $wasPrimary = $domain->is_primary;

        // Delete domain
        $domain->delete();

        // If was primary, set another domain as primary
        if ($wasPrimary) {
            $newPrimary = Domain::where('service_id', $serviceId)->first();
            if ($newPrimary) {
                $newPrimary->is_primary = true;
                $newPrimary->save();
            }
        }

        // Update container
        $this->updateContainerDomains($service);

        $this->logActivity($user->id, $serviceId, 'domain_remove', "Removed domain: {$domainName}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Domain removed successfully'
        ]);
    }

    /**
     * Set primary domain
     */
    public function setPrimary(Request $request, Response $response, array $args): Response
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

        $domainId = (int) ($data['domain_id'] ?? 0);
        $domain = Domain::where('id', $domainId)
            ->where('service_id', $serviceId)
            ->first();

        if (!$domain) {
            return $this->jsonResponse($response, ['success' => false, 'error' => 'Domain not found'], 404);
        }

        // Remove primary from all domains of this service
        Domain::where('service_id', $serviceId)->update(['is_primary' => false]);

        // Set new primary
        $domain->is_primary = true;
        $domain->save();

        // Update container
        $this->updateContainerDomains($service);

        $this->logActivity($user->id, $serviceId, 'domain_primary', "Set primary domain: {$domain->domain}");

        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Primary domain updated'
        ]);
    }

    /**
     * Update container environment with new domains
     * Since environment variables require container recreation
     */
    private function updateContainerDomains(Service $service): void
    {
        if (!$service->container_id) {
            return;
        }

        // 1. Get current package for re-provisioning
        $package = \LogicPanel\Models\Package::find($service->package_id);
        if (!$package)
            return;

        // 2. Stop and remove old container
        $oldContainerId = $service->container_id;
        $this->docker->stopContainer($oldContainerId);
        $this->docker->removeContainer($oldContainerId);

        // 3. Re-provision new container (this will pick up new domain list)
        $serviceController = new \LogicPanel\Controllers\ServiceController();
        $result = $serviceController->provisionContainer($service, $package);

        if ($result['success']) {
            $service->container_id = $result['container_id'];
            $service->save();
        }
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
