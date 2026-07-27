<?php
// Admin API - Clear All Log Files
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit();
}

// Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $csrf_token = $input['csrf_token'] ?? '';
    
    if (!validateCSRFLite($csrf_token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }
    
    $logDir = __DIR__ . '/../logs';
    
    // Function to recursively get all log files
    function getAllLogFiles($dir) {
        $files = [];
        if (!is_dir($dir)) return $files;
        
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            
            if (is_dir($path)) {
                // Recursively get files from subdirectories
                $subFiles = getAllLogFiles($path);
                $files = array_merge($files, $subFiles);
            } elseif (preg_match('/\.log$/', $item)) {
                $files[] = $path;
            }
        }
        
        return $files;
    }
    
    // Get all log files
    $logFiles = getAllLogFiles($logDir);
    
    if (empty($logFiles)) {
        echo json_encode(['success' => true, 'message' => 'No log files found to clear']);
        exit();
    }
    
    $clearedCount = 0;
    $errors = [];
    
    // Clear each log file
    foreach ($logFiles as $logPath) {
        $handle = fopen($logPath, 'r+');
        if ($handle === false) {
            $errors[] = basename($logPath) . ' - Failed to open';
            continue;
        }
        
        if (ftruncate($handle, 0) === false) {
            fclose($handle);
            $errors[] = basename($logPath) . ' - Failed to clear';
            continue;
        }
        
        fclose($handle);
        $clearedCount++;
    }
    
    if ($clearedCount === count($logFiles)) {
        echo json_encode(['success' => true, 'message' => "Successfully cleared {$clearedCount} log file(s)"]);
    } else {
        echo json_encode([
            'success' => true, 
            'message' => "Cleared {$clearedCount} of " . count($logFiles) . " log file(s)",
            'errors' => $errors
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
