<?php
// Admin Main Router - Handles all admin routes
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

$route = $_GET['route'] ?? 'dashboard';
// Remove trailing slash from route
$route = rtrim($route, '/');

// Whitelist of allowed routes
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'users',
    'resources',
    'reports',
    'settings',
    'logs',
    'edit-resource',
    'schools',
    'schools-add',
    'schools-view',
    'schools-edit',
    'school-accounts'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}

// Handle login route separately (no auth required)
if ($route === 'login') {
    require __DIR__ . '/login.php';
    exit;
}

// Handle logout route
if ($route === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login');
    exit;
}

// Authentication check for all other routes
if (!isset($_SESSION['user_id'])) {
    header('Location: login');
    exit;
}

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit;
}

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();
echo '<script>window.currentCSRFToken = "' . $csrf_token . '";</script>';

// Route to the appropriate page
if ($route === 'dashboard') {
    // Dashboard content - fetch statistics
    $user_id = $_SESSION['user_id'];
    $total_users = 0;
    $total_resources = 0;
    $total_downloads = 0;
    $recent_users = [];
    $recent_resources = [];
    $resources = [];
    $user_resources = [];
    $error = '';

    // Get admin statistics
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
        $stmt->execute();
        $total_users = $stmt->get_result()->fetch_assoc()['total_users'];
        
        $stmt = $conn->prepare("SELECT COUNT(*) as total_resources FROM resources");
        $stmt->execute();
        $total_resources = $stmt->get_result()->fetch_assoc()['total_resources'];
        
        $stmt = $conn->prepare("SELECT SUM(downloads) as total_downloads FROM resources");
        $stmt->execute();
        $total_downloads = $stmt->get_result()->fetch_assoc()['total_downloads'] ?? 0;
        
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
        $stmt->execute();
        $recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 5");
        $stmt->execute();
        $recent_resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $stmt = $conn->prepare("SELECT r.*, u.name, u.email FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC");
        $stmt->execute();
        $resources = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $user_resources = $recent_resources;
    } catch (Exception $e) {
        $error = "Error fetching data: " . $e->getMessage();
        $resources = [];
        $user_resources = [];
    }
    
    // Include dashboard view
    require __DIR__ . '/dashboard.php';
} elseif (strpos($route, 'schools') === 0) {
    // Handle schools routes
    if ($route === 'schools') {
        require __DIR__ . '/schools/index.php';
    } elseif ($route === 'schools-add') {
        require __DIR__ . '/schools/add.php';
    } elseif ($route === 'schools-view') {
        require __DIR__ . '/schools/view.php';
    } elseif ($route === 'schools-edit') {
        require __DIR__ . '/schools/edit.php';
    }
} elseif ($route === 'edit-resource') {
    // Handle edit resource route
    require __DIR__ . '/edit-resource.php';
} else {
    // Other routes
    $page_file = __DIR__ . "/{$route}.php";
    if (file_exists($page_file)) {
        require $page_file;
    } else {
        header('HTTP/1.0 404 Not Found');
        require __DIR__ . '/404.php';
        exit;
    }
}
?>