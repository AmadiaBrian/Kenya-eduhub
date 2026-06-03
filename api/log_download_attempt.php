<?php
session_start();
require_once '../includes/security_lite.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit();
}

$resourceId = $data['resource_id'] ?? 'unknown';
$reason = $data['reason'] ?? 'unknown';
$status = $data['status'] ?? 'unknown';
$userResourceCount = $data['user_resource_count'] ?? 0;

// Log the download attempt
$description = "Download attempt for resource ID: $resourceId";
$details = [
    'resource_id' => $resourceId,
    'reason' => $reason,
    'status' => $status,
    'user_resource_count' => $userResourceCount,
    'page' => 'dashboard/index.php'
];

if ($reason === 'download_restricted') {
    logActivity('DOWNLOAD_RESTRICTED', $description, $details);
} elseif ($reason === 'download_attempt') {
    logActivity('DOWNLOAD_ATTEMPT', $description, $details);
}

echo json_encode(['success' => true]);
?>
