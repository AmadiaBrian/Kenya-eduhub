<?php
// Auth Router - Handles all authentication routes
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

$route = $_GET['route'] ?? 'login';
// Remove trailing slash from route
$route = rtrim($route, '/');

// Whitelist of allowed routes
$allowed_routes = [
    'login',
    'register',
    'restore',
    'forgot_password',
    'reset_password',
    'verify',
    'logout',
    'verify-code'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}

// Handle logout separately (no authentication required)
if ($route === 'logout') {
    // Log activity before logout
    if (isset($_SESSION['user_id'])) {
        logActivity('USER_LOGOUT', 'User logged out', [
            'user_id' => $_SESSION['user_id'],
            'user_email' => $_SESSION['user_email'] ?? 'unknown'
        ]);
    }
    
    session_destroy();
    header('Location: ../index.php');
    exit();
}

// For protected routes, redirect if already logged in
$protected_routes = ['login', 'register', 'forgot_password'];
if (in_array($route, $protected_routes) && isset($_SESSION['user_id'])) {
    header('Location: ../dashboard');
    exit();
}

// Session timeout check for authenticated routes
$auth_routes = ['reset_password', 'verify'];
$public_routes = ['login', 'register', 'restore', 'forgot_password'];

if (in_array($route, $auth_routes) && isset($_SESSION['user_id'])) {
    try {
        if (!checkSessionTimeout($conn)) {
            header('Location: logout');
            exit;
        }
    } catch (Exception $e) {
        error_log("Session timeout check failed: " . $e->getMessage());
    }
}

// Security: Rate limiting for auth routes
$auth_identifier = $_SERVER['REMOTE_ADDR'] . '_auth_' . $route;
if (!checkRateLimit($auth_identifier, 5, 300)) { // 5 attempts per 5 minutes
    http_response_code(429);
    echo "Too many attempts. Please try again in 5 minutes.";
    exit();
}

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();
echo '<script>window.currentCSRFToken = "' . $csrf_token . '";</script>';

// Log auth access
logActivity('AUTH_ACCESS', 'User accessed auth route', [
    'route' => $route,
    'ip' => $_SERVER['REMOTE_ADDR']
]);

// Route to the appropriate page
if ($route === 'login') {
    require __DIR__ . '/login.php';
} elseif ($route === 'register') {
    require __DIR__ . '/register.php';
} elseif ($route === 'restore') {
    require __DIR__ . '/restore.php';
} elseif ($route === 'forgot_password') {
    require __DIR__ . '/forgot_password.php';
} elseif ($route === 'reset_password') {
    require __DIR__ . '/reset_password.php';
} elseif ($route === 'verify') {
    require __DIR__ . '/verify.php';
} elseif ($route === 'verify-code') {
    require __DIR__ . '/verify-code.php';
} elseif ($route === 'logout') {
    require __DIR__ . '/logout.php';
} else {
    // Fallback to 404
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}
?>