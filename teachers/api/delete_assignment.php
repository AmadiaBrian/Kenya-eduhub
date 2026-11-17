<?php
// Assignment Delete API
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

$raw_input = file_get_contents('php://input');
$input_json = json_decode($raw_input, true) ?: [];

$assignment_id = intval($_POST['assignment_id'] ?? ($input_json['assignment_id'] ?? 0));

if (!$assignment_id) {
    echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
        exit;
    }
    
    if ($assignment['teacher_id'] != $teacher_id) {
        echo json_encode(['success' => false, 'error' => 'You can only delete your own assignments']);
        exit;
    }
    
    $file_path = __DIR__ . '/../../uploads/assignments/' . $assignment['file_path'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    $stmt = $pdo->prepare("DELETE FROM assignment_downloads WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->execute([$assignment_id]);
    
    echo json_encode(['success' => true, 'message' => 'Assignment deleted successfully']);
} catch (PDOException $e) {
    error_log("Failed to delete assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to delete assignment: ' . $e->getMessage()]);
}
