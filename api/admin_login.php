<?php
// Handle CORS - Must be done before any output
$allowed_origins = array(
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5173'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $origin);
    header("Access-Control-Allow-Credentials: true");
}

header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: " . $origin);
        header("Access-Control-Allow-Credentials: true");
    }
    http_response_code(200);
    exit();
}

require_once '../config.php';
session_start();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->email) && !empty($data->password)) {
    // Sanitize inputs
    $email = $data->email;
    $password = $data->password;
    
    // First check if admin user exists (regardless of verification status)
    $sql = "SELECT id, name, email, password, is_verified FROM users WHERE email = ? AND role = 'admin'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $email, PDO::PARAM_STR);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if account is verified
        if ($user['is_verified'] == 0) {
            // Account not verified
            echo json_encode([
                "success" => false,
                "message" => "Admin account not verified. Please contact system administrator."
            ]);
        } else {
            // Account is verified, now verify password
            if (password_verify($password, $user['password'])) {
                // Login successful
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = 'admin';
                
                echo json_encode([
                    "success" => true,
                    "message" => "Admin login successful",
                    "admin" => [
                        "id" => $user['id'],
                        "name" => $user['name'],
                        "email" => $user['email']
                    ]
                ]);
            } else {
                // Invalid password
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid password"
                ]);
            }
        }
    } else {
        // Admin not found
        echo json_encode([
            "success" => false,
            "message" => "Admin account not found"
        ]);
    }
} else {
    // Missing required fields
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
}

$pdo = null;
?>