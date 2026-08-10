<?php
// Assignment Delete API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get session token from header
$headers = getallheaders();
$session_token = $headers['Authorization'] ?? '';

if (empty($session_token)) {
    echo json_encode(['success' => false, 'error' => 'No session token provided']);
    exit;
}

try {
    // Verify session token
    $stmt = $pdo->prepare("SELECT ts.*, t.*, s.school_name 
                          FROM teacher_sessions ts
                          JOIN teachers t ON ts.teacher_id = t.id
                          JOIN schools s ON t.school_id = s.id
                          WHERE ts.session_token = ? AND ts.expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();

    if (!$session) {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
        exit;
    }

    $teacher_id = $session['teacher_id'];
    $school_id = $session['school_id'];

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$assignment_id = $_POST['assignment_id'] ?? 0;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
    exit;
}

try {
    // Get assignment details
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
        exit;
    }
    
    // Check if teacher owns this assignment
    if ($assignment['teacher_id'] != $teacher_id) {
        echo json_encode(['success' => false, 'error' => 'You can only delete your own assignments']);
        exit;
    }
    
    // Delete the file
    $file_path = __DIR__ . '/../../uploads/assignments/' . $assignment['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete download records
    $stmt = $pdo->prepare("DELETE FROM assignment_downloads WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    
    // Delete assignment
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
} catch (PDOException $e) {
    error_log("Failed to delete assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to delete assignment. Please try again.']);
}
