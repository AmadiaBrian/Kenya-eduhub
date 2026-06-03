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

// Get the resource ID from the request
$rawInput = file_get_contents('php://input');
error_log('Delete resource raw input: ' . $rawInput);
$data = json_decode($rawInput, true);
error_log('Delete resource data: ' . print_r($data, true));
$resourceId = $data['id'] ?? null;

if (!$resourceId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Resource ID is required']);
    exit();
}

// First, get the file path to delete the actual file
$sql = "SELECT filename FROM resources WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $resourceId, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->rowCount() === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Resource not found']);
    exit();
}

$filename = $stmt->fetchColumn();
$filePath = $_SERVER['DOCUMENT_ROOT'] . '/kenyaeduhub/' . $filename;

// Delete the database record
$sql = "DELETE FROM resources WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $resourceId, PDO::PARAM_INT);

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
} catch (Exception $e) {
    error_log('Delete Exception: ' . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred during deletion."
    ]);
} catch (Error $e) {
    error_log('Delete Error: ' . $e->getMessage());
    echo json_encode([
        "success" => false,
        "message" => "Server error occurred during deletion."
    ]);
}

$stmt = null;
$pdo = null;
?>
