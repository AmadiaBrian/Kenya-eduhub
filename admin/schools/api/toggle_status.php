<?php
// Toggle School Status API
session_start();
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/security_lite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Check if user is admin
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || (!isset($user['role']) || $user['role'] !== 'admin')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Validate CSRF token using security_lite function
if (!isset($_GET['csrf_token']) || !validateCSRFLite($_GET['csrf_token'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$school_id = $_GET['id'] ?? '';
$status = $_GET['status'] ?? '';

if (empty($school_id) || !in_array($status, ['active', 'inactive'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE schools SET status = ? WHERE id = ?");
    $stmt->execute([$status, $school_id]);
    
    // Redirect back to schools page with success message
    header("Location: ../index.php?success=" . urlencode("School status updated successfully"));
    exit;
} catch (PDOException $e) {
    error_log("Failed to toggle school status: " . $e->getMessage());
    error_log("School ID: $school_id, Status: $status");
    
    // Redirect back to schools page with error message
    header("Location: ../index.php?error=" . urlencode("Failed to update school status"));
    exit;
}
