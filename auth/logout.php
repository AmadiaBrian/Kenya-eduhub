<?php
session_start();

// Include database connection and helpers
require_once '../config.php';
require_once '../includes/helpers.php';

// Handle remember me cookie cleanup
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    // Delete remember token from database
    $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ? AND user_id = ?");
    $stmt->bind_param("si", $token, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
    
    // Delete remember cookie
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
}

// Destroy all session data
$_SESSION = array();

// Destroy the session
session_destroy();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to homepage
header("Location: ../");
exit();
?>
