<?php
// Parent Assignments API
// Disable error reporting for API responses
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

// Check authentication
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

try {
    // Get children of this parent
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get all class IDs for the children
    $class_ids = array_column($children, 'class_id');
    $class_ids = array_filter($class_ids);
    
    // Get assignments for the children's classes
    $assignments = [];
    if (!empty($class_ids)) {
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        $query = "SELECT a.*, c.class_name, s.subject_name, t.first_name, t.last_name
                  FROM assignments a
                  LEFT JOIN classes c ON a.class_id = c.id
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  WHERE a.school_id = ? AND (a.class_id IN ($placeholders) OR a.class_id IS NULL)
                  ORDER BY a.created_at DESC LIMIT 50";
        
        $params = array_merge([$school_id], $class_ids);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();
    }
    
    echo json_encode([
        'success' => true,
        'children' => array_map(function($child) {
            return [
                'id' => $child['id'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class' => $child['class_name'] ?? 'Not assigned',
                'stream' => $child['stream_name'] ?? 'Not assigned'
            ];
        }, $children),
        'assignments' => array_map(function($assignment) {
            // Determine badge type
            $badge_type = 'Syllabus';
            if ($assignment['assignment_type'] === 'sentiment') {
                $badge_type = 'Sentiment';
            } elseif ($assignment['assignment_type'] === 'notes') {
                $badge_type = 'Notes';
            } elseif ($assignment['assignment_type'] === 'holiday') {
                $badge_type = 'Holiday';
            }
            
            return [
                'id' => $assignment['id'],
                'title' => $assignment['title'],
                'description' => $assignment['description'],
                'subject' => $assignment['subject_name'] ?? 'Not specified',
                'class' => $assignment['class_name'] ?? 'All classes',
                'teacher' => $assignment['first_name'] . ' ' . $assignment['last_name'],
                'due_date' => $assignment['due_date'],
                'created_at' => $assignment['created_at'],
                'assignment_type' => $assignment['assignment_type'] ?? 'syllabus',
                'badge_type' => $badge_type,
                'file_name' => $assignment['file_name'] ?? null,
                'attachment' => $assignment['attachment'] ?? null
            ];
        }, $assignments)
    ]);
    
} catch (PDOException $e) {
    error_log("Assignments API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
