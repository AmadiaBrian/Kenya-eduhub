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
    
    if (!$assignment_id) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
        exit;
    }
    
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'];
    
    // Get original assignment
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    $original = $stmt->fetch();
    
    if (!$original) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    // Create duplicate
    $stmt = $pdo->prepare("INSERT INTO assignments (teacher_id, school_id, class_id, subject_id, title, description, assignment_type, file_path, file_name, due_date, created_at, updated_at) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $teacher_id,
        $school_id,
        $original['class_id'],
        $original['subject_id'],
        $original['title'] . ' (Copy)',
        $original['description'],
        $original['assignment_type'],
        $original['file_path'],
        $original['file_name'],
        $original['due_date']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment duplicated successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to duplicate assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
