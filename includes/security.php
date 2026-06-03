<?php
/**
 * Kenya EduHub - Advanced Security System
 * Production-ready security layer
 */

if (!defined('SECURITY_INCLUDED')) {
    define('SECURITY_INCLUDED', true);
}

/* =========================
   🔐 SECURITY HEADERS
========================= */
function setSecurityHeaders() {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');

    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");

    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

/* =========================
   🧹 INPUT SANITIZATION
========================= */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/* =========================
   📧 EMAIL VALIDATION
========================= */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/* =========================
   🔑 CSRF PROTECTION
========================= */
function generateCSRFToken() {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['csrf_token_time'] = time();
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token'])) return false;

    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/* =========================
   🚦 RATE LIMITING
========================= */
function checkRateLimit($identifier, $max = 5, $window = 300) {
    $dir = __DIR__ . '/../logs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $file = $dir . 'rate_' . hash('sha256', $identifier) . '.json';

    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $now = time();

    $data = array_filter($data, fn($t) => ($now - $t) < $window);

    if (count($data) >= $max) return false;

    $data[] = $now;
    file_put_contents($file, json_encode($data), LOCK_EX);

    return true;
}

/* =========================
   🚫 IP BLOCKING
========================= */
function isBlockedIP() {
    $file = __DIR__ . '/../config/blocked_ips.json';
    if (!file_exists($file)) return false;

    $ips = json_decode(file_get_contents($file), true);
    return in_array($_SERVER['REMOTE_ADDR'], $ips);
}

/* =========================
   📝 LOGGING
========================= */
function logSecurityEvent($type, $details = []) {
    $dir = __DIR__ . '/../logs/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $file = $dir . 'security.log';

    $log = json_encode([
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $type,
        'details' => $details
    ]) . PHP_EOL;

    file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
}

/* =========================
   🔒 PASSWORD VALIDATION
========================= */
function validatePasswordStrength($password) {
    $errors = [];

    if (strlen($password) < 8) $errors[] = "Min 8 characters";
    if (!preg_match('/[A-Z]/', $password)) $errors[] = "Add uppercase";
    if (!preg_match('/[a-z]/', $password)) $errors[] = "Add lowercase";
    if (!preg_match('/[0-9]/', $password)) $errors[] = "Add number";
    if (!preg_match('/[\W]/', $password)) $errors[] = "Add symbol";

    return $errors;
}

/* =========================
   🛢️ SECURE DATABASE QUERY
========================= */
function secureQuery($conn, $query, $params = [], $types = '') {
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        logSecurityEvent("SQL_ERROR", ["error" => $conn->error]);
        throw new Exception("Database error");
    }

    if ($params) {
        if (!$types) {
            foreach ($params as $p) {
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
            }
        }
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        logSecurityEvent("SQL_EXEC_ERROR", ["error" => $stmt->error]);
        throw new Exception("Query failed");
    }

    return $stmt;
}

/* =========================
   📁 SECURE FILE UPLOAD
========================= */
function secureFileUpload($file) {
    $allowed_ext = ['pdf','doc','docx','txt'];
    $max_size = 10 * 1024 * 1024; // 10MB

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

    $mime = mime_content_type($file['tmp_name']);

    if (!str_starts_with($mime, 'application') && $mime !== 'text/plain') {
        return ["Invalid MIME type"];
    }

    return [];
}

/* =========================
   🔐 SESSION SECURITY
========================= */
function secureSession() {
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', !empty($_SERVER['HTTPS']));
    ini_set('session.cookie_samesite', 'Strict');

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } elseif (time() - $_SESSION['created'] > 300) {
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

/* =========================
   ⚡ GLOBAL INIT
========================= */

// Hide errors from users
error_reporting(0);
ini_set('display_errors', 0);

// Initialize security
setSecurityHeaders();
secureSession();

// Block bad IPs
if (isBlockedIP()) {
    logSecurityEvent("BLOCKED_IP");
    die("Access denied");
}
?>