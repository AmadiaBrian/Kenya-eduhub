<?php
// Examiner Login API
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid input data']);
    exit;
}

// Validate required fields
if (empty($input['email']) || empty($input['password'])) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

// Find examiner
try {
    $stmt = $pdo->prepare("SELECT id, school_id, name, email, password, status FROM examination_department_heads WHERE email = ?");
    $stmt->execute([$email]);
    $examiner = $stmt->fetch();
    
    error_log("Login attempt - Email: $email, Found: " . ($examiner ? 'YES' : 'NO'));
    
    if (!$examiner) {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }
    
    // Check if account is active
    if ($examiner['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Your account is ' . $examiner['status'] . '. Please contact support.']);
        exit;
    }
    
    // Verify password
    if (!password_verify($password, $examiner['password'])) {
        error_log("Password verification failed for email: $email");
        error_log("Password length: " . strlen($password));
        error_log("Hash length: " . strlen($examiner['password']));
        echo json_encode(['success' => false, 'error' => 'Invalid credentials']);
        exit;
    }
    
    // Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate session token
    $session_token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store session in database
    $stmt = $pdo->prepare("INSERT INTO examiner_sessions (examiner_id, session_token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$examiner['id'], $session_token, $expires_at]);
    
    // Set session variables
    $_SESSION['examiner_id'] = $examiner['id'];
    $_SESSION['examiner_token'] = $session_token;
    $_SESSION['examiner_name'] = $examiner['name'];
    $_SESSION['examiner_school_id'] = $examiner['school_id'];
    
    echo json_encode([
        'success' => true,
        'examiner_id' => $examiner['id'],
        'name' => $examiner['name'],
        'email' => $examiner['email'],
        'school_id' => $examiner['school_id']
    ]);
    
} catch (PDOException $e) {
    error_log("Examiner login failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Login failed. Please try again.']);
}
?>
