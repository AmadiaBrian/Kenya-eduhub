<?php
// Update School PIN API
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Validate CSRF token
if (!validateCSRFLite($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit();
}

$school_id = intval($_POST['school_id'] ?? 0);
$current_pin = trim($_POST['current_pin'] ?? '');
$new_pin = trim($_POST['new_pin'] ?? '');
$confirm_pin = trim($_POST['confirm_pin'] ?? '');

if ($school_id <= 0) {
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
    
    // Get current PIN
    $stmt = $conn->prepare("SELECT withdrawal_pin FROM schools WHERE id = ?");
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $school = $result->fetch_assoc();
    
    if (!$school) {
        echo json_encode(['success' => false, 'error' => 'School not found']);
        exit();
    }
    
    $errors = [];
    
    // Validate PIN
    if (empty($new_pin)) {
        $errors[] = 'PIN is required';
    } elseif (strlen($new_pin) < 4) {
        $errors[] = 'PIN must be at least 4 digits';
    } elseif (!ctype_digit($new_pin)) {
        $errors[] = 'PIN must contain only numbers';
    } elseif ($new_pin !== $confirm_pin) {
        $errors[] = 'PIN confirmation does not match';
    }
    
    // If PIN already exists, verify current PIN
    if (!empty($school['withdrawal_pin'])) {
        if (empty($current_pin)) {
            $errors[] = 'Current PIN is required';
        } elseif (!password_verify($current_pin, $school['withdrawal_pin'])) {
            $errors[] = 'Current PIN is incorrect';
        }
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit();
    }
    
    // Update PIN
    $hashed_pin = password_hash($new_pin, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE schools SET withdrawal_pin = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed_pin, $school_id);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'PIN updated successfully']);
} catch (Exception $e) {
    error_log("Error updating PIN: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
