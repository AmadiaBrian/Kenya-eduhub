<?php
// Students Management API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

// Get current school ID
$school_id = get_current_school_id();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_school_auth();
    
    // Get filter parameters
    $class_id = $_GET['class_id'] ?? null;
    $stream_id = $_GET['stream_id'] ?? null;
    
    // Get all students for the school
    try {
        $query = "SELECT s.*, c.class_name, st.stream_name, 
                  CONCAT(p.first_name, ' ', p.last_name) as parent_name
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  LEFT JOIN student_parents sp ON s.id = sp.student_id AND sp.is_primary = 1
                  LEFT JOIN parents p ON sp.parent_id = p.id
                  WHERE s.school_id = ?";
        $params = [$school_id];
        
        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
        }
        
        if ($stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $stream_id;
        }
        
        $query .= " ORDER BY s.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll();
        
        // Calculate fee balance for each student
        foreach ($students as &$student) {
            $student_id = $student['id'];
            $class_id = $student['class_id'];
            
            // Get current term and year
            $current_term = 'Term 1'; // Default, could be dynamic
            $current_year = date('Y');
            
            // Get total fees for student's class
            $total_fees = 0;
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT amount FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ?");
                $stmt->execute([$school_id, $class_id, $current_term, $current_year]);
                $fee_structure = $stmt->fetch();
                if ($fee_structure) {
                    $total_fees = $fee_structure['amount'];
                }
            }
            
            // Get total payments for this student
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM fee_payments WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $payments = $stmt->fetch();
            $total_paid = $payments['total_paid'];
            
            // Calculate balance
            $balance = $total_fees - $total_paid;
            $student['fee_balance'] = $balance;
            $student['total_fees'] = $total_fees;
            $student['total_paid'] = $total_paid;
        }
        
        success_response($students, 'Students retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch students: " . $e->getMessage());
        error_response('Failed to fetch students', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    // Rate limiting
    $ip = $_SERVER['REMOTE_ADDR'];
    if (!check_rate_limit('add_student_' . $school_id . '_' . $ip, 20, 300)) {
        error_response('Too many requests. Please try again later.', 429);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    // Validate required fields
    $required_fields = ['first_name', 'last_name', 'gender', 'date_of_birth', 'admission_date'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            error_response("Field '$field' is required");
        }
    }
    
    // Sanitize inputs
    $first_name = sanitize_input($input['first_name']);
    $last_name = sanitize_input($input['last_name']);
    $gender = sanitize_input($input['gender']);
    $date_of_birth = sanitize_input($input['date_of_birth']);
    $admission_date = sanitize_input($input['admission_date']);
    $class_id = !empty($input['class_id']) ? (int)$input['class_id'] : null;
    $stream_id = !empty($input['stream_id']) ? (int)$input['stream_id'] : null;
    
    // Validate gender
    if (!in_array($gender, ['Male', 'Female'])) {
        error_response('Invalid gender');
    }
    
    // Generate admission number
    $year = date('Y', strtotime($admission_date));
    $admission_number = generate_admission_number($school_id, $year);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO students (school_id, admission_number, first_name, last_name, gender, date_of_birth, class_id, stream_id, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $admission_number, $first_name, $last_name, $gender, $date_of_birth, $class_id, $stream_id, $admission_date]);
        
        $student_id = $pdo->lastInsertId();
        
        log_security_event('STUDENT_ADDED', "Student added: $admission_number (School ID: $school_id)");
        
        success_response([
            'student_id' => $student_id,
            'admission_number' => $admission_number
        ], 'Student added successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to add student: " . $e->getMessage());
        error_response('Failed to add student', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['student_id'])) {
        error_response('Student ID is required');
    }
    
    $student_id = (int)$input['student_id'];
    
    // Verify student belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
        $stmt->execute([$student_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Student not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Student verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Build update query dynamically
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
    if (!empty($input['gender'])) {
        if (!in_array($input['gender'], ['Male', 'Female'])) {
            error_response('Invalid gender');
        }
        $update_fields[] = "gender = ?";
        $params[] = sanitize_input($input['gender']);
    }
    if (!empty($input['date_of_birth'])) {
        $update_fields[] = "date_of_birth = ?";
        $params[] = sanitize_input($input['date_of_birth']);
    }
    if (isset($input['class_id'])) {
        $update_fields[] = "class_id = ?";
        $params[] = $input['class_id'] ? (int)$input['class_id'] : null;
    }
    if (isset($input['stream_id'])) {
        $update_fields[] = "stream_id = ?";
        $params[] = $input['stream_id'] ? (int)$input['stream_id'] : null;
    }
    if (isset($input['status'])) {
        $valid_statuses = ['active', 'inactive', 'transferred', 'graduated'];
        if (!in_array($input['status'], $valid_statuses)) {
            error_response('Invalid status');
        }
        $update_fields[] = "status = ?";
        $params[] = sanitize_input($input['status']);
    }
    
    if (empty($update_fields)) {
        error_response('No fields to update');
    }
    
    $params[] = $student_id;
    
    try {
        $query = "UPDATE students SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        log_security_event('STUDENT_UPDATED', "Student updated: ID $student_id (School ID: $school_id)");
        
        success_response([], 'Student updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update student: " . $e->getMessage());
        error_response('Failed to update student', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_school_auth();
    
    $student_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$student_id) {
        error_response('Student ID is required');
    }
    
    // Verify student belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
        $stmt->execute([$student_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Student not found', 404);
        }
        
        $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        
        log_security_event('STUDENT_DELETED', "Student deleted: ID $student_id (School ID: $school_id)");
        
        success_response([], 'Student deleted successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to delete student: " . $e->getMessage());
        error_response('Failed to delete student', 500);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
