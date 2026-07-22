<?php
session_start();
require_once '../../config.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
    exit;
}

try {
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
    
    // Check if assignment_comments table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'assignment_comments'");
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist, return empty comments
        echo json_encode(['success' => true, 'comments' => []]);
        exit;
    }
    
    // Get teacher name
    $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) as name FROM teachers WHERE id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
    $teacher_name = $teacher ? $teacher['name'] : 'Teacher';
    
    // Get comments
    $stmt = $pdo->prepare("SELECT ac.*, 
                          CASE ac.author_type
                              WHEN 'teacher' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM teachers WHERE id = ac.author_id)
                              ELSE ac.author_name
                          END as author_name
                          FROM assignment_comments ac
                          WHERE ac.assignment_id = ?
                          ORDER BY ac.created_at DESC");
    $stmt->execute([$assignment_id]);
    $comments = $stmt->fetchAll();
    
    $comments_formatted = [];
    foreach ($comments as $comment) {
        $comments_formatted[] = [
            'id' => $comment['id'],
            'comment' => $comment['comment'],
            'author_name' => $comment['author_name'] ?: 'Unknown',
            'created_at' => date('M d, H:i', strtotime($comment['created_at']))
        ];
    }
    
    echo json_encode(['success' => true, 'comments' => $comments_formatted]);
    
} catch (PDOException $e) {
    error_log("Failed to get assignment comments: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
