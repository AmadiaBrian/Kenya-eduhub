<?php
session_start();
require_once '../../config.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $assignment_id = $input['assignment_id'] ?? null;
    $title = $input['title'] ?? null;
    $description = $input['description'] ?? '';
    $due_date = $input['due_date'] ?? null;
    
    if (!$assignment_id || !$title) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID and title are required']);
        exit;
    }
    
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'];
    
    // Verify teacher owns this assignment
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    // Update assignment
    $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$title, $description, $due_date, $assignment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to update assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
