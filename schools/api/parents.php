<?php
// Parents Management API
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
    
    $teacher_view = isset($_GET['teacher_view']) && $_GET['teacher_view'] === 'true';
    $teacher_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;
    $teacher_stream_id = isset($_GET['stream_id']) ? (int)$_GET['stream_id'] : null;
    $teacher_type = isset($_GET['teacher_type']) ? $_GET['teacher_type'] : 'class_teacher';
    
    try {
        if ($teacher_view) {
            // Teacher view - filter parents by their assigned class/stream
            if ($teacher_type === 'class_teacher' && $teacher_class_id) {
                // Class teacher: show parents of students in their class/stream
                $where_clause = "p.school_id = ? AND s.class_id = ?";
                $params = [$school_id, $teacher_class_id];
                
                if ($teacher_stream_id) {
                    $where_clause .= " AND s.stream_id = ?";
                    $params[] = $teacher_stream_id;
                }
            } else {
                // Subject teacher or no class assigned - show all parents (or could filter by assigned classes)
                $where_clause = "p.school_id = ?";
                $params = [$school_id];
            }
            
            $query = "SELECT DISTINCT p.*, 
                     GROUP_CONCAT(CONCAT(s.admission_number, ' - ', s.first_name, ' ', s.last_name) SEPARATOR ', ') as linked_students,
                     (SELECT COUNT(*) FROM student_parents WHERE parent_id = p.id) as children_count
                     FROM parents p
                     LEFT JOIN student_parents sp ON p.id = sp.parent_id
                     LEFT JOIN students s ON sp.student_id = s.id
                     WHERE $where_clause
                     GROUP BY p.id
                     ORDER BY p.created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
        } else {
            // School admin view - show all parents
            $query = "SELECT p.*, 
                     GROUP_CONCAT(CONCAT(s.admission_number, ' - ', s.first_name, ' ', s.last_name) SEPARATOR ', ') as linked_students,
                     (SELECT COUNT(*) FROM student_parents WHERE parent_id = p.id) as children_count
                     FROM parents p
                     LEFT JOIN student_parents sp ON p.id = sp.parent_id
                     LEFT JOIN students s ON sp.student_id = s.id
                     WHERE p.school_id = ?
                     GROUP BY p.id
                     ORDER BY p.created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$school_id]);
        }
        
        $parents = $stmt->fetchAll();
        
        success_response($parents, 'Parents retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch parents: " . $e->getMessage());
        error_response('Failed to fetch parents', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'id_number', 'relationship', 'admission_number'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            error_response("Field '$field' is required");
        }
    }
    
    $first_name = sanitize_input($input['first_name']);
    $last_name = sanitize_input($input['last_name']);
    $email = sanitize_input($input['email']);
    $phone = sanitize_input($input['phone']);
    $id_number = sanitize_input($input['id_number']);
    $address = !empty($input['address']) ? sanitize_input($input['address']) : null;
    $relationship = sanitize_input($input['relationship']);
    $admission_number = sanitize_input($input['admission_number']);
    
    // Validate email
    if (!validate_email($email)) {
        error_response('Invalid email format');
    }
    
    // Validate relationship
    $valid_relationships = ['Father', 'Mother', 'Guardian'];
    if (!in_array($relationship, $valid_relationships)) {
        error_response('Invalid relationship');
    }
    
    // Verify student exists and belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE admission_number = ? AND school_id = ?");
        $stmt->execute([$admission_number, $school_id]);
        $student = $stmt->fetch();
        if (!$student) {
            error_response('Student not found with this admission number', 404);
        }
        $student_id = $student['id'];
    } catch (PDOException $e) {
        error_log("Student verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Check if email already exists for this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM parents WHERE email = ? AND school_id = ?");
        $stmt->execute([$email, $school_id]);
        if ($stmt->fetch()) {
            error_response('Email already registered for this school');
        }
        
        $stmt = $pdo->prepare("INSERT INTO parents (school_id, first_name, last_name, email, phone, id_number, address, relationship) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $first_name, $last_name, $email, $phone, $id_number, $address, $relationship]);
        
        $parent_id = $pdo->lastInsertId();
        
        // Link parent to student
        $stmt = $pdo->prepare("INSERT INTO student_parents (student_id, parent_id, is_primary) VALUES (?, ?, 1)");
        $stmt->execute([$student_id, $parent_id]);
        
        log_security_event('PARENT_ADDED', "Parent added: $email (School ID: $school_id)");
        
        success_response(['parent_id' => $parent_id], 'Parent added successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to add parent: " . $e->getMessage());
        error_response('Failed to add parent', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['parent_id'])) {
        error_response('Parent ID is required');
    }
    
    $parent_id = (int)$input['parent_id'];
    
    // Verify parent belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM parents WHERE id = ? AND school_id = ?");
        $stmt->execute([$parent_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Parent not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Parent verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    $update_fields = [];
    $params = [];
    
    if (!empty($input['first_name'])) {
        $update_fields[] = "first_name = ?";
        $params[] = sanitize_input($input['first_name']);
    }
    if (!empty($input['last_name'])) {
        $update_fields[] = "last_name = ?";
        $params[] = sanitize_input($input['last_name']);
    }
    if (!empty($input['email'])) {
        if (!validate_email($input['email'])) {
            error_response('Invalid email format');
        }
        $update_fields[] = "email = ?";
        $params[] = sanitize_input($input['email']);
    }
    if (!empty($input['phone'])) {
        $update_fields[] = "phone = ?";
        $params[] = sanitize_input($input['phone']);
    }
    if (!empty($input['id_number'])) {
        $update_fields[] = "id_number = ?";
        $params[] = sanitize_input($input['id_number']);
    }
    if (isset($input['address'])) {
        $update_fields[] = "address = ?";
        $params[] = $input['address'] ? sanitize_input($input['address']) : null;
    }
    if (!empty($input['relationship'])) {
        $valid_relationships = ['Father', 'Mother', 'Guardian'];
        if (!in_array($input['relationship'], $valid_relationships)) {
            error_response('Invalid relationship');
        }
        $update_fields[] = "relationship = ?";
        $params[] = sanitize_input($input['relationship']);
    }
    
    if (empty($update_fields)) {
        error_response('No fields to update');
    }
    
    $params[] = $parent_id;
    
    try {
        $query = "UPDATE parents SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        log_security_event('PARENT_UPDATED', "Parent updated: ID $parent_id (School ID: $school_id)");
        
        success_response([], 'Parent updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update parent: " . $e->getMessage());
        error_response('Failed to update parent', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_school_auth();
    
    $parent_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$parent_id) {
        error_response('Parent ID is required');
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM parents WHERE id = ? AND school_id = ?");
        $stmt->execute([$parent_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Parent not found', 404);
        }
        
        $stmt = $pdo->prepare("DELETE FROM parents WHERE id = ?");
        $stmt->execute([$parent_id]);
        
        log_security_event('PARENT_DELETED', "Parent deleted: ID $parent_id (School ID: $school_id)");
        
        success_response([], 'Parent deleted successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to delete parent: " . $e->getMessage());
        error_response('Failed to delete parent', 500);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
