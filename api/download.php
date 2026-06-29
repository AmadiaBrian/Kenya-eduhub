<?php
// Handle both GET and POST requests
header("Content-Type: application/json");

// Include security first
require_once '../includes/security_lite.php';

// Security: Rate limiting for downloads
$download_identifier = $_SERVER['REMOTE_ADDR'] . '_' . ($_SESSION['user_id'] ?? 'anonymous');
if (!checkRateLimit($download_identifier, 20, 300)) { // 20 downloads per 5 minutes
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many download attempts. Please try again later.']);
    exit();
}

// Remove any existing CORS headers
header_remove("Access-Control-Allow-Origin");
header_remove("Access-Control-Allow-Methods");
header_remove("Access-Control-Allow-Headers");

// Allow multiple origins (more restrictive)
$allowed_origins = array(
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5173',
    'http://localhost/Kenyaeduhub'
);

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
    header("Access-Control-Allow-Credentials: true");
} else {
    // For development, allow localhost with more strict validation
    if (isset($_SERVER['HTTP_ORIGIN']) && 
        (strpos($_SERVER['HTTP_ORIGIN'], 'localhost') !== false || 
         strpos($_SERVER['HTTP_ORIGIN'], '127.0.0.1') !== false)) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Access-Control-Allow-Credentials: true");
    }
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-CSRF-Token");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

include_once '../config.php';

// Get resource ID from GET parameter first, then POST data
$resourceId = $_GET['id'] ?? null;

// If not in GET, try POST data
if (!$resourceId) {
    $data = json_decode(file_get_contents("php://input"), true);
    $resourceId = $data['resource_id'] ?? $data['id'] ?? null;
}

if (!$resourceId) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Resource ID is required"
    ]);
    exit();
}

// Validate resource ID
if (!is_numeric($resourceId)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid resource ID"
    ]);
    exit();
}

// Get resource info
$sql = "SELECT * FROM resources WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $resourceId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode([
        "success" => false,
        "message" => "Resource not found"
    ]);
    exit();
}

$resource = $result->fetch_assoc();

// Update download count
$updateSql = "UPDATE resources SET downloads = downloads + 1 WHERE id = ?";
$updateStmt = $conn->prepare($updateSql);
$updateStmt->bind_param("i", $resourceId);

if ($updateStmt->execute()) {
    // Check if this is a download request (not just count update)
    if (isset($_GET['download']) && $_GET['download'] === 'true') {
        $storedFile = $resource['filename'];
        $filePath = $storedFile;

        if (!file_exists($filePath)) {
            $normalizedFile = ltrim(str_replace('\\', '/', $storedFile), '/');

            if (strpos($normalizedFile, 'api/') === 0) {
                $filePath = dirname(__DIR__) . '/' . $normalizedFile;
            } else {
                $filePath = __DIR__ . '/' . $normalizedFile;
            }
        }
        
        if (file_exists($filePath)) {
            // Set headers for file download
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output file
            readfile($filePath);
            exit();
        } else {
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "File not found at: " . $filePath
            ]);
        }
    } else {
        // Just return success for count update
        echo json_encode([
            "success" => true,
            "message" => "Download count updated",
            "downloads" => $resource['downloads'] + 1
        ]);
    }
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Failed to update download count"
    ]);
}

$stmt->close();
$updateStmt->close();
$conn->close();
?>
