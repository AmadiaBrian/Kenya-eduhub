<?php
// School Authentication Functions
require_once __DIR__ . '/security.php';

// Check if school is logged in
function is_school_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
        return false;
    }
    
    // Verify session in database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, expires_at FROM school_sessions WHERE school_id = ? AND session_token = ? AND expires_at > NOW()");
        $stmt->execute([$_SESSION['school_id'], $_SESSION['school_token']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            session_destroy();
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Auth check failed: " . $e->getMessage());
        return false;
    }
}

// Require school authentication
function require_school_auth() {
    if (!is_school_logged_in()) {
        error_response('Unauthorized. Please login.', 401);
    }
}

// Get current school ID
function get_current_school_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['school_id'] ?? null;
}

// Check if parent is logged in
function is_parent_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['parent_id']) || !isset($_SESSION['parent_token'])) {
        return false;
    }
    
    // Verify session in database
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, expires_at FROM parent_sessions WHERE parent_id = ? AND session_token = ? AND expires_at > NOW()");
        $stmt->execute([$_SESSION['parent_id'], $_SESSION['parent_token']]);
        $session = $stmt->fetch();
        
        if (!$session) {
            session_destroy();
            return false;
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Parent auth check failed: " . $e->getMessage());
        return false;
    }
}

// Require parent authentication
function require_parent_auth() {
    if (!is_parent_logged_in()) {
        error_response('Unauthorized. Please login.', 401);
    }
}

// Get current parent ID
function get_current_parent_id() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['parent_id'] ?? null;
}
?>
