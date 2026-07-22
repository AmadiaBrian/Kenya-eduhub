<?php
session_start();
require_once '../../config.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    header('HTTP/1.0 400 Bad Request');
    exit;
}

try {
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'];
    
    // Get assignment details
    $stmt = $pdo->prepare("SELECT file_path, file_name FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    $file_path = '../../uploads/assignments/' . $assignment['file_path'];
    
    if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        exit;
    }
    
    // Get file extension
    $file_ext = strtolower(pathinfo($assignment['file_name'], PATHINFO_EXTENSION));
    
    // Set appropriate content type
    $content_types = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'txt' => 'text/plain',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];
    
    $content_type = $content_types[$file_ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: inline; filename="' . $assignment['file_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    
    readfile($file_path);
    
} catch (PDOException $e) {
    error_log("Failed to get assignment file: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    exit;
}
