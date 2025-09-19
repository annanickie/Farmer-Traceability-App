<?php
// includes/config.php

// Check if constants are already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'pgp_farmer_traceability');
}

if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}

if (!defined('DB_PASS')) {
    define('DB_PASS', '');
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/traceability/');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'PGP Farmer Traceability');
}

// Session configuration - only set if session is not active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    session_start();
}
?>