<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'users_db');

// Create MySQLi connection (for existing code)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check MySQLi connection
if ($conn->connect_error) {
    error_log("MySQLi connection failed: " . $conn->connect_error);
    die("Database connection failed. Please check your configuration.");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Create PDO connection (for existing code)
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("PDO connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}

// Cleanup function for pending withdrawals older than 2 minutes
function cleanupPendingWithdrawals(PDO $pdo): int {
    try {
        $stmt = $pdo->prepare("DELETE FROM school_withdrawals 
                              WHERE status = 'pending' 
                              AND created_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
        $stmt->execute();
        $deleted_count = $stmt->rowCount();
        
        if ($deleted_count > 0) {
            error_log("Cleanup: Deleted $deleted_count pending withdrawals older than 2 minutes at " . date('Y-m-d H:i:s'));
        }
        
        return $deleted_count;
    } catch(PDOException $e) {
        error_log("Cleanup failed: " . $e->getMessage());
        return 0;
    }
}

// Run cleanup randomly (10% chance) to avoid running on every page load
if (rand(1, 10) === 1) {
    cleanupPendingWithdrawals($pdo);
}
?>