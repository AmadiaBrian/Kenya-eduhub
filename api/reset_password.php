<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';
$code = isset($data['code']) ? trim($data['code']) : '';
$newPassword = isset($data['newPassword']) ? $data['newPassword'] : '';

if (!$email || !$code || !$newPassword) {
    echo json_encode(['success' => false, 'message' => 'Email, code, and new password are required']);
    exit;
}

if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    // Check if code exists and is valid
    $stmt = $pdo->prepare("SELECT reset_expires_at FROM users WHERE email = ? AND reset_code = ?");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired reset code']);
        exit;
    }

    // Check if code has expired
    if (strtotime($user['reset_expires_at']) < time()) {
        echo json_encode(['success' => false, 'message' => 'Reset code has expired']);
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update the user's password and clear reset code
    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE email = ?");
    $stmt->execute([$hashedPassword, $email]);

    echo json_encode(['success' => true, 'message' => 'Password has been successfully reset']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>