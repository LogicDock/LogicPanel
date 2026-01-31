<?php
/**
 * LogicPanel Adminer Integration - Sessionless Auth
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

try {
	$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
	$dotenv->load();
} catch (\Exception $e) {
}

$jwtSecret = $_ENV['JWT_SECRET'] ?? 'your-super-secret-key-change-in-production';
$authToken = $_GET['auth'] ?? $_COOKIE['lp_adminer_auth'] ?? null;
$is_authenticated = false;

if ($authToken) {
	try {
		JWT::decode($authToken, new Key($jwtSecret, 'HS256'));
		$is_authenticated = true;

		if (!isset($_COOKIE['lp_adminer_auth'])) {
			setcookie('lp_adminer_auth', $authToken, [
				'expires' => time() + 3600,
				'path' => '/public/',
				'httponly' => true,
				'samesite' => 'Lax'
			]);
		}
	} catch (\Exception $e) {
	}
}

if (!$is_authenticated) {
	header('Location: /login?error=adminer_auth');
	exit;
}

session_name('ADMINER_SESSION');
@session_start();

if (isset($_SESSION['token']) && !is_int($_SESSION['token'])) {
	unset($_SESSION['token']);
}

include __DIR__ . '/adminer_core.php';