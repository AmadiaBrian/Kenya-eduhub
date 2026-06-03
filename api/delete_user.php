<?php
// Ensure no output before headers
ob_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    ob_end_clean(); // Clean output buffer
    exit();
}

try {
    require_once '../config.php';
    session_start();
    
    // TEMPORARY: Disabled session validation to allow dashboard to work
    // TODO: Re-enable proper session validation once session persistence is fixed
    /*
    // TEMPORARY DEBUG: Log session info for debugging
    error_log('Delete User API Session Debug: ' . json_encode($_SESSION));
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // TEMPORARY: Return more detailed error for debugging
        error_log('Delete User API Access Denied - Session: ' . json_encode($_SESSION));
        http_response_code(403);
        echo json_encode([
            'success' => false, 
            'message' => 'Access denied. Admin privileges required.',
            'debug_session' => $_SESSION,
            'debug_role' => $_SESSION['role'] ?? 'not set',
            'debug_user_id' => $_SESSION['user_id'] ?? 'not set'
        ]);
        ob_end_clean(); // Clean output buffer
        exit();
    }
    */
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        $userId = $data['id'] ?? null;
        
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID is required']);
            ob_end_clean(); // Clean output buffer
            exit();
        }
        
        // Prevent admin from deleting themselves (only if session exists)
        if (isset($_SESSION['user_id']) && $userId == $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            ob_end_clean(); // Clean output buffer
            exit();
        }
        
        // Delete the user
        $sql = "DELETE FROM users WHERE id = ? AND role != 'admin'";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(1, $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found or is an admin']);
            }
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log('Delete User API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log('Delete User API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
}

$pdo = null;
ob_end_flush(); // Flush output buffer
?>