<?php
// Simple test endpoint to check if data is being received
header('Content-Type: application/json');

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

echo json_encode([
    'success' => true,
    'message' => 'Data received successfully',
    'received_data' => $data,
    'raw_input' => $json_data,
    'json_error' => json_last_error_msg()
]);
?>
