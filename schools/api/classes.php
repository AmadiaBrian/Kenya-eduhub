<?php
// Classes Management API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$school_id = get_current_school_id();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check auth without strict token verification for debugging
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized - No school session found', 401);
    }
    
    // require_school_auth();
    
    try {
        $query = "SELECT c.*, s.school_type as class_level,
                  (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
                  (SELECT COUNT(*) FROM streams WHERE class_id = c.id) as stream_count
                  FROM classes c
                  JOIN schools s ON c.school_id = s.id
                  WHERE c.school_id = ?
                  ORDER BY c.class_name";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id]);
        $classes = $stmt->fetchAll();
        
        success_response($classes, 'Classes retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch classes: " . $e->getMessage());
        error_response('Failed to fetch classes', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    $required_fields = ['class_name'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            error_response("Field '$field' is required");
        }
    }
    
    $class_name = sanitize_input($input['class_name']);
    $capacity = !empty($input['capacity']) ? (int)$input['capacity'] : 0;
    
    try {
        // Get school_type to use as class_level
        $stmt = $pdo->prepare("SELECT school_type FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        $class_level = $school['school_type'] ?? 'Primary';
        
        $stmt = $pdo->prepare("INSERT INTO classes (school_id, class_name, class_level, capacity) VALUES (?, ?, ?, ?)");
        $stmt->execute([$school_id, $class_name, $class_level, $capacity]);
        
        $class_id = $pdo->lastInsertId();
        
        log_security_event('CLASS_ADDED', "Class added: $class_name (School ID: $school_id)");
        
        success_response(['class_id' => $class_id], 'Class added successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to add class: " . $e->getMessage());
        error_response('Failed to add class', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['class_id'])) {
        error_response('Class ID is required');
    }
    
    $class_id = (int)$input['class_id'];
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
        $stmt->execute([$class_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Class not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Class verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    $update_fields = [];
    $params = [];
    
    if (!empty($input['class_name'])) {
        $update_fields[] = "class_name = ?";
        $params[] = sanitize_input($input['class_name']);
    }
    if (isset($input['capacity'])) {
        $update_fields[] = "capacity = ?";
        $params[] = (int)$input['capacity'];
    }
    
    // Update class_level from school_type
    $stmt = $pdo->prepare("SELECT school_type FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $class_level = $school['school_type'] ?? 'Primary';
    $update_fields[] = "class_level = ?";
    $params[] = $class_level;
    
    if (empty($update_fields)) {
        error_response('No fields to update');
    }
    
    $params[] = $class_id;
    
    try {
        $query = "UPDATE classes SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        log_security_event('CLASS_UPDATED', "Class updated: ID $class_id (School ID: $school_id)");
        
        success_response([], 'Class updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update class: " . $e->getMessage());
        error_response('Failed to update class', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_school_auth();
    
    $class_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$class_id) {
        error_response('Class ID is required');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
        $stmt->execute([$class_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Class not found', 404);
        }
        
        $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        
        log_security_event('CLASS_DELETED', "Class deleted: ID $class_id (School ID: $school_id)");
        
        success_response([], 'Class deleted successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to delete class: " . $e->getMessage());
        error_response('Failed to delete class', 500);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
