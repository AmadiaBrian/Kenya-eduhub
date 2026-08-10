<?php
// Finance Manager Logout
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

if (!isset($_SESSION['finance_manager_id'])) {
    header('Location: ../index.php');
    exit;
}

// Clear session token from database
try {
    $session_token = $_SESSION['finance_manager_session_token'] ?? '';
    if ($session_token) {
        $stmt = $pdo->prepare("DELETE FROM finance_manager_sessions WHERE session_token = ?");
        $stmt->execute([$session_token]);
    }
} catch (PDOException $e) {
    error_log("Failed to clear finance manager session: " . $e->getMessage());
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: ../index.php');
exit;
