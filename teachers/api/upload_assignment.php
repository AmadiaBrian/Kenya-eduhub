<?php
// Assignment Upload API
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
