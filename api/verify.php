<?php
header("Content-Type: application/json");

// Allow multiple origins
$allowed_origins = array(
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5173'
);

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

require_once '../config.php';
// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->code)) {
    // Sanitize inputs
    $email = $data->email;
    $code = $data->code;
    
    // Check if user exists with the provided email and code
    // First get the user with matching email and code
    $sql = "SELECT id, code_expires_at FROM users WHERE email = ? AND verification_code = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->bindParam(2, $code, PDO::PARAM_STR);
    $stmt->execute();
    
    // Check if user exists and code hasn't expired
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && new DateTime($user['code_expires_at']) > new DateTime()) {
        // Update user as verified
        $update_sql = "UPDATE users SET is_verified = 1, verification_code = NULL, code_expires_at = NULL WHERE email = ?";
        $update_stmt = $pdo->prepare($update_sql);
        $update_stmt->bindParam(1, $email, PDO::PARAM_STR);
        
        if ($update_stmt->execute()) {
            // Verification successful
            echo json_encode([
                "success" => true,
                "message" => "Email verified successfully. You can now login."
            ]);
        } else {
            // Update failed
            echo json_encode([
                "success" => false,
                "message" => "Verification failed. Please try again."
            ]);
        }
    } else {
        // Invalid code or expired
        echo json_encode([
            "success" => false,
            "message" => "Invalid or expired verification code."
        ]);
    }
} else {
    // Missing required fields
    echo json_encode([
        "success" => false,
        "message" => "Email and verification code are required"
    ]);
}

$pdo = null;
?>