<?php
// CORS Headers - MUST be first
$allowed_origins = array(
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5173'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Always set a specific origin, never wildcard
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
} else if (strpos($origin, 'localhost') !== false) {
    header("Access-Control-Allow-Origin: " . $origin);
} else {
    header("Access-Control-Allow-Origin: http://localhost:3000");
}

header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

include_once '../config.php';

// Fetch all resources
$sql = "SELECT * FROM resources ORDER BY created_at DESC";
$result = $conn->query($sql);

$resources = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $resources[] = $row;
    }
}

echo json_encode([
    "success" => true,
    "resources" => $resources
]);

$conn->close();
?>