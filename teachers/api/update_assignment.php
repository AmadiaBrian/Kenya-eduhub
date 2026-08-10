<?php
// Assignment Update API
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

try {
    $assignment_id = $_POST['assignment_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $assignment_type = $_POST['assignment_type'] ?? '';
    
    if (empty($assignment_id) || empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID and title are required']);
        exit;
    }
    
    // Verify teacher owns this assignment
    $stmt = $pdo->prepare("SELECT id, file_path FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    // Handle file upload if provided
    if (!empty($_FILES['file']['name'])) {
        $file = $_FILES['file'];
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']);
            exit;
        }
        
        // Create uploads directory if not exists
        $upload_dir = __DIR__ . '/../../uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Delete old file
        if ($assignment['file_path'] && file_exists($upload_dir . $assignment['file_path'])) {
            unlink($upload_dir . $assignment['file_path']);
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file_ext;
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            exit;
        }
        
        // Update with new file
        $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, assignment_type = ?, file_path = ?, file_name = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $assignment_type, $filename, $file['name'], $assignment_id]);
    } else {
        // Update without file
        $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, assignment_type = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $assignment_type, $assignment_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to update assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
