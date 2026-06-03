<?php
header("Content-Type: application/json");

include_once '../config.php';

// Fetch all resources
$sql = "SELECT id, title, filename FROM resources ORDER BY created_at DESC";
$result = $conn->query($sql);

$resources = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $resources[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'filename' => $row['filename'],
            'file_exists' => file_exists('../uploads/' . $row['filename'])
        ];
    }
}

echo json_encode([
    'success' => true,
    'resources' => $resources
]);

$conn->close();
?>
