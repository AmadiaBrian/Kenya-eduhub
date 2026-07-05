<?php
// Subjects API
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

function success_response($data, $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error_response($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function require_school_auth() {
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized', 401);
    }
}

require_school_auth();

$school_id = $_SESSION['school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $query = "SELECT * FROM subjects WHERE school_id = ? ORDER BY subject_name ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id]);
        $subjects = $stmt->fetchAll();
        
        success_response($subjects, 'Subjects retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects: " . $e->getMessage());
        error_response('Failed to fetch subjects', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid JSON input');
    }
    
    if (empty($input['subject_name'])) {
        error_response("Subject name is required");
    }
    
    $subject_name = sanitize_input($input['subject_name']);
    $subject_code = !empty($input['subject_code']) ? sanitize_input($input['subject_code']) : null;
    $status = !empty($input['status']) ? sanitize_input($input['status']) : 'active';
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        error_response('Invalid status');
    }
    
    // Check if subject name already exists for this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ? AND school_id = ?");
        $stmt->execute([$subject_name, $school_id]);
        if ($stmt->fetch()) {
            error_response('Subject name already exists');
        }
    } catch (PDOException $e) {
        error_log("Subject name check failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Check if subject code already exists for this school
    if ($subject_code) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? AND school_id = ?");
            $stmt->execute([$subject_code, $school_id]);
            if ($stmt->fetch()) {
                error_response('Subject code already exists');
            }
        } catch (PDOException $e) {
            error_log("Subject code check failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (school_id, subject_name, subject_code, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $subject_name, $subject_code, $status]);
        
        success_response(['subject_id' => $pdo->lastInsertId()], 'Subject added successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to add subject: " . $e->getMessage());
        error_response('Failed to add subject', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid JSON input');
    }
    
    if (empty($input['subject_id']) || empty($input['subject_name'])) {
        error_response("Subject ID and subject name are required");
    }
    
    $subject_id = (int)$input['subject_id'];
    $subject_name = sanitize_input($input['subject_name']);
    $subject_code = !empty($input['subject_code']) ? sanitize_input($input['subject_code']) : null;
    $status = !empty($input['status']) ? sanitize_input($input['status']) : 'active';
    
    // Validate status
    if (!in_array($status, ['active', 'inactive'])) {
        error_response('Invalid status');
    }
    
    // Verify subject belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
        $stmt->execute([$subject_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Subject not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Subject verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Check if subject name already exists for another subject
    try {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_name = ? AND school_id = ? AND id != ?");
        $stmt->execute([$subject_name, $school_id, $subject_id]);
        if ($stmt->fetch()) {
            error_response('Subject name already exists');
        }
    } catch (PDOException $e) {
        error_log("Subject name check failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Check if subject code already exists for another subject
    if ($subject_code) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code = ? AND school_id = ? AND id != ?");
            $stmt->execute([$subject_code, $school_id, $subject_id]);
            if ($stmt->fetch()) {
                error_response('Subject code already exists');
            }
        } catch (PDOException $e) {
            error_log("Subject code check failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, status = ? WHERE id = ?");
        $stmt->execute([$subject_name, $subject_code, $status, $subject_id]);
        
        success_response([], 'Subject updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update subject: " . $e->getMessage());
        error_response('Failed to update subject', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $subject_id = $_GET['id'] ?? null;
    
    if (!$subject_id) {
        error_response('Subject ID is required');
    }
    
    // Verify subject belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
        $stmt->execute([$subject_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Subject not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Subject verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    try {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        
        success_response([], 'Subject deleted successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to delete subject: " . $e->getMessage());
        error_response('Failed to delete subject', 500);
    }
}
?>
