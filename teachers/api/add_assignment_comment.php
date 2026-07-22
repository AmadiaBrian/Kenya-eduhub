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
    $input = json_decode(file_get_contents('php://input'), true);
    $assignment_id = $input['assignment_id'] ?? null;
    $comment = $input['comment'] ?? null;
    
    if (!$assignment_id || !$comment) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID and comment are required']);
        exit;
    }
    
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'] ?? null;
    
    // Verify teacher owns this assignment
    if ($school_id) {
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
        $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assignment_id, $teacher_id]);
    }
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    // Check if assignment_comments table exists, create if not
    $stmt = $pdo->query("SHOW TABLES LIKE 'assignment_comments'");
    if ($stmt->rowCount() == 0) {
        $pdo->query("CREATE TABLE IF NOT EXISTS assignment_comments (
            id int(11) NOT NULL AUTO_INCREMENT,
            assignment_id int(11) NOT NULL,
            author_id int(11) NOT NULL,
            author_type enum('teacher','parent','student') DEFAULT 'teacher',
            author_name varchar(255) DEFAULT NULL,
            comment text NOT NULL,
            created_at timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (id),
            KEY idx_assignment_id (assignment_id),
            KEY idx_author_id (author_id)
        ) ENGINE=InnoDB");
    }
    
    // Get teacher name
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    $teacher_name = $teacher ? $teacher['name'] : 'Teacher';
    
    // Add comment
    $stmt = $pdo->prepare("INSERT INTO assignment_comments (assignment_id, author_id, author_type, author_name, comment) VALUES (?, ?, 'teacher', ?, ?)");
    $stmt->execute([$assignment_id, $teacher_id, $teacher_name, $comment]);
    
    echo json_encode(['success' => true, 'message' => 'Comment added successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to add assignment comment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
