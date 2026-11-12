<?php
// Disable error reporting for API responses
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");

// Allow all origins for local development
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

include_once '../config.php';

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Test database connection
if (!$conn) {
    error_log("Login API - Database connection failed");
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed"
    ]);
    exit();
}

// Get posted data
$json_input = file_get_contents("php://input");
$data = json_decode($json_input);

// Check if data is empty (form submission might be URL encoded)
if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = (object) [
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? ''
    ];
}

if (!empty($data->email) && !empty($data->password)) {
    // Sanitize inputs
    $email = $conn->real_escape_string($data->email);
    $password = $data->password;

    // First check if user exists
    $sql = "SELECT id, name, email, phone, password, role, is_verified FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result === false) {
        echo json_encode([
            "success" => false,
            "message" => "Database query failed"
        ]);
        exit();
    }
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if account is verified
        if ($user['is_verified'] == 0) {
            echo json_encode([
                "success" => false,
                "message" => "Account not verified. Please check your email for verification instructions."
            ]);
        } else {
            // Account is verified, now verify password
            if (password_verify($password, $user['password'])) {
                // Login successful - set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                if (!isset($_SESSION['csrf_lite_token'])) {
                    $_SESSION['csrf_lite_token'] = bin2hex(random_bytes(16));
                    $_SESSION['csrf_lite_time'] = time();
                }
                $csrf_token = $_SESSION['csrf_lite_token'];
                
                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "session_id" => session_id(),
                    "csrf_token" => $csrf_token,
                    "user" => [
                        "id" => (int)$user['id'],
                        "name" => $user['name'] ?? '',
                        "email" => $user['email'] ?? '',
                        "phone" => $user['phone'] ?? '',
                        "role" => $user['role'] ?? 'user'
                    ]
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid password"
                ]);
            }
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Email not found"
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
}

$conn->close();
?>