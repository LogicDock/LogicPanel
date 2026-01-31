<?php
/**
 * LogicPanel Adminer - Standalone Version
 * No session, no token, no authentication wrapper
 * Simply includes the Adminer core
 */

// Start a fresh session just for Adminer's internal CSRF protection
session_name('ADMINER_STANDALONE');
session_start();

// Clear any problematic session token left by older versions
if (isset($_SESSION['token']) && !is_int($_SESSION['token'])) {
	unset($_SESSION['token']);
}

// Include Adminer core directly
include __DIR__ . '/adminer_core.php';