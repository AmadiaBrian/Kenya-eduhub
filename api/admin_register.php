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
    header("Access-Control-Allow-Credentials: true");
}

header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

include_once '../config.php';
session_start();

// Get posted data
$data = json_decode(file_get_contents("php://input"));

if (!empty($data->name) && !empty($data->email) && !empty($data->password)) {
    // Sanitize inputs
    $name = $conn->real_escape_string($data->name);
    $email = $conn->real_escape_string($data->email);
    $password = $data->password;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "message" => "Invalid email format"
        ]);
        exit();
    }
    
    // Check if email already exists
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = $conn->query($check_email);
    
    if ($result->num_rows > 0) {
        echo json_encode([
            "success" => false,
            "message" => "Email is already registered"
        ]);
        exit();
    }
    
    // Check admin limit (max 2 admins)
    $result = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $row = $result->fetch_assoc();
    if ($row['total'] >= 2) {
        echo json_encode([
            "success" => false,
            "message" => "Maximum number of admin accounts reached"
        ]);
        exit();
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert admin into database
    $sql = "INSERT INTO users (name, email, password, role, is_verified) VALUES ('$name', '$email', '$hashed_password', 'admin', 1)";
    
    if ($conn->query($sql) === TRUE) {
        echo json_encode([
            "success" => true,
            "message" => "Admin account created successfully"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Error creating admin account: " . $conn->error
        ]);
    }
} else {
    // Missing required fields
    echo json_encode([
        "success" => false,
        "message" => "Name, email, and password are required"
    ]);
}

$conn->close();
?>