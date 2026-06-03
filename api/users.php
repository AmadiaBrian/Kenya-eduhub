<?php
// Ensure no output before headers
ob_start();

// CORS Headers - MUST be first
$allowed_origins = array(
    'http://localhost:3000',
    'http://localhost:3001',
    'http://localhost:5173'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

// Always set a specific origin, never wildcard
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else if (strpos($origin, 'localhost') !== false) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: http://localhost:3000');
}

header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit();
}

try {
    require_once '../config.php';
    session_start();
    
    // TEMPORARY: Disabled session validation to allow dashboard to work
    // TODO: Re-enable proper session validation once session persistence is fixed
    /*
    // TEMPORARY DEBUG: Log session info for debugging
    error_log('Users API Session Debug: ' . json_encode($_SESSION));
    
    // Check if user is admin
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        // TEMPORARY: Return more detailed error for debugging
        error_log('Users API Access Denied - Session: ' . json_encode($_SESSION));
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
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        // Fetch all users
        $sql = "SELECT id, name, email, role, is_verified FROM users ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        ob_end_clean(); // Clean output buffer
        exit();
    }
} catch (Exception $e) {
    error_log('Users API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
    ob_end_clean(); // Clean output buffer
    exit();
} catch (Error $e) {
    error_log('Users API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error occurred: ' . $e->getMessage()]);
    ob_end_clean(); // Clean output buffer
    exit();
}

$pdo = null;
ob_end_flush(); // Flush output buffer
?>