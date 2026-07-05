<?php
// School Logout API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Invalid request method', 405);
}

require_school_auth();

try {
    // Delete session from database
    $stmt = $pdo->prepare("DELETE FROM school_sessions WHERE school_id = ? AND session_token = ?");
    $stmt->execute([$_SESSION['school_id'], $_SESSION['school_token']]);
    
    // Destroy session
    session_destroy();
    
    log_security_event('SCHOOL_LOGOUT', "School logged out: {$_SESSION['school_name']}");
    
    success_response([], 'Logout successful');
    
} catch (PDOException $e) {
    error_log("Logout failed: " . $e->getMessage());
    error_response('Logout failed. Please try again.', 500);
}
?>
