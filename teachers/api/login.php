<?php
// Teacher Login API
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once __DIR__ . '/../../config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Email and password are required']);
    exit;
}

try {
    // Query teacher login credentials
    $stmt = $pdo->prepare("SELECT tl.id, tl.teacher_id, tl.password, t.first_name, t.last_name, t.school_id, t.class_id, t.stream_id, c.class_name, s.stream_name
                           FROM teacher_logins tl
                           JOIN teachers t ON tl.teacher_id = t.id
                           LEFT JOIN classes c ON t.class_id = c.id
                           LEFT JOIN streams s ON t.stream_id = s.id
                           WHERE tl.email = ? AND tl.is_active = 1");
    $stmt->execute([$email]);
    $teacher_login = $stmt->fetch();

    if ($teacher_login && password_verify($password, $teacher_login['password'])) {
        // Generate session token
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+8 hours'));

        // Store session in database
        $stmt = $pdo->prepare("INSERT INTO teacher_sessions (teacher_id, session_token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_login['teacher_id'], $session_token, $expires_at]);

        // Set session variables
        $_SESSION['teacher_id'] = $teacher_login['teacher_id'];
        $_SESSION['teacher_name'] = $teacher_login['first_name'] . ' ' . $teacher_login['last_name'];
        $_SESSION['school_id'] = $teacher_login['school_id'];
        $_SESSION['class_id'] = $teacher_login['class_id'];
        $_SESSION['stream_id'] = $teacher_login['stream_id'];
        $_SESSION['class_name'] = $teacher_login['class_name'];
        $_SESSION['stream_name'] = $teacher_login['stream_name'];
        $_SESSION['teacher_session_token'] = $session_token;
        $_SESSION['user_type'] = 'teacher';

        echo json_encode([
            'success' => true,
            'session_token' => $session_token,
            'teacher' => [
                'id' => $teacher_login['teacher_id'],
                'name' => $teacher_login['first_name'] . ' ' . $teacher_login['last_name'],
                'school_id' => $teacher_login['school_id'],
                'class_id' => $teacher_login['class_id'],
                'stream_id' => $teacher_login['stream_id'],
                'class_name' => $teacher_login['class_name'],
                'stream_name' => $teacher_login['stream_name']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
    }
} catch (PDOException $e) {
    error_log("Teacher login error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
