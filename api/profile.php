<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';
require_once '../includes/helpers.php';

// Parse request data from JSON body or POST form data
$inputJSON = file_get_contents('php://input');
$json = json_decode($inputJSON, true);

$user_id = (int)($json['user_id'] ?? $_POST['user_id'] ?? $_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to update your profile"
    ]);
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User account not found"
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $json['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = trim($json['name'] ?? $_POST['name'] ?? '');
        $email = trim($json['email'] ?? $_POST['email'] ?? '');
        $phone = trim($json['phone'] ?? $_POST['phone'] ?? '');

        if (empty($name) || empty($email)) {
            echo json_encode([
                "success" => false,
                "message" => "Name and email are required"
            ]);
            exit();
        }
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid email format"
            ]);
            exit();
        }

        // Validate phone if provided
        if (!empty($phone) && !preg_match('/^(07|01)[0-9]{8}$/', $phone) && !preg_match('/^\+254[71][0-9]{8}$/', $phone)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid phone format. Use Kenya format: 07XXXXXXXX, 01XXXXXXXX, or +2547XXXXXXXX"
            ]);
            exit();
        }
        
        // Check if email is already taken by another user
        $check_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            echo json_encode([
                "success" => false,
                "message" => "Email is already taken by another user"
            ]);
            exit();
        }

        // Check if phone is already taken by another user
        if (!empty($phone)) {
            $check_phone = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $check_phone->bind_param("si", $phone, $user_id);
            $check_phone->execute();
            if ($check_phone->get_result()->num_rows > 0) {
                echo json_encode([
                    "success" => false,
                    "message" => "Phone number is already registered to another account"
                ]);
                exit();
            }
        }
        
        // Update user profile in database
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?");
        $update_stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Profile updated successfully!",
                "user" => [
                    "id" => $user_id,
                    "name" => $name,
                    "email" => $email,
                    "phone" => $phone,
                    "role" => $user['role']
                ]
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to update profile"
            ]);
        }
    } elseif ($action === 'change_password') {
        $current_password = $json['current_password'] ?? $_POST['current_password'] ?? '';
        $new_password = $json['new_password'] ?? $_POST['new_password'] ?? '';
        $confirm_password = $json['confirm_password'] ?? $_POST['confirm_password'] ?? '';
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            echo json_encode([
                "success" => false,
                "message" => "Current password is incorrect"
            ]);
            exit();
        }
        
        if (strlen($new_password) < 6) {
            echo json_encode([
                "success" => false,
                "message" => "New password must be at least 6 characters"
            ]);
            exit();
        }
        
        if ($new_password !== $confirm_password) {
            echo json_encode([
                "success" => false,
                "message" => "Passwords do not match"
            ]);
            exit();
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $password_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $password_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($password_stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Password changed successfully!"
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Failed to change password"
            ]);
        }
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Invalid action"
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Return current user profile
    echo json_encode([
        "success" => true,
        "user" => [
            "id" => (int)$user['id'],
            "name" => $user['name'] ?? '',
            "email" => $user['email'] ?? '',
            "phone" => $user['phone'] ?? '',
            "role" => $user['role'] ?? 'user',
            "created_at" => $user['created_at'] ?? ''
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
