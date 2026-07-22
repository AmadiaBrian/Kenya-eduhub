<?php
// Assignment Download API - Records download and serves file
session_start();
require_once __DIR__ . '/config.php';

// Check authentication
$user_type = null;
$user_id = null;
$user_name = '';

// Debug logging - check all session variables
error_log("Session data: " . json_encode($_SESSION));

if (isset($_SESSION['parent_id'])) {
    $user_type = 'parent';
    $user_id = $_SESSION['parent_id'];
    $user_name = $_SESSION['parent_name'] ?? 'Parent';
} elseif (isset($_SESSION['teacher_id'])) {
    $user_type = 'teacher';
    $user_id = $_SESSION['teacher_id'];
    $user_name = $_SESSION['teacher_name'] ?? 'Teacher';
} elseif (isset($_SESSION['student_id'])) {
    $user_type = 'student';
    $user_id = $_SESSION['student_id'];
    $user_name = $_SESSION['student_name'] ?? 'Student';
} else {
    header('HTTP/1.0 401 Unauthorized');
    echo 'Unauthorized access';
    exit;
}

$assignment_id = $_GET['assignment_id'] ?? 0;

if (!$assignment_id) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Assignment ID required';
    exit;
}

// Debug logging
error_log("Download request - Assignment ID: $assignment_id, User Type: $user_type, User ID: $user_id, User Name: $user_name");

try {
    // Get assignment details
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();
    
    if (!$assignment) {
        header('HTTP/1.0 404 Not Found');
        echo 'Assignment not found';
        exit;
    }
    
    // Check if user has access to this assignment
    $has_access = false;
    if ($user_type === 'teacher') {
        // Teachers can download from their own school
        $has_access = ($assignment['school_id'] === $_SESSION['school_id']);
    } elseif ($user_type === 'parent') {
        // Parents can download assignments for their children's classes
        $stmt = $pdo->prepare("SELECT s.class_id FROM students s
                              JOIN student_parents sp ON s.id = sp.student_id
                              WHERE sp.parent_id = ? AND s.status = 'active'");
        $stmt->execute([$user_id]);
        $children = $stmt->fetchAll();
        $class_ids = array_column($children, 'class_id');
        
        // Can download if assignment is for their child's class or general (class_id IS NULL)
        $has_access = ($assignment['school_id'] === $_SESSION['school_id'] && 
                      (in_array($assignment['class_id'], $class_ids) || $assignment['class_id'] === null));
    } elseif ($user_type === 'student') {
        // Students can download assignments for their class
        $stmt = $pdo->prepare("SELECT class_id FROM students WHERE id = ?");
        $stmt->execute([$user_id]);
        $student = $stmt->fetch();
        
        if ($student) {
            $has_access = ($assignment['school_id'] === $_SESSION['school_id'] && 
                          ($assignment['class_id'] === $student['class_id'] || $assignment['class_id'] === null));
        }
    }
    
    if (!$has_access) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access denied';
        exit;
    }
    
    // Record download
    $stmt = $pdo->prepare("INSERT INTO assignment_downloads (assignment_id, user_type, user_id, user_name, download_date)
                          VALUES (?, ?, ?, ?, NOW())");
    $stmt->execute([$assignment_id, $user_type, $user_id, $user_name]);
    
    // Serve file
    $file_path = __DIR__ . '/../uploads/assignments/' . $assignment['file_path'];
    
    if (!file_exists($file_path)) {
        header('HTTP/1.0 404 Not Found');
        echo 'File not found';
        exit;
    }
    
    // Get file info
    $file_info = pathinfo($file_path);
    $file_ext = strtolower($file_info['extension']);
    
    // Set appropriate content type
    $content_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png'
    ];
    
    $content_type = $content_types[$file_ext] ?? 'application/octet-stream';
    
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $assignment['file_name'] . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    header('HTTP/1.0 500 Internal Server Error');
    echo 'Error processing download';
    exit;
}
