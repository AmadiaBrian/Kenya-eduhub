<?php
header('Content-Type: application/json');

// Include security first
require_once '../includes/security_lite.php';

// Enable error reporting for debugging (only in development)
if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

try {
    require_once '../config.php';
    session_start();
    
    // Security: Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
    
    // Security: Rate limiting for uploads
    $upload_identifier = $_SERVER['REMOTE_ADDR'] . '_' . $_SESSION['user_id'];
    if (!checkRateLimit($upload_identifier, 5, 300)) { // 5 uploads per 5 minutes
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Too many upload attempts. Please try again later.']);
        exit();
    }
    
    // Security: CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFLite($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit();
    }
    
    // Log incoming request for debugging
    error_log('Upload request from user ' . $_SESSION['user_id'] . ': ' . print_r($_POST, true));
    error_log('Files received: ' . print_r($_FILES, true));
    
    // Log upload attempt
    logActivity('UPLOAD_ATTEMPT', 'User attempted to upload a resource', [
        'page' => 'api/upload.php',
        'title' => sanitizeStrict($_POST['title'] ?? ''),
        'level' => sanitizeStrict($_POST['level'] ?? ''),
        'subject' => sanitizeStrict($_POST['subject'] ?? ''),
        'type' => sanitizeStrict($_POST['type'] ?? '')
    ]);
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Security: Sanitize and validate inputs
        $title = sanitizeStrict($_POST['title'] ?? '');
        $level = sanitizeStrict($_POST['level'] ?? '');
        $subject = sanitizeStrict($_POST['subject'] ?? '');
        $type = sanitizeStrict($_POST['type'] ?? '');
        $description = sanitizeStrict($_POST['description'] ?? '');
        
        // Security: Validate required fields
        if (empty($title) || empty($level) || empty($subject) || empty($type) || empty($description)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            exit();
        }
        
        // Security: Validate file upload
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No valid file uploaded']);
            exit();
        }
        
        // Security: Validate file
        $file_errors = secureFileUpload($_FILES['file']);
        if (!empty($file_errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => implode(', ', $file_errors)]);
            exit();
        }
        
        // Handle file upload
        if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
            $target_dir = __DIR__ . "/uploads/";
            // Create uploads directory if it doesn't exist
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Failed to create uploads directory. Please check permissions."
                    ]);
                    exit();
                }
            }
            
            $file_name = basename($_FILES["file"]["name"]);
            $target_file = $target_dir . uniqid() . '_' . $file_name;
            
            // Check file size (15MB limit)
            if ($_FILES["file"]["size"] < 15000000) {
                // Check if directory is writable
                if (!is_writable($target_dir)) {
                    echo json_encode([
                        "success" => false,
                        "message" => "Upload directory is not writable. Please check permissions."
                    ]);
                    exit();
                }
                
                if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
                    // Calculate file hash for duplicate detection
                    $file_hash = md5_file($target_file);
                    
                    // Check if a file with the same content already exists
                    $check_sql = "SELECT id FROM resources WHERE file_hash = ?";
                    $check_stmt = $pdo->prepare($check_sql);
                    $check_stmt->execute([$file_hash]);
                    
                    if ($check_stmt->rowCount() > 0) {
                        // File with same content already exists, delete the uploaded file
                        unlink($target_file);
                        echo json_encode([
                            "success" => false,
                            "message" => "This file already exists in the system."
                        ]);
                        exit();
                    }
                    
                    // Save to database using prepared statement
                    // Store relative path from api directory
                    $filename = "api/uploads/" . basename($target_file);
                    $sql = "INSERT INTO resources (title, level, subject, type, description, filename, file_hash, user_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $pdo->prepare($sql);
                    
                    try {
                        $user_id = $_SESSION['user_id'] ?? null;
                        $stmt->execute([$title, $level, $subject, $type, $description, $filename, $file_hash, $user_id]);
                        
                        if ($stmt->rowCount() > 0) {
                            // Log successful upload
                            logActivity('UPLOAD_SUCCESS', 'Resource uploaded successfully', [
                                'page' => 'api/upload.php',
                                'title' => $title,
                                'level' => $level,
                                'subject' => $subject,
                                'type' => $type,
                                'filename' => $filename,
                                'file_hash' => $file_hash
                            ]);
                            
                            echo json_encode([
                                "success" => true,
                                "message" => "Resource uploaded successfully",
                                "file" => $filename
                            ]);
                        } else {
                            echo json_encode([
                                "success" => false,
                                "message" => "Database error"
                            ]);
                        }
                    } catch (Exception $e) {
                        // Delete the uploaded file if database insert fails
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }
                        echo json_encode([
                            "success" => false,
                            "message" => "Database error: " . $e->getMessage()
                        ]);
                    }
                } else {
                    echo json_encode([
                        "success" => false,
                        "message" => "Sorry, there was an error uploading your file. Error code: " . $_FILES["file"]["error"]
                    ]);
                }
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Sorry, your file is too large. Maximum 15MB allowed."
                ]);
            }
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No file uploaded or upload error."
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid request method: " . $_SERVER['REQUEST_METHOD'],
            "debug_info" => [
                "request_method" => $_SERVER['REQUEST_METHOD'],
                "post_data" => $_POST,
                "files_data" => $_FILES,
                "all_server_vars" => $_SERVER
            ]
        ]);
    }
} catch (Exception $e) {
    error_log('Upload Exception: ' . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred during upload.",
        "debug_info" => [
            "exception" => $e->getMessage(),
            "request_method" => $_SERVER['REQUEST_METHOD'],
            "post_data" => $_POST,
            "files_data" => $_FILES,
            "all_server_vars" => $_SERVER
        ]
    ]);
}

$pdo = null;
?>
