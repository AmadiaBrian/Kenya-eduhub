<?php
// Dashboard Main Router - Handles all dashboard routes
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

$route = $_GET['route'] ?? 'dashboard';
// Remove trailing slash from route
$route = rtrim($route, '/');

// Whitelist of allowed routes
$allowed_routes = [
    'dashboard',
    'profile',
    'settings',
    'maintenance'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}

// Authentication check for all routes
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login');
    exit;
}

// Session timeout check
try {
    if (!checkSessionTimeout($conn)) {
        header('Location: ../auth/logout');
        exit;
    }
} catch (Exception $e) {
    error_log("Session timeout check failed: " . $e->getMessage());
}

// Maintenance mode check
try {
    if (isMaintenanceMode($conn) && $route !== 'maintenance') {
        // Check if user is admin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        // Redirect to maintenance page if not admin
        if (!$user || $user['role'] !== 'admin') {
            header('Location: maintenance');
            exit;
        }
    }
} catch (Exception $e) {
    error_log("Maintenance mode check failed: " . $e->getMessage());
}

// Log dashboard access
logActivity('DASHBOARD_ACCESS', 'User accessed dashboard route', [
    'route' => $route,
    'user_role' => $_SESSION['user_role'] ?? 'unknown'
]);

// Security: Rate limiting for dashboard access
$dashboard_identifier = $_SERVER['REMOTE_ADDR'] . '_' . $_SESSION['user_id'] . '_dashboard';
if (!checkRateLimit($dashboard_identifier, 100, 300)) { // 100 requests per 5 minutes
    http_response_code(429);
    echo "Too many requests. Please try again later.";
    exit();
}

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();
echo '<script>window.currentCSRFToken = "' . $csrf_token . '";</script>';

// Route to the appropriate page
if ($route === 'dashboard') {
    require __DIR__ . '/dashboard.php';
} elseif ($route === 'profile') {
    require __DIR__ . '/profile.php';
} elseif ($route === 'settings') {
    require __DIR__ . '/settings.php';
} elseif ($route === 'maintenance') {
    require __DIR__ . '/maintenance.php';
} else {
    // Fallback to 404
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Page Not Found</h1>';
    exit;
}
?>