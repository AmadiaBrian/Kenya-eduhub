<?php
// Session and security are handled by index.php router
// No need to repeat session_start() and security checks here

require_once '../config.php';
require_once '../includes/helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$theme = $_POST['theme'] ?? 'light';

// Update user's theme preference
try {
    $stmt = $conn->prepare("UPDATE user_settings SET theme = ? WHERE user_id = ?");
    $stmt->bind_param("si", $theme, $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update theme']);
}
?>