<?php
// Get School PIN Status API
session_start();
error_log("PIN Status API called - Session: " . json_encode($_SESSION));

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

error_log("Config loaded successfully");

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    error_log("Unauthorized: No user_id in session");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

error_log("User ID found: " . $_SESSION['user_id']);

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

error_log("User data: " . json_encode($user));

if (!isset($user['role']) || $user['role'] !== 'admin') {
    error_log("Unauthorized: User is not admin. Role: " . ($user['role'] ?? 'none'));
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

error_log("User is admin, proceeding");

// Validate CSRF token
if (!validateCSRFLite($_GET['csrf_token'] ?? '')) {
    error_log("Invalid CSRF token");
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$school_id = intval($_GET['id'] ?? 0);
error_log("School ID: " . $school_id);

if ($school_id <= 0) {
    error_log("Invalid school ID");
    echo json_encode(['success' => false, 'error' => 'Invalid school ID']);
    exit();
}

try {
    // Check if withdrawal_pin column exists
    $column_check = $conn->query("SHOW COLUMNS FROM schools LIKE 'withdrawal_pin'");
    if ($column_check->num_rows == 0) {
        // Add column if it doesn't exist
        $conn->query("ALTER TABLE schools ADD COLUMN withdrawal_pin VARCHAR(255) DEFAULT NULL AFTER password");
    }
    
    // Get PIN status
    $stmt = $conn->prepare("SELECT withdrawal_pin FROM schools WHERE id = ?");
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $school = $result->fetch_assoc();
    
    if (!$school) {
        echo json_encode(['success' => false, 'error' => 'School not found']);
        exit();
    }
    
    $has_pin = !empty($school['withdrawal_pin']);
    
    echo json_encode(['success' => true, 'has_pin' => $has_pin]);
} catch (Exception $e) {
    error_log("Error getting PIN status: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
