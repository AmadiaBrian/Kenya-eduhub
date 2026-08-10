<?php
// Assignment Upload API
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
    $assignment_type = $_POST['assignment_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $target_class_id = $_POST['class_id'] ?? '';
    $target_subject_id = $_POST['subject_id'] ?? '';
    $due_date = $_POST['due_date'] ?? '';
    
    // Debug logging
    error_log("Upload attempt - Title: $title, File: " . ($_FILES['file']['name'] ?? 'none'));
    
    if (empty($assignment_type) || empty($title) || empty($_FILES['file']['name'])) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
        exit;
    }
    
    $file = $_FILES['file'];
    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    error_log("File details - Original name: {$file['name']}, Size: {$file['size']}");
    
    if (!in_array($file_ext, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed types: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, JPEG, PNG']);
        exit;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
        echo json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']);
        exit;
    }
    
    // Create uploads directory if not exists
    $upload_dir = __DIR__ . '/../../uploads/assignments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $stmt = $pdo->prepare("
            INSERT INTO assignments (teacher_id, school_id, class_id, subject_id, title, description, 
                                   assignment_type, file_path, file_name, due_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $teacher_id,
            $school_id,
            $target_class_id ?: null,
            $target_subject_id ?: null,
            $title,
            $description,
            $assignment_type,
            $filename,
            $file['name'],
            $due_date ?: null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Assignment uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file. Please try again.']);
    }
} catch (PDOException $e) {
    error_log("Failed to save assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to save assignment. Please try again.']);
}
