<?php
header('Content-Type: application/json');
require_once 'config.php';
session_start();

// Check if user is admin
if (!isset($_SESSION['admin_email'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if this is a form submission with file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES) && isset($_POST['id'])) {
    $resourceId = $_POST['id'];
    $title = $_POST['title'] ?? '';
    $level = $_POST['level'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $type = $_POST['type'] ?? '';
    $description = $_POST['description'] ?? '';
    
    // Validate required fields
    if (empty($title) || empty($level) || empty($subject) || empty($type) || empty($description)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        exit();
    }
    
    // Initialize the SQL query and parameters
    $sql = "UPDATE resources SET title = ?, level = ?, subject = ?, type = ?, description = ?";
    $params = [$title, $level, $subject, $type, $description];
    $types = 'sssss';
    
    // Handle file upload if a new file was provided
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file'];
        $fileName = basename($file['name']);
        $fileTmpName = $file['tmp_name'];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $newFileName = uniqid('', true) . '.' . $fileExt;
        $uploadPath = __DIR__ . '/uploads/' . $newFileName;
        
        // Move the uploaded file
        if (move_uploaded_file($fileTmpName, $uploadPath)) {
            $filePath = 'uploads/' . $newFileName;
            
            // Get the old filename to delete it later
            $oldFile = $conn->prepare("SELECT filename FROM resources WHERE id = ?");
            $oldFile->bind_param("i", $resourceId);
            $oldFile->execute();
            $oldFileResult = $oldFile->get_result();
            $oldFilePath = $oldFileResult->fetch_assoc()['filename'];
            
            // Delete the old file if it exists
            if ($oldFilePath && file_exists(__DIR__ . '/' . $oldFilePath)) {
                unlink(__DIR__ . '/' . $oldFilePath);
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
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update resource: ' . $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
    exit();
}

// Handle JSON request (for non-file updates)
$data = json_decode(file_get_contents('php://input'), true);
$resourceId = $data['id'] ?? null;
$title = $data['title'] ?? null;
$level = $data['level'] ?? null;
$subject = $data['subject'] ?? null;
$type = $data['type'] ?? null;
$description = $data['description'] ?? null;

// Validate required fields
if (!$resourceId || !$title || !$level || !$subject || !$type || !$description) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Update the resource in the database without requiring a file
$sql = "UPDATE resources SET title = ?, level = ?, subject = ?, type = ?, description = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $title, $level, $subject, $type, $description, $resourceId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resource updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update resource: ' . $conn->error]);
}

$stmt->close();
$conn->close();
?>