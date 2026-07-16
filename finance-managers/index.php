<?php
// Finance Managers Router
session_start();
require_once __DIR__ . '/../config.php';

// Get the requested route
$route = $_GET['route'] ?? 'login';

// Allowed routes whitelist
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'account',
    'profile',
    'fees',
    'invoices',
    'reports'
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
