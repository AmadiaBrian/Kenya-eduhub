<?php
// Teacher Logout
session_start();
require_once __DIR__ . '/../../config.php';

if (isset($_SESSION['teacher_id'])) {
    // Delete session from database
    try {
        $session_token = $_SESSION['teacher_session_token'] ?? '';
        if ($session_token) {
            $stmt = $pdo->prepare("DELETE FROM teacher_sessions WHERE session_token = ?");
            $stmt->execute([$session_token]);
        }
    } catch (PDOException $e) {
        error_log("Failed to delete teacher session: " . $e->getMessage());
    }
}

// Destroy session
session_unset();
session_destroy();

header('Location: index.php');
exit;
?>
