<?php
// Parent Login API
// Disable error reporting for API responses
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$identifier = $data['identifier'] ?? ''; // Can be phone or ID number

if (empty($email) || empty($identifier)) {
    echo json_encode(['success' => false, 'error' => 'Please enter both email and phone number/ID number']);
    exit;
}

try {
    // First check if email exists
    $stmt = $pdo->prepare("SELECT id, email, phone, id_number FROM parents WHERE email = ?");
    $stmt->execute([$email]);
    $email_check = $stmt->fetch();

    if (!$email_check) {
        echo json_encode(['success' => false, 'error' => 'We could not find an account associated with this email address. Please contact your school administration for assistance.']);
        exit;
    }

    // Email exists, now check if the identifier matches
    $stmt = $pdo->prepare("SELECT p.id as parent_id, p.first_name, p.last_name, p.email, p.phone, p.id_number, p.school_id, s.school_name
                           FROM parents p
                           JOIN schools s ON p.school_id = s.id
                           WHERE p.email = ? AND (p.phone = ? OR p.id_number = ?)");
    $stmt->execute([$email, $identifier, $identifier]);
    $parent_login = $stmt->fetch();

    if ($parent_login) {
        // Generate session token
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+8 hours'));

        // Store session in database
        $stmt = $pdo->prepare("INSERT INTO parent_sessions (parent_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$parent_login['parent_id'], $session_token, $expires_at]);

        // Set session variables
        $_SESSION['parent_id'] = $parent_login['parent_id'];
        $_SESSION['parent_name'] = $parent_login['first_name'] . ' ' . $parent_login['last_name'];
        $_SESSION['parent_email'] = $parent_login['email'];
        $_SESSION['parent_phone'] = $parent_login['phone'];
        $_SESSION['school_id'] = $parent_login['school_id'];
        $_SESSION['school_name'] = $parent_login['school_name'];
        $_SESSION['parent_session_token'] = $session_token;

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'session_id' => session_id(),
            'session_token' => $session_token,
            'parent' => [
                'id' => $parent_login['parent_id'],
                'name' => $parent_login['first_name'] . ' ' . $parent_login['last_name'],
                'email' => $parent_login['email'],
                'phone' => $parent_login['phone'],
                'school_id' => $parent_login['school_id'],
                'school_name' => $parent_login['school_name']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'The phone number or ID number you entered does not match our records. Please verify your information and try again.']);
    }
} catch (PDOException $e) {
    error_log("Parent login API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'We are experiencing technical difficulties. Please try again later or contact your school administration.']);
}
