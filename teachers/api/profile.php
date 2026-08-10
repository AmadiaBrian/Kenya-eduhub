<?php
// Teacher Profile API
// Session-based authentication
session_start();
header('Content-Type: application/json');

// Check if teacher is authenticated
if (!isset($_SESSION['teacher_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../../config.php';

$teacher_id = $_SESSION['teacher_id'];
$school_id = $_SESSION['school_id'] ?? null;

// Handle GET requests - fetch profile data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_profile') {
        try {
            // Get teacher details
            $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['error' => 'Teacher not found']);
                exit;
            }
            
            // Get subjects for the school
            $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
            $stmt->execute([$teacher['school_id']]);
            $subjects = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'teacher' => [
                    'id' => $teacher['id'],
                    'first_name' => $teacher['first_name'],
                    'last_name' => $teacher['last_name'],
                    'email' => $teacher['email'],
                    'phone' => $teacher['phone'],
                    'id_number' => $teacher['id_number'],
                    'address' => $teacher['address'] ?? '',
                    'subject' => $teacher['subject'] ?? '',
                    'teacher_type' => $teacher['teacher_type'],
                    'school_id' => $teacher['school_id'],
                    'school_name' => $teacher['school_name'],
                    'class_id' => $teacher['class_id'] ?? null,
                    'class_name' => $teacher['class_name'] ?? null,
                    'stream_id' => $teacher['stream_id'] ?? null,
                    'stream_name' => $teacher['stream_name'] ?? null,
                    'status' => $teacher['status'],
                    'created_at' => $teacher['created_at']
                ],
                'subjects' => $subjects
            ]);
        } catch (PDOException $e) {
            error_log("Failed to fetch teacher profile: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch profile']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }
}

// Handle POST requests - update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $id_number = trim($_POST['id_number'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        
        // Validation
        $errors = [];
        if (empty($first_name)) $errors[] = 'First name is required';
        if (empty($last_name)) $errors[] = 'Last name is required';
        if (empty($email)) $errors[] = 'Email is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
        if (empty($phone)) $errors[] = 'Phone is required';
        if (empty($id_number)) $errors[] = 'ID number is required';
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, address = ?, subject = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $address, $subject, $teacher_id]);
            
            // Update session
            $_SESSION['teacher_name'] = $first_name . ' ' . $last_name;
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
        } catch (PDOException $e) {
            error_log("Failed to update teacher profile: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile']);
            exit;
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        $errors = [];
        if (empty($current_password)) $errors[] = 'Current password is required';
        if (empty($new_password)) $errors[] = 'New password is required';
        if (strlen($new_password) < 8) $errors[] = 'New password must be at least 8 characters';
        if ($new_password !== $confirm_password) $errors[] = 'Passwords do not match';
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['error' => 'Validation failed', 'errors' => $errors]);
            exit;
        }
        
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM teachers WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch();
            
            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['error' => 'Teacher not found']);
                exit;
            }
            
            if (!password_verify($current_password, $teacher['password'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Current password is incorrect']);
                exit;
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $teacher_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (PDOException $e) {
            error_log("Failed to change password: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to change password']);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }
}
?>
