<?php
// Security functions for Schools API
require_once __DIR__ . '/config.php';

// Rate limiting using files
function check_rate_limit($identifier, $max_requests = 10, $time_window = 300) {
    $rate_limit_dir = __DIR__ . '/../../logs/rate_limits';
    if (!is_dir($rate_limit_dir)) {
        mkdir($rate_limit_dir, 0755, true);
    }
    
    $file = $rate_limit_dir . '/' . md5($identifier) . '.txt';
    $current_time = time();
    $window_start = $current_time - $time_window;
    
    $requests = [];
    if (file_exists($file)) {
        $requests = json_decode(file_get_contents($file), true) ?: [];
    }
    
    // Remove old requests
    $requests = array_filter($requests, function($timestamp) use ($window_start) {
        return $timestamp > $window_start;
    });
    
    if (count($requests) >= $max_requests) {
        return false;
    }
    
    $requests[] = $current_time;
    file_put_contents($file, json_encode($requests));
    return true;
}

// CSRF Token generation
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    return $token;
}

// CSRF Token validation
function validate_csrf_token($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Input sanitization
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Generate school code
function generate_school_code() {
    return 'SCH' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

// Generate admission number
function generate_admission_number($school_id, $year) {
    global $pdo;
    
    // Get the school's admission prefix
    $stmt = $pdo->prepare("SELECT admission_prefix FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    
    $prefix = $school['admission_prefix'] ?? '';
    
    // Get the last admission number for this school
    $stmt = $pdo->prepare("SELECT admission_number FROM students WHERE school_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$school_id]);
    $last_student = $stmt->fetch();
    
    $next_number = 1;
    
    if ($last_student) {
        // Extract the sequential number from the last admission number
        // Format: prefix/sequence (e.g., TKNP/B/7280) or just sequence (e.g., 7280)
        $parts = explode('/', $last_student['admission_number']);
        $last_sequence = (int)end($parts);
        $next_number = $last_sequence + 1;
    }
    
    // If prefix is empty, just return the number
    if (empty($prefix)) {
        return (string)$next_number;
    }
    
    return $prefix . '/' . $next_number;
}

// Generate receipt number
function generate_receipt_number() {
    return 'RCP' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10));
}

// Log security events
function log_security_event($event_type, $details) {
    $log_dir = __DIR__ . '/../../logs/security';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/' . date('Y-m-d') . '.log';
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $event_type . ' - ' . $details . PHP_EOL;
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
?>
