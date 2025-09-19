<?php
// logout.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize Auth class
$auth = new Auth();

// Log the logout action
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    $role = $_SESSION['role'] ?? 'user';
    
    // Log the logout action (you could save this to a database)
    error_log("User logout: ID $userId, Username: $username, Role: $role");
}

// Destroy the session
session_unset();
session_destroy();

// Clear any session cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page with logout message
header("Location: login.php?logout=success");
exit();
?>