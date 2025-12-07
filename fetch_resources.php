<?php
header('Content-Type: application/json');
require_once 'config.php';


// Check connection
if ($conn->connect_error) {
    // Log the error for debugging, but don't expose sensitive info to the client
    error_log("Connection failed: " . $conn->connect_error);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// Initialize data array
$data = [];

$sql = "SELECT id, title, level, subject, type, description, filename, 
               DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s') as created_at 
        FROM resources 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Ensure all fields are properly encoded
        $row = array_map('htmlspecialchars', $row);
        $data[] = $row;
    }
    $result->free();
} else {
    error_log("Error fetching resources: " . $conn->error);
    echo json_encode(['error' => 'Error fetching resources.']);
    exit();
}

$conn->close();

// Set proper JSON content type and encode the data
header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
