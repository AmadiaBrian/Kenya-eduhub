<?php
// Verify Withdrawal PIN API
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Check if user is logged in as school
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];
$pin = trim($_POST['pin'] ?? '');

if (empty($pin)) {
    echo json_encode(['success' => false, 'error' => 'PIN is required']);
    exit();
}

try {
    // Get school's withdrawal PIN
    $stmt = $pdo->prepare("SELECT withdrawal_pin FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    
    if (!$school) {
        echo json_encode(['success' => false, 'error' => 'School not found']);
        exit();
    }
    
    if (empty($school['withdrawal_pin'])) {
        echo json_encode(['success' => false, 'error' => 'No PIN set. Please set your withdrawal PIN in Settings.']);
        exit();
    }
    
    // Verify PIN
    if (password_verify($pin, $school['withdrawal_pin'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
    }
} catch (Exception $e) {
    error_log("PIN verification error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Verification failed']);
}
