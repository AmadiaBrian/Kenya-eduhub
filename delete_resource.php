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

// Get the resource ID from the request
$data = json_decode(file_get_contents('php://input'), true);
$resourceId = $data['id'] ?? null;

if (!$resourceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
    exit();
}

// First, get the file path to delete the actual file
$sql = "SELECT filename FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resourceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
    exit();
}

$row = $result->fetch_assoc();
$filename = $row['filename'];
$filePath = __DIR__ . '/uploads/' . $filename;

// Delete the database record
$sql = "DELETE FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resourceId);

if ($stmt->execute()) {
    // Delete the file if it exists
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Resource deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete resource']);
}

$stmt->close();
$conn->close();
?>
