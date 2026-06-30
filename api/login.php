<?php
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

// Start session
session_start();

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

// Debug: Log the received data
error_log("Login API - Received data: " . $json_input);
error_log("Login API - Decoded data: " . print_r($data, true));

// Check if data is empty (form submission might be URL encoded)
if (empty($data) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Try to get from POST data
    $data = (object) [
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? ''
    ];
    error_log("Login API - Using POST data instead of JSON");
}

if (!empty($data->email) && !empty($data->password)) {
    // Sanitize inputs
    $email = $conn->real_escape_string($data->email);
    $password = $data->password;
    
    error_log("Login API - Looking for email: " . $email);
    
    // First check if user exists (regardless of verification status)
    $sql = "SELECT id, name, email, password, role, is_verified FROM users WHERE email = '$email'";
    $result = $conn->query($sql);
    
    if ($result === false) {
        error_log("Login API - SQL query failed: " . $conn->error);
        echo json_encode([
            "success" => false,
            "message" => "Database query failed"
        ]);
        exit();
    }
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        error_log("Login API - User found: " . $user['email'] . ", verified: " . $user['is_verified']);
        
        // Check if account is verified
        if ($user['is_verified'] == 0) {
            // Account not verified
            error_log("Login API - Account not verified");
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
                
                // Generate CSRF token for mobile API (direct implementation)
                if (!isset($_SESSION['csrf_lite_token'])) {
                    $_SESSION['csrf_lite_token'] = bin2hex(random_bytes(16));
                    $_SESSION['csrf_lite_time'] = time();
                }
                $csrf_token = $_SESSION['csrf_lite_token'];
                
                error_log("Login API - Password verified, login successful, session set");
                echo json_encode([
                    "success" => true,
                    "message" => "Login successful",
                    "session_id" => session_id(),
                    "csrf_token" => $csrf_token,
                    "user" => [
                        "id" => $user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "role" => $user['role']
                    ]
                ]);
            } else {
                // Invalid password
                error_log("Login API - Invalid password");
                echo json_encode([
                    "success" => false,
                    "message" => "Invalid password"
                ]);
            }
        }
    } else {
        // User not found
        error_log("Login API - User not found");
        echo json_encode([
            "success" => false,
            "message" => "Email not found"
        ]);
    }
} else {
    // Missing required fields
    error_log("Login API - Missing required fields");
    echo json_encode([
        "success" => false,
        "message" => "Email and password are required"
    ]);
}

$conn->close();
?>