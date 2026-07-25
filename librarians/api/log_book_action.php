<?php
// API endpoint to log book actions for audit trail
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config.php';
}

// Authentication check
if (!isset($_SESSION['librarian_id']) || !isset($_SESSION['librarian_token'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$librarian_id = $_SESSION['librarian_id'];
$school_id = $_SESSION['school_id'];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$book_id = $input['book_id'] ?? 0;
$action = $input['action'] ?? '';
$details = $input['details'] ?? '';

// Validate input
if (!$book_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Book ID and action are required']);
    exit;
}

// Valid actions
$valid_actions = ['added', 'edited', 'deleted', 'borrowed', 'returned', 'reserved', 'lost', 'damaged'];
if (!in_array($action, $valid_actions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

try {
    // Log the action
    $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, ?, ?, 'librarian', ?)");
    $stmt->execute([$book_id, $school_id, $action, $librarian_id, $details]);
    
    echo json_encode(['success' => true, 'message' => 'Action logged successfully']);
} catch (PDOException $e) {
    error_log("Failed to log book action: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to log action']);
}
