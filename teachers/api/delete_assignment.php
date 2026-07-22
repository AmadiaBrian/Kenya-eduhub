<?php
// Assignment Delete API
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$school_id = $_SESSION['school_id'];

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
    
    // Check if teacher owns this assignment or is from same school
    if ($assignment['teacher_id'] != $teacher_id && $assignment['school_id'] != $school_id) {
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
