<?php
/**
 * LogicPanel Adminer Integration
 * 
 * We now use 'lp_session_token' for LogicPanel authentication to avoid
 * conflict with Adminer's 'token' which must be an integer (PHP 8.2+).
 */

session_start();

// 1. Verify LogicPanel Access using our unique session key
$is_authenticated = isset($_SESSION['lp_session_token']) && is_string($_SESSION['lp_session_token']);

if (!$is_authenticated) {
	header('Location: /login');
	exit;
}

// 2. Clear Adminer's token if it's in a bad state (string instead of int)
// This prevents the "int ^ string" TypeError in PHP 8.2+
if (isset($_SESSION['token']) && !is_int($_SESSION['token'])) {
	unset($_SESSION['token']);
}

// 3. Load Adminer Core
// Adminer will use the default 'PHPSESSID' session but its 'token' key
// will no longer collide with LogicPanel's authentication token.
include __DIR__ . '/adminer_core.php';