<?php
/**
 * Kenya EduHub - Lightweight Security (No Page Impact)
 * Essential protection without breaking existing functionality
 */

// Prevent direct access
if (!defined('SECURITY_LITE_INCLUDED')) {
    define('SECURITY_LITE_INCLUDED', true);
}

/* =========================
   🔧 ESSENTIAL SECURITY ONLY
========================= */

// Basic input sanitization (non-destructive)
function sanitizeInputLite($data) {
    if (is_array($data)) {
        return array_map('sanitizeInputLite', $data);
    }
    return trim($data); // Just trim, no HTML encoding for forms
}

// XSS protection for output (safe for forms)
function sanitizeOutput($data) {
    if (is_array($data)) {
        return array_map('sanitizeOutput', $data);
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Activity logging function (text file based)
function logActivity($actionType, $description, $additionalData = []) {
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/activity.log';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessionId = session_id() ?? 'Unknown';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
        $pageAccessed = $_SERVER['PHP_SELF'] ?? 'Unknown';
        $timestamp = date('Y-m-d H:i:s');
        
        // Format additional data
        $dataStr = '';
        if (!empty($additionalData)) {
            $dataStr = ' | Data: ' . json_encode($additionalData, JSON_UNESCAPED_UNICODE);
        }
        
        // Create log entry
        $logEntry = sprintf(
            "[%s] %s | User: %s (%s) | IP: %s | Action: %s | %s | Page: %s | Method: %s | Session: %s | Agent: %s%s\n",
            $timestamp,
            'SUCCESS',
            $userId,
            $userEmail,
            $ipAddress,
            $actionType,
            $description,
            $pageAccessed,
            $requestMethod,
            $sessionId,
            substr($userAgent, 0, 100), // Truncate long user agents
            $dataStr
        );
        
        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write to daily log file for better organization
        $dailyLogFile = $logDir . '/activity_' . date('Y-m-d') . '.log';
        file_put_contents($dailyLogFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        return true;
        
    } catch (Exception $e) {
        // Fail silently to not break user experience
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

// Log failed activity (for errors, failed attempts, etc.)
function logFailedActivity($actionType, $description, $errorMessage = '', $additionalData = []) {
    $logDir = __DIR__ . '/../logs';
    $logFile = $logDir . '/activity.log';
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $userEmail = $_SESSION['user_email'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $sessionId = session_id() ?? 'Unknown';
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'Unknown';
        $pageAccessed = $_SERVER['PHP_SELF'] ?? 'Unknown';
        $timestamp = date('Y-m-d H:i:s');
        
        // Format additional data
        $dataStr = '';
        if (!empty($additionalData)) {
            $dataStr = ' | Data: ' . json_encode($additionalData, JSON_UNESCAPED_UNICODE);
        }
        
        // Create log entry
        $logEntry = sprintf(
            "[%s] %s | User: %s (%s) | IP: %s | Action: %s | %s | Page: %s | Method: %s | Session: %s | Agent: %s | Error: %s%s\n",
            $timestamp,
            'FAILED',
            $userId,
            $userEmail,
            $ipAddress,
            $actionType,
            $description,
            $pageAccessed,
            $requestMethod,
            $sessionId,
            substr($userAgent, 0, 100), // Truncate long user agents
            $errorMessage,
            $dataStr
        );
        
        // Write to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write to daily log file for better organization
        $dailyLogFile = $logDir . '/activity_' . date('Y-m-d') . '.log';
        file_put_contents($dailyLogFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Also write to error log file for failed activities
        $errorLogFile = $logDir . '/errors.log';
        file_put_contents($errorLogFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        return true;
        
    } catch (Exception $e) {
        // Fail silently to not break user experience
        error_log("Failed activity logging failed: " . $e->getMessage());
        return false;
    }
}

// XSS protection for specific inputs (email, names)
function sanitizeStrict($data) {
    if (is_array($data)) {
        return array_map('sanitizeStrict', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Email validation
function validateEmailLite($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// CSRF token generation (minimal, no session conflicts)
function generateCSRFLite() {
    if (!isset($_SESSION['csrf_lite_token'])) {
        $_SESSION['csrf_lite_token'] = bin2hex(random_bytes(16));
        $_SESSION['csrf_lite_time'] = time();
    }
    return $_SESSION['csrf_lite_token'];
}

// CSRF token validation (minimal)
function validateCSRFLite($token) {
    if (empty($_SESSION['csrf_lite_token'])) {
        return false;
    }
    
    // Token expires after 1 hour
    if (time() - $_SESSION['csrf_lite_time'] > 3600) {
        unset($_SESSION['csrf_lite_token'], $_SESSION['csrf_lite_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_lite_token'], $token);
}

// Simple rate limiting (memory-based, no files)
function checkRateLimit($identifier, $max = 10, $window = 300) {
    static $attempts = [];
    
    $now = time();
    $key = md5($identifier);
    
    // Clean old attempts
    if (isset($attempts[$key])) {
        $attempts[$key] = array_filter($attempts[$key], function($time) use ($now, $window) {
            return ($now - $time) < $window;
        });
    }
    
    // Check limit
    if (isset($attempts[$key]) && count($attempts[$key]) >= $max) {
        return false;
    }
    
    // Add current attempt
    $attempts[$key][] = $now;
    return true;
}

// Password strength validation
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) $errors[] = "Be at least 8 characters long";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Contain at least one uppercase letter";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Contain at least one lowercase letter";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Contain at least one number";
    if (!preg_match('/[\W]/', $password)) $errors[] = "Contain at least one special character (!@#$%^&*)";
    
    return $errors;
}

// Secure database helper (optional, doesn't break existing code)
function secureQueryOptional($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);
    if ($stmt && $params) {
        if (!$types) {
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
        }
        $stmt->bind_param($types, ...$params);
    }
    return $stmt;
}

// File upload security (basic)
function secureFileUpload($file) {
    $allowed_ext = ['pdf','doc','docx','txt','ppt','pptx','xls','xlsx'];
    $max_size = 50 * 1024 * 1024; // 50MB
    
    if (!is_uploaded_file($file['tmp_name'])) {
        return ["Invalid upload"];
    }
    
    if ($file['size'] > $max_size) {
        return ["File too large"];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        return ["Invalid file type"];
    }
    
    return []; // No errors
}

// Session security (minimal)
function secureSessionLite() {
    // Only set if session is not active yet
    if (session_status() === PHP_SESSION_NONE) {
        if (!ini_get('session.cookie_httponly')) {
            ini_set('session.cookie_httponly', 1);
        }
        
        if (!empty($_SERVER['HTTPS']) && !ini_get('session.cookie_secure')) {
            ini_set('session.cookie_secure', 1);
        }
    }
}

// Initialize minimal security (no headers, no session start)
secureSessionLite();

// Clickjacking protection (minimal, won't break frames)
function setClickjackingProtection() {
    // Only set headers if not already sent and not in iframe
    if (!headers_sent() && !isset($_GET['iframe'])) {
        header('X-Frame-Options: SAMEORIGIN'); // Allow same-origin frames only
        header('X-Content-Type-Options: nosniff');
    }
}

// XSS protection headers (minimal)
function setXSSProtection() {
    if (!headers_sent()) {
        header('X-XSS-Protection: 1; mode=block');
    }
}

// Initialize protections
setClickjackingProtection();
setXSSProtection();

// Simple logging (optional)
function logSecurityEvent($type, $details = []) {
    // Only log if logs directory exists
    $log_file = __DIR__ . '/../logs/security.log';
    if (is_dir(dirname($log_file))) {
        $log = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'event' => $type,
            'details' => $details
        ]) . PHP_EOL;
        
        file_put_contents($log_file, $log, FILE_APPEND | LOCK_EX);
    }
}
?>
