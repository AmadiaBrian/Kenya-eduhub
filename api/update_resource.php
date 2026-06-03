<?php
// Set CORS headers first, before any possible errors
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users

try {
    require_once '../config.php';
    session_start();

// Debug: Log request data
error_log('Update resource request: ' . print_r($_REQUEST, true));
error_log('Update resource files: ' . print_r($_FILES, true));
error_log('Update resource post: ' . print_r($_POST, true));
error_log('Session data: ' . print_r($_SESSION, true));



// Check if this is a form submission (with or without file)
error_log('Checking form submission');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && !empty($_POST['id'])) {
    error_log('Processing form submission');
    error_log('Received POST data: ' . print_r($_POST, true));
    error_log('Received FILES data: ' . print_r($_FILES, true));
    
    $resourceId = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $level = $_POST['level'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    error_log("Parsed data - ID: $resourceId, Title: $title, Level: $level, Subject: $subject, Type: $type, Description: $description");
    
    // Check if we have a file upload
    $hasFile = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;
    error_log('Has file upload: ' . ($hasFile ? 'Yes' : 'No'));
    
    // Validate required fields
    error_log("Validation check - Title: '" . $title . "', Level: '" . $level . "', Subject: '" . $subject . "', Type: '" . $type . "', Description: '" . $description . "'");
    if (empty($title) || empty($level) || empty($subject) || empty($type) || empty($description)) {
        $missing = [];
        if (empty($title)) $missing[] = 'title';
        if (empty($level)) $missing[] = 'level';
        if (empty($subject)) $missing[] = 'subject';
        if (empty($type)) $missing[] = 'type';
        if (empty($description)) $missing[] = 'description';
        
        error_log('Missing fields: ' . implode(', ', $missing));
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }
    
    // Initialize the SQL query and parameters
    $sql = "UPDATE resources SET title = ?, level = ?, subject = ?, type = ?, description = ?";
    $params = [$title, $level, $subject, $type, $description];
    $types = 'sssss';
    
    // Handle file upload if a new file was provided
    if ($hasFile) {
        $file = $_FILES['file'];
        $fileName = basename($file['name']);
        $fileTmpName = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid('', true) . '.' . $fileExt;
        $uploadPath = __DIR__ . '/uploads/' . $newFileName;
        
        // Move the uploaded file
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            $filePath = 'api/uploads/' . $newFileName;
            
            // Get the old filename to delete it later
            $oldFile = $pdo->prepare("SELECT filename FROM resources WHERE id = ?");
            $oldFile->bindParam(1, $resourceId, PDO::PARAM_INT);
            $oldFile->execute();
            $oldFilePath = $oldFile->fetchColumn();
            
            // Delete the old file if it exists
            $fullOldPath = $_SERVER['DOCUMENT_ROOT'] . '/kenyaeduhub/' . $oldFilePath;
            if ($oldFilePath && file_exists($fullOldPath)) {
                unlink($fullOldPath);
            }
            
            // Update with new file
            $sql .= ", filename = ?";
            $params[] = $filePath;
            $types .= 's';
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload file']);
            exit();
        }
    }
    
    // Complete the SQL query
    $sql .= " WHERE id = ?";
    $params[] = $resourceId;
    $types .= 'i';
    
    // Prepare and execute the statement
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
    }
    
    $stmt = null;
    $pdo = null;
    exit();
}

// Handle JSON request (for non-file updates)
// Only process JSON if it's not a form submission
if (!isset($_POST['id'])) {
    error_log('Checking JSON request');
    $rawInput = file_get_contents('php://input');
    error_log('Raw input: ' . $rawInput);
    $data = json_decode($rawInput, true);
    error_log('JSON data: ' . print_r($data, true));
    $resourceId = $data['id'] ?? null;
    $title = $data['title'] ?? null;
    $level = $data['level'] ?? null;
    $subject = $data['subject'] ?? null;
    $type = $data['type'] ?? null;
    $description = $data['description'] ?? null;
    
    error_log("Parsed JSON data - ID: $resourceId, Title: $title, Level: $level, Subject: $subject, Type: $type, Description: $description");

    // Validate required fields for JSON requests
    if (!$resourceId || !$title || !$level || !$subject || !$type || !$description) {
        $missing = [];
        if (!$resourceId) $missing[] = 'id';
        if (!$title) $missing[] = 'title';
        if (!$level) $missing[] = 'level';
        if (!$subject) $missing[] = 'subject';
        if (!$type) $missing[] = 'type';
        if (!$description) $missing[] = 'description';
        
        error_log('Missing fields in JSON request: ' . implode(', ', $missing));
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
        exit();
    }

    // Update the resource in the database without requiring a file
    $sql = "UPDATE resources SET title = ?, level = ?, subject = ?, type = ?, description = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $params = [$title, $level, $subject, $type, $description, $resourceId];

    if ($stmt->execute($params)) {
        echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update resource']);
    }

    $stmt = null;
    $pdo = null;
    exit();
} else {
    // This is a form submission, not a JSON request
    error_log('Skipping JSON handler as this is a form submission');
}
} catch (Exception $e) {
    error_log('Update Exception: ' . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred during update."
    ]);
} catch (Error $e) {
    error_log('Update Error: ' . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred during update."
    ]);
}

$stmt = null;
$pdo = null;
?>