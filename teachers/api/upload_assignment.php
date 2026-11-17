<?php
// Assignment Upload API
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $assignment_type = $_POST['assignment_type'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $target_class_id = $_POST['class_id'] ?? '';
    $target_subject_id = $_POST['subject_id'] ?? '';
    $due_date = $_POST['due_date'] ?? '';

    if (empty($assignment_type) || empty($title) || empty($_FILES['file']['name'])) {
        echo json_encode(['success' => false, 'error' => 'Please fill in all required fields']);
        exit;
    }
    
    $file = $_FILES['file'];
    $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_types)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Allowed: PDF, DOC, DOCX, PPT, PPTX, TXT, JPG, PNG']);
        exit;
    }
    
    if ($file['size'] > 10 * 1024 * 1024) { // 10MB
        echo json_encode(['success' => false, 'error' => 'File size exceeds 10MB limit']);
        exit;
    }
    
    $upload_dir = __DIR__ . '/../../uploads/assignments/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $file_ext;
    $filepath = $upload_dir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $stmt = $pdo->prepare("
            INSERT INTO assignments (teacher_id, school_id, class_id, subject_id, title, description, 
                                   assignment_type, file_path, file_name, due_date, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $teacher_id,
            $school_id,
            $target_class_id ?: null,
            $target_subject_id ?: null,
            $title,
            $description,
            $assignment_type,
            $filename,
            $file['name'],
            $due_date ?: null
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Assignment uploaded successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file. Please try again.']);
    }
} catch (PDOException $e) {
    error_log("Failed to save assignment: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to save assignment: ' . $e->getMessage()]);
}
