<?php
// School Login API
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Invalid request method', 405);
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
if (!check_rate_limit('school_login_' . $ip, 10, 300)) {
    error_response('Too many login attempts. Please try again later.', 429);
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    error_response('Invalid input data');
}

// Validate required fields
if (empty($input['email']) || empty($input['password'])) {
    error_response('Email and password are required');
}

$email = sanitize_input($input['email']);
$password = $input['password'];

// Find school
try {
    $stmt = $pdo->prepare("SELECT id, school_name, email, password, status, school_code FROM schools WHERE email = ?");
    $stmt->execute([$email]);
    $school = $stmt->fetch();
    
    if (!$school) {
        log_security_event('SCHOOL_LOGIN_FAILED', "Invalid email: $email");
        error_response('Invalid credentials');
    }
    
    // Check if account is active
    if ($school['status'] !== 'active') {
        log_security_event('SCHOOL_LOGIN_BLOCKED', "Inactive account: $email (Status: {$school['status']})");
        error_response('Your account is ' . $school['status'] . '. Please contact support.');
    }
    
    // Verify password
    if (!password_verify($password, $school['password'])) {
        log_security_event('SCHOOL_LOGIN_FAILED', "Invalid password for: $email");
        error_response('Invalid credentials');
    }
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate session token
    $session_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store session in database
    $stmt = $pdo->prepare("INSERT INTO school_sessions (school_id, session_token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$school['id'], $session_token, $expires_at]);
    
    // Set session variables
    $_SESSION['school_id'] = $school['id'];
    $_SESSION['school_token'] = $session_token;
    $_SESSION['school_name'] = $school['school_name'];
    $_SESSION['school_code'] = $school['school_code'];
    
    log_security_event('SCHOOL_LOGIN_SUCCESS', "School logged in: {$school['school_name']} (ID: {$school['id']})");
    
    success_response([
        'school_id' => $school['id'],
        'school_name' => $school['school_name'],
        'school_code' => $school['school_code'],
        'email' => $school['email']
    ], 'Login successful');
    
} catch (PDOException $e) {
    error_log("Login failed: " . $e->getMessage());
    error_response('Login failed. Please try again.', 500);
}
?>
