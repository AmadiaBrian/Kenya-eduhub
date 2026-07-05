<?php
// School Registration API
require_once __DIR__ . '/security.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Invalid request method', 405);
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
if (!check_rate_limit('school_register_' . $ip, 3, 900)) {
    error_response('Too many registration attempts. Please try again later.', 429);
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    error_response('Invalid input data');
}

// Validate required fields
$required_fields = ['school_name', 'email', 'password', 'phone', 'address', 'county', 'school_type'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        error_response("Field '$field' is required");
    }
}

// Sanitize inputs
$school_name = sanitize_input($input['school_name']);
$email = sanitize_input($input['email']);
$password = $input['password'];
$phone = sanitize_input($input['phone']);
$address = sanitize_input($input['address']);
$county = sanitize_input($input['county']);
$school_type = sanitize_input($input['school_type']);
$admission_prefix = !empty($input['admission_prefix']) ? sanitize_input($input['admission_prefix']) : null;

// Validate email
if (!validate_email($email)) {
    error_response('Invalid email format');
}

// Validate password strength
if (strlen($password) < 8) {
    error_response('Password must be at least 8 characters long');
}

// Validate school type
$valid_types = ['Primary', 'Secondary', 'College', 'University'];
if (!in_array($school_type, $valid_types)) {
    error_response('Invalid school type');
}

// Check if email already exists
try {
    $stmt = $pdo->prepare("SELECT id FROM schools WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        error_response('Email already registered');
    }
} catch (PDOException $e) {
    error_log("Registration check failed: " . $e->getMessage());
    error_response('Database error', 500);
}

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Generate school code
$school_code = generate_school_code();

// Insert school
try {
    $stmt = $pdo->prepare("INSERT INTO schools (school_code, school_name, email, password, phone, address, county, school_type, admission_prefix, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$school_code, $school_name, $email, $password_hash, $phone, $address, $county, $school_type, $admission_prefix]);
    
    $school_id = $pdo->lastInsertId();
    
    log_security_event('SCHOOL_REGISTRATION', "New school registered: $school_name (ID: $school_id)");
    
    success_response([
        'school_id' => $school_id,
        'school_code' => $school_code,
        'school_name' => $school_name
    ], 'Registration successful. Your account is pending approval.');
    
} catch (PDOException $e) {
    error_log("Registration failed: " . $e->getMessage());
    error_response('Registration failed. Please try again.', 500);
}
?>
