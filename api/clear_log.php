<?php
// Admin API - Clear Log File
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $csrf_token = $input['csrf_token'] ?? '';
    
    if (!validateCSRFLite($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    
    $logFile = $input['file'] ?? '';
    
    // Security: Validate log file path
    if (!preg_match('/^[a-zA-Z0-9_\/.-]+$/', $logFile)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid file name']);
        exit();
    }
    
    $logDir = __DIR__ . '/../logs';
    $logPath = $logDir . '/' . $logFile;
    
    if (!file_exists($logPath)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Log file not found']);
        exit();
    }
    
    // Clear the log file (empty it, don't delete it)
    $handle = fopen($logPath, 'r+');
    if ($handle === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to open log file']);
        exit();
    }
    
    // Truncate the file to 0 bytes
    if (ftruncate($handle, 0) === false) {
        fclose($handle);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to clear log file']);
        exit();
    }
    
    fclose($handle);
    
    // Verify file still exists after clearing
    if (!file_exists($logPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'File was deleted instead of cleared']);
        exit();
    }
    
    echo json_encode(['success' => true, 'message' => 'Log file cleared successfully']);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
