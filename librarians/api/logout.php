<?php
// Librarian Logout API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Delete session from database if session token exists
    if (isset($_SESSION['librarian_session_token'])) {
        $stmt = $pdo->prepare("DELETE FROM librarian_sessions WHERE session_token = ?");
        $stmt->execute([$_SESSION['librarian_session_token']]);
    }
    
    // Destroy session
    session_destroy();
    
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
} catch (PDOException $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Logout failed']);
}
?>
