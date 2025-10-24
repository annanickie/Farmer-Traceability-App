<?php
// includes/config.php

// Check if constants are already defined
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}

if (!defined('DB_NAME')) {
    define('DB_NAME', 'u164129621_prpip'); // Your Hostinger database name
}

if (!defined('DB_USER')) {
    define('DB_USER', 'u164129621_prpip'); // Your Hostinger database username
}

if (!defined('DB_PASS')) {
    define('DB_PASS', 'Occulteast@1'); // Replace with your actual database password
}

if (!defined('BASE_URL')) {
    define('BASE_URL', 'https://pgptrace.in/'); // Your actual domain
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'PGP Farmer Traceability');
}

// Document verification settings
if (!defined('ENABLE_STRICT_VERIFICATION')) {
    define('ENABLE_STRICT_VERIFICATION', false); // Set to true to block registration on duplicate documents
}

if (!defined('AADHAAR_VERIFICATION_REQUIRED')) {
    define('AADHAAR_VERIFICATION_REQUIRED', false);
}

if (!defined('PAN_VERIFICATION_REQUIRED')) {
    define('PAN_VERIFICATION_REQUIRED', false);
}

// File upload settings
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
}

if (!defined('ALLOWED_IMAGE_TYPES')) {
    define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
}

if (!defined('ALLOWED_DOCUMENT_TYPES')) {
    define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
}

// Geotag settings
if (!defined('GEOTAG_ENABLED')) {
    define('GEOTAG_ENABLED', true);
}

if (!defined('MAX_GEOTAG_ACCURACY')) {
    define('MAX_GEOTAG_ACCURACY', 100); // Maximum accuracy in meters
}

// Upload paths
if (!defined('UPLOAD_BASE_PATH')) {
    define('UPLOAD_BASE_PATH', dirname(__DIR__) . '/uploads/');
}

// Simple environment check - only define if not already defined
if (!defined('IS_LOCALHOST')) {
    $is_localhost = (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || 
                     strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false ||
                     strpos($_SERVER['HTTP_HOST'], 'pgptrace.in') === false); // Adjust for your domain
    define('IS_LOCALHOST', $is_localhost);
}

// Error reporting
if (defined('IS_LOCALHOST') && IS_LOCALHOST) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Session configuration - only set if session is not active
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_lifetime', 0);
    
    // Add basic security for production
    if (defined('IS_LOCALHOST') && !IS_LOCALHOST) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 1); // Only if using HTTPS
    }
    
    session_start();
}

// Simple utility function - CHECK IF NOT ALREADY DEFINED
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}

// File upload utility function
if (!function_exists('validateFileUpload')) {
    function validateFileUpload($file, $allowed_types = null, $max_size = null) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'File upload error: ' . $file['error']];
        }
        
        $max_size = $max_size ?: MAX_FILE_SIZE;
        $allowed_types = $allowed_types ?: array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOCUMENT_TYPES);
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => 'File size too large. Maximum ' . ($max_size / 1024 / 1024) . 'MB allowed.'];
        }
        
        if (!in_array($file_extension, $allowed_types)) {
            return ['success' => false, 'error' => 'Invalid file type. Allowed types: ' . implode(', ', $allowed_types)];
        }
        
        return ['success' => true];
    }
}
?>