<?php
session_start();
require_once '../config.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to update your profile"
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                "success" => false,
                "message" => "Invalid email format"
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
        
        // Update user profile
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $update_stmt->bind_param("ssi", $name, $email, $user_id);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                "success" => true,
                "message" => "Profile updated successfully!",
                "user" => [
                    "id" => $user_id,
                    "name" => $name,
                    "email" => $email,
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
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
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
            "id" => $user['id'],
            "name" => $user['name'],
            "email" => $user['email'],
            "role" => $user['role'],
            "is_verified" => $user['is_verified'],
            "created_at" => $user['created_at']
        ]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
