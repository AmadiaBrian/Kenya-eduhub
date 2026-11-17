<?php
// Assignment Update API
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
    $raw_input = file_get_contents('php://input');
    $input_json = json_decode($raw_input, true) ?: [];

    $assignment_id = intval($_POST['assignment_id'] ?? ($input_json['assignment_id'] ?? 0));
    $title = trim($_POST['title'] ?? ($input_json['title'] ?? ''));
    $description = trim($_POST['description'] ?? ($input_json['description'] ?? ''));
    $assignment_type = trim($_POST['assignment_type'] ?? ($input_json['assignment_type'] ?? ''));
    $due_date = trim($_POST['due_date'] ?? ($input_json['due_date'] ?? ''));
    
    if (empty($assignment_id) || empty($title)) {
        echo json_encode(['success' => false, 'error' => 'Assignment ID and title are required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, file_path, assignment_type FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
    $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }

    if (empty($assignment_type)) {
        $assignment_type = $assignment['assignment_type'];
    }
    
    // Handle file upload if provided
    if (!empty($_FILES['file']['name'])) {
        $file = $_FILES['file'];
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type']);
            exit;
        }
        
        if ($file['size'] > 10 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']);
            exit;
        }
        
        $upload_dir = __DIR__ . '/../../uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        if ($assignment['file_path'] && file_exists($upload_dir . $assignment['file_path'])) {
            unlink($upload_dir . $assignment['file_path']);
        }
        
        $filename = uniqid() . '_' . time() . '.' . $file_ext;
        $filepath = $upload_dir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'error' => 'Failed to upload file']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, assignment_type = ?, file_path = ?, file_name = ?, due_date = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $assignment_type, $filename, $file['name'], $due_date ?: null, $assignment_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, assignment_type = ?, due_date = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$title, $description, $assignment_type, $due_date ?: null, $assignment_id]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Assignment updated successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to update assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
