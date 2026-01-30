<?php
// Helper script to bridge LogicPanel dashboard and Adminer auto-login
session_start();

// Security check: Ensure the user is actually logged into LogicPanel (via token)
if (!isset($_SESSION['lp_session_token']) || !is_string($_SESSION['lp_session_token'])) {
    http_response_code(403);
    die('Access Denied. Please login to LogicPanel first.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $server = $_POST['server'] ?? 'lp-mysql-mother';
    $username = $_POST['username'] ?? '';
    // Password intentionally excluded for security and user preference ("password excluded")
    $db = $_POST['db'] ?? '';
    $driver = $_POST['driver'] ?? 'server';

    // Construct Redirect URL parameters for Adminer
    // Adminer reads these from $_GET
    $params = [
        'server' => $server,
        'username' => $username,
        'db' => $db
    ];

    // Some drivers might need explicit key (e.g. pgsql)
    if ($driver !== 'server') {
        $params[$driver] = $server;
        if ($driver !== 'server')
            unset($params['server']); // Adminer uses key name as driver
    } else {
        // Default MySQL
    }

    // Actually, Adminer URL structure is: ?server=HOST&username=USER&db=DB
    // For other drivers: ?pgsql=HOST&username=USER...

    if ($driver !== 'server' && $driver !== 'mysql') {
        // Replace 'server' key with the driver name
        $params = [
            $driver => $server,
            'username' => $username,
            'db' => $db
        ];
    }

    // Redirect to Adminer
    $url = "adminer.php?" . http_build_query($params);
    header("Location: $url");
    exit;
}

// Fallback
header("Location: adminer.php");
exit;
