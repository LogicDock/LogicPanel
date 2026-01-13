<?php
/**
 * LogicPanel WHMCS Provisioning Module
 * 
 * @package    WHMCS
 * @author     LogicDock
 * @version    1.0.0
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

use WHMCS\Database\Capsule;

/**
 * Module metadata
 */
function logicpanel_MetaData()
{
    return [
        'DisplayName' => 'LogicPanel - Node.js Hosting',
        'APIVersion' => '1.1',
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '80',
        'DefaultSSLPort' => '443',
    ];
}

/**
 * Module configuration options
 */
function logicpanel_ConfigOptions()
{
    // Check if refresh is requested
    if (isset($_GET['refresh_packages']) && $_GET['refresh_packages'] == '1') {
        logicpanel_clearPackagesCache();
    }

    // Fetch packages from LogicPanel API
    $packages = logicpanel_getPackages();
    $packageOptions = [];

    foreach ($packages as $pkg) {
        $packageOptions[$pkg['name']] = $pkg['display_name'] . ' (' . $pkg['resources']['memory_display'] . ' RAM, ' . $pkg['resources']['cpu_display'] . ')';
    }

    if (empty($packageOptions)) {
        $packageOptions['starter'] = 'Starter (Configure API first)';
    }

    // Build refresh link - preserve current page context
    $productId = $_GET['id'] ?? '';
    $tab = $_GET['tab'] ?? '3'; // Module Settings is tab 3
    if ($productId) {
        $refreshUrl = 'configproducts.php?action=edit&id=' . urlencode($productId) . '&tab=' . urlencode($tab) . '&refresh_packages=1';
    } else {
        $refreshUrl = 'configproducts.php?refresh_packages=1';
    }

    return [
        'Package' => [
            'FriendlyName' => 'Hosting Package',
            'Type' => 'dropdown',
            'Options' => $packageOptions,
            'Description' => 'Select resource package from LogicPanel | <a href="' . htmlspecialchars($refreshUrl) . '" style="color:#0066cc;">🔄 Refresh Packages</a>',
        ],
        'Node Version' => [
            'FriendlyName' => 'Node.js Version',
            'Type' => 'dropdown',
            'Options' => [
                '18' => 'Node.js 18 LTS',
                '20' => 'Node.js 20 LTS',
                '22' => 'Node.js 22',
            ],
            'Default' => '20',
            'Description' => 'Default Node.js version',
        ],
        'Default Port' => [
            'FriendlyName' => 'Application Port',
            'Type' => 'text',
            'Size' => '10',
            'Default' => '3000',
            'Description' => 'Default port for Node.js app',
        ],
    ];
}

/**
 * Clear packages cache
 */
function logicpanel_clearPackagesCache()
{
    try {
        Capsule::table('tblconfiguration')->where('setting', 'logicpanel_packages')->delete();
    } catch (Exception $e) {
        // Ignore errors
    }
}

/**
 * Create account when order is activated
 */
function logicpanel_CreateAccount(array $params)
{
    try {
        $postData = [
            'whmcs_user_id' => $params['userid'],
            'whmcs_service_id' => $params['serviceid'],
            'email' => $params['clientsdetails']['email'],
            'username' => $params['clientsdetails']['email'],
            'name' => $params['clientsdetails']['firstname'] . ' ' . $params['clientsdetails']['lastname'],
            'domain' => $params['domain'],
            'package' => $params['configoption1'], // Package name
            'node_version' => $params['configoption2'] ?: '20',
            'port' => $params['configoption3'] ?: 3000,
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/account/create', $postData);

        if ($response['success']) {
            // Store service ID in custom field
            Capsule::table('tblhosting')
                ->where('id', $params['serviceid'])
                ->update([
                    'username' => 'lp_' . $response['service_id'],
                    'password' => encrypt('sso_enabled'),
                ]);

            return 'success';
        } else {
            return $response['error'] ?? 'Unknown error occurred';
        }
    } catch (Exception $e) {
        logModuleCall('logicpanel', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Suspend account
 */
function logicpanel_SuspendAccount(array $params)
{
    try {
        $postData = [
            'whmcs_service_id' => $params['serviceid'],
            'reason' => $params['suspendreason'] ?? 'Suspended by WHMCS',
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/account/suspend', $postData);

        if ($response['success']) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to suspend account';
        }
    } catch (Exception $e) {
        logModuleCall('logicpanel', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Unsuspend account
 */
function logicpanel_UnsuspendAccount(array $params)
{
    try {
        $postData = [
            'whmcs_service_id' => $params['serviceid'],
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/account/unsuspend', $postData);

        if ($response['success']) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to unsuspend account';
        }
    } catch (Exception $e) {
        logModuleCall('logicpanel', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Terminate account
 */
function logicpanel_TerminateAccount(array $params)
{
    try {
        $postData = [
            'whmcs_service_id' => $params['serviceid'],
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/account/terminate', $postData);

        if ($response['success']) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to terminate account';
        }
    } catch (Exception $e) {
        logModuleCall('logicpanel', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Change password (regenerate SSO)
 */
function logicpanel_ChangePassword(array $params)
{
    try {
        $postData = [
            'whmcs_service_id' => $params['serviceid'],
            'whmcs_user_id' => $params['userid'],
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/account/password', $postData);

        if ($response['success']) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to change password';
        }
    } catch (Exception $e) {
        logModuleCall('logicpanel', __FUNCTION__, $params, $e->getMessage(), $e->getTraceAsString());
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Client area output
 */
function logicpanel_ClientArea(array $params)
{
    // Generate SSO URL
    try {
        $postData = [
            'whmcs_service_id' => $params['serviceid'],
            'whmcs_user_id' => $params['userid'],
        ];

        $response = logicpanel_apiCall($params, 'POST', '/api/v1/sso/generate', $postData);

        $ssoUrl = $response['sso_url'] ?? '#';

    } catch (Exception $e) {
        $ssoUrl = '#';
    }

    // Get service info
    try {
        $serviceInfo = logicpanel_apiCall($params, 'GET', '/api/v1/service/' . $params['serviceid']);
    } catch (Exception $e) {
        $serviceInfo = ['service' => []];
    }

    $panelUrl = logicpanel_getApiUrl($params);

    return [
        'tabOverviewReplacementTemplate' => 'templates/overview.tpl',
        'templateVariables' => [
            'ssoUrl' => $ssoUrl,
            'panelUrl' => $panelUrl,
            'serviceInfo' => $serviceInfo['service'] ?? [],
            'domain' => $params['domain'],
            'package' => $params['configoption1'],
            'nodeVersion' => $params['configoption2'] ?: '20',
        ],
    ];
}

/**
 * Admin area custom buttons
 */
function logicpanel_AdminCustomButtonArray()
{
    return [
        'Restart Container' => 'restartContainer',
        'Sync Status' => 'syncStatus',
    ];
}

/**
 * Restart container action
 */
function logicpanel_restartContainer(array $params)
{
    try {
        $response = logicpanel_apiCall($params, 'POST', '/api/v1/service/' . $params['serviceid'] . '/restart', [
            'whmcs_service_id' => $params['serviceid'],
        ]);

        if ($response['success']) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to restart container';
        }
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Sync status action
 */
function logicpanel_syncStatus(array $params)
{
    try {
        $response = logicpanel_apiCall($params, 'GET', '/api/v1/service/' . $params['serviceid']);

        if ($response['success'] && isset($response['service'])) {
            return 'success';
        } else {
            return $response['error'] ?? 'Failed to sync status';
        }
    } catch (Exception $e) {
        return 'Error: ' . $e->getMessage();
    }
}

/**
 * Test connection
 */
function logicpanel_TestConnection(array $params)
{
    try {
        $response = logicpanel_apiCall($params, 'GET', '/api/v1/health');

        if (isset($response['status']) && $response['status'] === 'healthy') {
            return [
                'success' => true,
                'message' => 'Connection successful! Docker: ' . ($response['docker'] ?? 'ok') . ', Database: ' . ($response['database'] ?? 'ok'),
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Server response: Docker=' . ($response['docker'] ?? 'unknown') . ', Database=' . ($response['database'] ?? 'unknown'),
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Connection failed: ' . $e->getMessage(),
        ];
    }
}

/**
 * API call helper
 */
function logicpanel_apiCall(array $params, string $method, string $endpoint, array $data = [])
{
    $apiUrl = logicpanel_getApiUrl($params);
    $apiKey = $params['serverusername'] ?? '';
    $apiSecret = $params['serverpassword'] ?? '';

    $url = rtrim($apiUrl, '/') . $endpoint;

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-Key: ' . $apiKey,
        'X-API-Secret: ' . $apiSecret,
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    // SSL verification - disable for localhost/development
    $hostname = $params['serverhostname'] ?? '';
    $isLocalhost = in_array($hostname, ['localhost', '127.0.0.1', '::1']) ||
        strpos($hostname, '.local') !== false ||
        strpos($hostname, '.test') !== false;

    if ($isLocalhost || empty($params['serversecure'])) {
        // Disable SSL verification for local development
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    } else {
        // Enable SSL verification for production
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }

    $decoded = json_decode($response, true);

    if ($httpCode >= 400) {
        throw new Exception($decoded['error'] ?? 'HTTP Error: ' . $httpCode);
    }

    logModuleCall('logicpanel', $endpoint, $data, $response, $decoded);

    return $decoded ?: [];
}

/**
 * Get API URL from server params
 */
function logicpanel_getApiUrl(array $params): string
{
    $protocol = !empty($params['serversecure']) ? 'https' : 'http';
    $hostname = $params['serverhostname'] ?? '';
    $port = $params['serverport'] ?? ($protocol === 'https' ? 443 : 80);

    $url = $protocol . '://' . $hostname;

    if (($protocol === 'https' && $port != 443) || ($protocol === 'http' && $port != 80)) {
        $url .= ':' . $port;
    }

    // For localhost development, add the path to LogicPanel
    $isLocalhost = in_array($hostname, ['localhost', '127.0.0.1', '::1']);
    if ($isLocalhost) {
        $url .= '/logicpanel/public';
    }

    return $url;
}

/**
 * Get packages from LogicPanel API
 */
/**
 * Get packages from LogicPanel API
 */
function logicpanel_getPackages(): array
{
    // Try to get from cache first
    $cacheKey = 'logicpanel_packages';

    try {
        $cached = Capsule::table('tblconfiguration')->where('setting', $cacheKey)->first();

        if ($cached && isset($cached->value)) {
            $updatedAt = $cached->updated_at ?? $cached->created_at ?? date('Y-m-d H:i:s');
            // Cache for 1 hour
            if (strtotime($updatedAt) > (time() - 3600)) {
                $packages = json_decode($cached->value, true);
                if ($packages) {
                    return $packages;
                }
            }
        }
    } catch (Exception $e) {
        // Cache read failed, continue to fetch
    }

    // Get first LogicPanel server
    try {
        $server = Capsule::table('tblservers')
            ->where('type', 'logicpanel')
            ->where('disabled', 0)
            ->first();

        if (!$server) {
            return [];
        }

        // WHMCS stores username/password encrypted in tblservers
        $username = decrypt($server->username);
        $password = decrypt($server->password);

        // Sanitize credentials (remove non-printable characters to avoid header errors)
        $username = preg_replace('/[\x00-\x1F\x7F]/', '', $username);
        $password = preg_replace('/[\x00-\x1F\x7F]/', '', $password);

        $params = [
            'serverhostname' => $server->hostname,
            'serverport' => $server->port,
            'serversecure' => $server->secure,
            'serverusername' => $username,  // API Key
            'serverpassword' => $password,  // API Secret
        ];

        // Note: /api/packages is outside the v1 group in routes.php
        $response = logicpanel_apiCall($params, 'GET', '/api/packages');
        $packages = $response['packages'] ?? [];

        // Cache the packages if we got results
        if (!empty($packages)) {
            $now = date('Y-m-d H:i:s');
            if ($cached) {
                Capsule::table('tblconfiguration')
                    ->where('setting', $cacheKey)
                    ->update([
                        'value' => json_encode($packages),
                        'updated_at' => $now
                    ]);
            } else {
                Capsule::table('tblconfiguration')->insert([
                    'setting' => $cacheKey,
                    'value' => json_encode($packages),
                    'created_at' => $now,
                    'updated_at' => $now
                ]);
            }
        }

        return $packages;
    } catch (Exception $e) {
        return [];
    }
}
