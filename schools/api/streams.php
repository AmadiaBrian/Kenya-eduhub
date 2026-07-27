<?php
// Streams Management API
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
    
    $class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
    
    try {
        if ($class_id) {
            // Verify class belongs to this school
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Class not found', 404);
            }
            
            $query = "SELECT s.*, c.class_name, 
                     (SELECT COUNT(*) FROM students WHERE stream_id = s.id) as student_count
                     FROM streams s
                     JOIN classes c ON s.class_id = c.id
                     WHERE s.class_id = ?
                     ORDER BY s.stream_name";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$class_id]);
        } else {
            // Return unique stream names with aggregated data
            $query = "SELECT s.stream_name, 
                             GROUP_CONCAT(DISTINCT c.class_name ORDER BY c.class_level, c.class_name SEPARATOR ', ') as class_names,
                             GROUP_CONCAT(DISTINCT c.class_level ORDER BY c.class_level SEPARATOR ', ') as class_levels,
                             SUM(s.capacity) as total_capacity,
                             (SELECT COUNT(*) FROM students st JOIN streams s2 ON st.stream_id = s2.id WHERE s2.stream_name = s.stream_name) as student_count
                     FROM streams s
                     JOIN classes c ON s.class_id = c.id
                     WHERE c.school_id = ?
                     GROUP BY s.stream_name
                     ORDER BY s.stream_name";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$school_id]);
        }
        
        $streams = $stmt->fetchAll();
        
        success_response($streams, 'Streams retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch streams: " . $e->getMessage());
        error_response('Failed to fetch streams', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    if (empty($input['class_id']) || empty($input['stream_name'])) {
        error_response('Class ID and stream name are required');
    }
    
    $class_id = (int)$input['class_id'];
    $stream_name = sanitize_input($input['stream_name']);
    $capacity = !empty($input['capacity']) ? (int)$input['capacity'] : 0;
    
    // Verify class belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
        $stmt->execute([$class_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Class not found', 404);
        }
        
        $stmt = $pdo->prepare("INSERT INTO streams (class_id, stream_name, capacity) VALUES (?, ?, ?)");
        $stmt->execute([$class_id, $stream_name, $capacity]);
        
        $stream_id = $pdo->lastInsertId();
        
        log_security_event('STREAM_ADDED', "Stream added: $stream_name (Class ID: $class_id, School ID: $school_id)");
        
        success_response(['stream_id' => $stream_id], 'Stream added successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to add stream: " . $e->getMessage());
        error_response('Failed to add stream', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['stream_id'])) {
        error_response('Stream ID is required');
    }
    
    $stream_id = (int)$input['stream_id'];
    
    try {
        $query = "SELECT s.id FROM streams s
                 JOIN classes c ON s.class_id = c.id
                 WHERE s.id = ? AND c.school_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$stream_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Stream not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Stream verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    $update_fields = [];
    $params = [];
    
    if (!empty($input['stream_name'])) {
        $update_fields[] = "stream_name = ?";
        $params[] = sanitize_input($input['stream_name']);
    }
    if (isset($input['capacity'])) {
        $update_fields[] = "capacity = ?";
        $params[] = (int)$input['capacity'];
    }
    
    if (empty($update_fields)) {
        error_response('No fields to update');
    }
    
    $params[] = $stream_id;
    
    try {
        $query = "UPDATE streams SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        log_security_event('STREAM_UPDATED', "Stream updated: ID $stream_id (School ID: $school_id)");
        
        success_response([], 'Stream updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update stream: " . $e->getMessage());
        error_response('Failed to update stream', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_school_auth();
    
    $stream_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$stream_id) {
        error_response('Stream ID is required');
    }
    
    try {
        $query = "SELECT s.id FROM streams s
                 JOIN classes c ON s.class_id = c.id
                 WHERE s.id = ? AND c.school_id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$stream_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Stream not found', 404);
        }
        
        $stmt = $pdo->prepare("DELETE FROM streams WHERE id = ?");
        $stmt->execute([$stream_id]);
        
        log_security_event('STREAM_DELETED', "Stream deleted: ID $stream_id (School ID: $school_id)");
        
        success_response([], 'Stream deleted successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to delete stream: " . $e->getMessage());
        error_response('Failed to delete stream', 500);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
