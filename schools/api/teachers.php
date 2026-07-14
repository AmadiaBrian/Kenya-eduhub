<?php
// Teachers API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
        $query = "SELECT t.*, c.class_name, s.stream_name 
                 FROM teachers t
                 LEFT JOIN classes c ON t.class_id = c.id
                 LEFT JOIN streams s ON t.stream_id = s.id
                 WHERE t.school_id = ?
                 ORDER BY t.created_at DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id]);
        $teachers = $stmt->fetchAll();
        
        // Fetch subject assignments for each teacher
        foreach ($teachers as &$teacher) {
            $stmt = $pdo->prepare("SELECT ts.*, c.class_name, s.subject_name 
                                 FROM teacher_subjects ts
                                 JOIN classes c ON ts.class_id = c.id
                                 LEFT JOIN subjects s ON ts.subject_id = s.id
                                 WHERE ts.teacher_id = ?");
            $stmt->execute([$teacher['id']]);
            $teacher['subject_assignments'] = $stmt->fetchAll();
        }
        
        success_response($teachers, 'Teachers retrieved successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to fetch teachers: " . $e->getMessage());
        error_response('Failed to fetch teachers', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid JSON input');
    }
    
    $required_fields = ['first_name', 'last_name', 'email', 'phone', 'id_number', 'password'];
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
    $teacher_type = !empty($input['teacher_type']) ? sanitize_input($input['teacher_type']) : 'subject_teacher';
    $password = $input['password'];
    $address = !empty($input['address']) ? sanitize_input($input['address']) : null;
    
    // Validate teacher type
    if (!in_array($teacher_type, ['class_teacher', 'subject_teacher'])) {
        error_response('Invalid teacher type');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_response('Invalid email format');
    }
    
    // Check if email already exists
    try {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE email = ? AND school_id = ?");
        $stmt->execute([$email, $school_id]);
        if ($stmt->fetch()) {
            error_response('Email already exists');
        }
    } catch (PDOException $e) {
        error_log("Email check failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    $class_id = null;
    $stream_id = null;
    $subject_assignments = [];
    
    if ($teacher_type === 'class_teacher') {
        // Class teacher must have class assignment
        if (empty($input['class_id'])) {
            error_response('Class teachers must be assigned to a class');
        }
        
        $class_id = (int)$input['class_id'];
        $stream_id = !empty($input['stream_id']) ? (int)$input['stream_id'] : null;
        
        // Verify class belongs to this school
        try {
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Invalid class selected');
            }
        } catch (PDOException $e) {
            error_log("Class verification failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
        
        // Verify stream belongs to the class
        if ($stream_id && $class_id) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM streams WHERE id = ? AND class_id = ?");
                $stmt->execute([$stream_id, $class_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid stream selected');
                }
            } catch (PDOException $e) {
                error_log("Stream verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    } else {
        // Subject teacher must have subject assignments
        if (empty($input['subject_assignments']) || !is_array($input['subject_assignments'])) {
            error_response('Subject teachers must have at least one subject assignment');
        }
        
        $subject_assignments = $input['subject_assignments'];
        
        // Validate each subject assignment
        foreach ($subject_assignments as $assignment) {
            if (empty($assignment['class_id']) || empty($assignment['subject_id'])) {
                error_response('Each subject assignment must have a class and subject');
            }
            
            $assignment_class_id = (int)$assignment['class_id'];
            $assignment_subject_id = (int)$assignment['subject_id'];
            
            // Verify class belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_class_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid class selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Class verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
            
            // Verify subject belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_subject_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid subject selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Subject verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    }
    
    // Handle optional subject assignments for class teachers
    if ($teacher_type === 'class_teacher' && !empty($input['subject_assignments']) && is_array($input['subject_assignments'])) {
        $subject_assignments = $input['subject_assignments'];
        
        // Validate each subject assignment
        foreach ($subject_assignments as $assignment) {
            if (empty($assignment['class_id']) || empty($assignment['subject_id'])) {
                error_response('Each subject assignment must have a class and subject');
            }
            
            $assignment_class_id = (int)$assignment['class_id'];
            $assignment_subject_id = (int)$assignment['subject_id'];
            
            // Verify class belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_class_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid class selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Class verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
            
            // Verify subject belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_subject_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid subject selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Subject verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert teacher
        $stmt = $pdo->prepare("INSERT INTO teachers (school_id, class_id, stream_id, teacher_type, first_name, last_name, email, phone, id_number, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $class_id, $stream_id, $teacher_type, $first_name, $last_name, $email, $phone, $id_number, $address]);
        $teacher_id = $pdo->lastInsertId();
        
        // Create teacher login
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO teacher_logins (teacher_id, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$teacher_id, $email, $hashed_password]);
        
        // Create subject assignments for both teacher types
        if (!empty($subject_assignments)) {
            foreach ($subject_assignments as $assignment) {
                $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, class_id, subject_id) VALUES (?, ?, ?)");
                $stmt->execute([$teacher_id, (int)$assignment['class_id'], (int)$assignment['subject_id']]);
            }
        }
        
        $pdo->commit();
        
        success_response(['teacher_id' => $teacher_id], 'Teacher added successfully');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to add teacher: " . $e->getMessage());
        error_response('Failed to add teacher', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid JSON input');
    }
    
    $required_fields = ['teacher_id', 'first_name', 'last_name', 'email', 'phone', 'id_number'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            error_response("Field '$field' is required");
        }
    }
    
    $teacher_id = (int)$input['teacher_id'];
    $first_name = sanitize_input($input['first_name']);
    $last_name = sanitize_input($input['last_name']);
    $email = sanitize_input($input['email']);
    $phone = sanitize_input($input['phone']);
    $id_number = sanitize_input($input['id_number']);
    $teacher_type = !empty($input['teacher_type']) ? sanitize_input($input['teacher_type']) : 'subject_teacher';
    $status = sanitize_input($input['status']);
    $address = !empty($input['address']) ? sanitize_input($input['address']) : null;
    
    // Validate teacher type
    if (!in_array($teacher_type, ['class_teacher', 'subject_teacher'])) {
        error_response('Invalid teacher type');
    }
    
    // Verify teacher belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE id = ? AND school_id = ?");
        $stmt->execute([$teacher_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Teacher not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Teacher verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    // Check if email already exists for another teacher
    try {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE email = ? AND school_id = ? AND id != ?");
        $stmt->execute([$email, $school_id, $teacher_id]);
        if ($stmt->fetch()) {
            error_response('Email already exists');
        }
    } catch (PDOException $e) {
        error_log("Email check failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    $class_id = null;
    $stream_id = null;
    $subject_assignments = [];
    
    if ($teacher_type === 'class_teacher') {
        // Class teacher must have class assignment
        if (empty($input['class_id'])) {
            error_response('Class teachers must be assigned to a class');
        }
        
        $class_id = (int)$input['class_id'];
        $stream_id = !empty($input['stream_id']) ? (int)$input['stream_id'] : null;
        
        // Verify class belongs to this school
        try {
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Invalid class selected');
            }
        } catch (PDOException $e) {
            error_log("Class verification failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
        
        // Verify stream belongs to the class
        if ($stream_id && $class_id) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM streams WHERE id = ? AND class_id = ?");
                $stmt->execute([$stream_id, $class_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid stream selected');
                }
            } catch (PDOException $e) {
                error_log("Stream verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    } else {
        // Subject teacher must have subject assignments
        if (empty($input['subject_assignments']) || !is_array($input['subject_assignments'])) {
            error_response('Subject teachers must have at least one subject assignment');
        }
        
        $subject_assignments = $input['subject_assignments'];
        
        // Validate each subject assignment
        foreach ($subject_assignments as $assignment) {
            if (empty($assignment['class_id']) || empty($assignment['subject_id'])) {
                error_response('Each subject assignment must have a class and subject');
            }
            
            $assignment_class_id = (int)$assignment['class_id'];
            $assignment_subject_id = (int)$assignment['subject_id'];
            
            // Verify class belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_class_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid class selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Class verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
            
            // Verify subject belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_subject_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid subject selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Subject verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    }
    
    // Handle optional subject assignments for class teachers
    if ($teacher_type === 'class_teacher' && !empty($input['subject_assignments']) && is_array($input['subject_assignments'])) {
        $subject_assignments = $input['subject_assignments'];
        
        // Validate each subject assignment
        foreach ($subject_assignments as $assignment) {
            if (empty($assignment['class_id']) || empty($assignment['subject_id'])) {
                error_response('Each subject assignment must have a class and subject');
            }
            
            $assignment_class_id = (int)$assignment['class_id'];
            $assignment_subject_id = (int)$assignment['subject_id'];
            
            // Verify class belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_class_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid class selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Class verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
            
            // Verify subject belongs to this school
            try {
                $stmt = $pdo->prepare("SELECT id FROM subjects WHERE id = ? AND school_id = ?");
                $stmt->execute([$assignment_subject_id, $school_id]);
                if (!$stmt->fetch()) {
                    error_response('Invalid subject selected in subject assignment');
                }
            } catch (PDOException $e) {
                error_log("Subject verification failed: " . $e->getMessage());
                error_response('Database error', 500);
            }
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Update teacher
        $stmt = $pdo->prepare("UPDATE teachers SET class_id = ?, stream_id = ?, teacher_type = ?, first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, status = ?, address = ? WHERE id = ?");
        $stmt->execute([$class_id, $stream_id, $teacher_type, $first_name, $last_name, $email, $phone, $id_number, $status, $address, $teacher_id]);
        
        // Delete existing subject assignments for this teacher
        $stmt = $pdo->prepare("DELETE FROM teacher_subjects WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        
        // Create new subject assignments for both teacher types
        if (!empty($subject_assignments)) {
            foreach ($subject_assignments as $assignment) {
                $stmt = $pdo->prepare("INSERT INTO teacher_subjects (teacher_id, class_id, subject_id) VALUES (?, ?, ?)");
                $stmt->execute([$teacher_id, (int)$assignment['class_id'], (int)$assignment['subject_id']]);
            }
        }
        
        $pdo->commit();
        
        success_response([], 'Teacher updated successfully');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to update teacher: " . $e->getMessage());
        error_response('Failed to update teacher', 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $teacher_id = $_GET['id'] ?? null;
    
    if (!$teacher_id) {
        error_response('Teacher ID is required');
    }
    
    // Verify teacher belongs to this school
    try {
        $stmt = $pdo->prepare("SELECT id FROM teachers WHERE id = ? AND school_id = ?");
        $stmt->execute([$teacher_id, $school_id]);
        if (!$stmt->fetch()) {
            error_response('Teacher not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Teacher verification failed: " . $e->getMessage());
        error_response('Database error', 500);
    }
    
    try {
        $pdo->beginTransaction();
        
        // Delete teacher login
        $stmt = $pdo->prepare("DELETE FROM teacher_logins WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        
        // Delete teacher sessions
        $stmt = $pdo->prepare("DELETE FROM teacher_sessions WHERE teacher_id = ?");
        $stmt->execute([$teacher_id]);
        
        // Delete teacher
        $stmt = $pdo->prepare("DELETE FROM teachers WHERE id = ?");
        $stmt->execute([$teacher_id]);
        
        $pdo->commit();
        
        success_response([], 'Teacher deleted successfully');
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Failed to delete teacher: " . $e->getMessage());
        error_response('Failed to delete teacher', 500);
    }
}
?>
