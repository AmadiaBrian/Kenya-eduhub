<?php
// Parent Profile API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
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

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get parent details
        $stmt = $pdo->prepare("SELECT p.*, s.school_name FROM parents p JOIN schools s ON p.school_id = s.id WHERE p.id = ?");
        $stmt->execute([$parent_id]);
        $parent = $stmt->fetch();
        
        if ($parent) {
            echo json_encode([
                'success' => true,
                'parent' => [
                    'id' => $parent['id'],
                    'first_name' => $parent['first_name'],
                    'last_name' => $parent['last_name'],
                    'email' => $parent['email'],
                    'phone' => $parent['phone'],
                    'address' => $parent['address'] ?? '',
                    'id_number' => $parent['id_number'] ?? '',
                    'school_name' => $parent['school_name'] ?? '',
                    'created_at' => $parent['created_at'] ?? ''
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Parent not found']);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle profile update
        $data = json_decode(file_get_contents('php://input'), true);
        
        $first_name = $data['first_name'] ?? '';
        $last_name = $data['last_name'] ?? '';
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        
        if (empty($first_name) || empty($last_name) || empty($phone)) {
            echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE parents SET first_name = ?, last_name = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->execute([$first_name, $last_name, $phone, $address, $parent_id]);
        
        // Update session
        $_SESSION['parent_name'] = $first_name . ' ' . $last_name;
        
        // Get updated parent data
        $stmt = $pdo->prepare("SELECT p.*, s.school_name FROM parents p JOIN schools s ON p.school_id = s.id WHERE p.id = ?");
        $stmt->execute([$parent_id]);
        $parent = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'parent' => [
                'id' => $parent['id'],
                'first_name' => $parent['first_name'],
                'last_name' => $parent['last_name'],
                'email' => $parent['email'],
                'phone' => $parent['phone'],
                'address' => $parent['address'] ?? '',
                'id_number' => $parent['id_number'] ?? '',
                'school_name' => $parent['school_name'] ?? '',
                'created_at' => $parent['created_at'] ?? ''
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    error_log("Parent profile API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
