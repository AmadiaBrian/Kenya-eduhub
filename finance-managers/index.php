<?php
// Finance Managers Router
session_start();
require_once __DIR__ . '/../config.php';

// Get the requested route
$route = $_GET['route'] ?? 'login';

// Handle API routes (no authentication for API files)
if (strpos($route, 'api/') === 0) {
    $api_file = __DIR__ . '/' . $route . '.php';
    if (file_exists($api_file)) {
        // Set JSON header for API responses
        header('Content-Type: application/json');
        require_once $api_file;
        exit;
    } else {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'API file not found']);
        exit;
    }
}

// Handle M-Pesa B2C callbacks (no authentication required)
if ($route === 'b2c/b2c_result') {
    require_once __DIR__ . '/b2c/b2c_result.php';
    exit;
}

if ($route === 'b2c/b2c_timeout') {
    require_once __DIR__ . '/b2c/b2c_timeout.php';
    exit;
}

// Handle file access for report_files and receipts (only for files with extensions)
if ((strpos($route, 'report_files/') === 0 || strpos($route, 'receipts/') === 0) && strpos($route, '.') !== false) {
    $file_path = __DIR__ . '/' . $route;
    if (file_exists($file_path) && is_file($file_path)) {
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $allowed_extensions = ['pdf', 'csv', 'xlsx', 'xls'];
        
        if (in_array($extension, $allowed_extensions)) {
            // Set appropriate content type
            $content_types = [
                'pdf' => 'application/pdf',
                'csv' => 'text/csv',
                'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xls' => 'application/vnd.ms-excel'
            ];
            
            header('Content-Type: ' . ($content_types[$extension] ?? 'application/octet-stream'));
            header('Content-Disposition: inline; filename="' . basename($file_path) . '"');
            header('Content-Length: ' . filesize($file_path));
            readfile($file_path);
            exit;
        }
    }
}

// Allowed routes whitelist
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'account',
    'profile',
    'fees',
    'invoices',
    'reports',
    'reminders'
];

// Handle logout
if ($route === 'logout') {
    session_destroy();
    header('Location: login');
    exit;
}

// Handle login route
if ($route === 'login') {
    // If already logged in, redirect to dashboard
    if (isset($_SESSION['finance_manager_id'])) {
        header('Location: dashboard');
        exit;
    }
    require_once __DIR__ . '/login.php';
    exit;
}

// Check authentication for all other routes
if (!isset($_SESSION['finance_manager_id'])) {
    header('Location: login');
    exit;
}

// Validate route
if (!in_array($route, $allowed_routes)) {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
    exit;
}

// Route to the appropriate page
$page_file = __DIR__ . '/' . $route . '.php';
if (file_exists($page_file)) {
    require_once $page_file;
} else {
    http_response_code(404);
    require_once __DIR__ . '/404.php';
}
