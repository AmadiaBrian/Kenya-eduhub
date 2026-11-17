<?php
// Assignment Duplicate API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

function getAuthSessionToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $val) {
        if (strcasecmp($key, 'X-Session-Token') === 0 && !empty($val)) {
            return trim($val);
        }
        if (strcasecmp($key, 'Authorization') === 0 && !empty($val)) {
            if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) {
                return trim($matches[1]);
            }
            return trim($val);
        }
    }
    if (!empty($_SERVER['HTTP_X_SESSION_TOKEN'])) return trim($_SERVER['HTTP_X_SESSION_TOKEN']);
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $val = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) return trim($matches[1]);
        return trim($val);
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $val = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) return trim($matches[1]);
        return trim($val);
    }
    if (!empty($_GET['session_token'])) return trim($_GET['session_token']);
    if (!empty($_POST['session_token'])) return trim($_POST['session_token']);
    return '';
}

$school_id = null;
$teacher_id = null;

$session_token = getAuthSessionToken();

if (!empty($session_token)) {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, t.school_id
                              FROM teacher_sessions ts
                              JOIN teachers t ON ts.teacher_id = t.id
                              WHERE ts.session_token = ? AND ts.expires_at > NOW()");
        $stmt->execute([$session_token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            $teacher_id = $session['teacher_id'];
            $school_id = $session['school_id'];
        }
    } catch (PDOException $e) {
        error_log("Failed to verify session: " . $e->getMessage());
    }
}

if (!$school_id && isset($_SESSION['school_id'])) {
    $school_id = $_SESSION['school_id'];
    $teacher_id = $_SESSION['teacher_id'] ?? null;
}

if (!$school_id || !$teacher_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized or missing session token']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $assignment_id = intval($input['assignment_id'] ?? 0);
    
    if (!$assignment_id) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    $original = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$original) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO assignments (teacher_id, school_id, class_id, subject_id, title, description, assignment_type, file_path, file_name, due_date, created_at, updated_at)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $teacher_id,
        $school_id,
        $original['class_id'],
        $original['subject_id'],
        $original['title'] . ' (Copy)',
        $original['description'],
        $original['assignment_type'],
        $original['file_path'],
        $original['file_name'],
        $original['due_date']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment duplicated successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to duplicate assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
