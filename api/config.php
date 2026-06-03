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
?>