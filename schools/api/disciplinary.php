<?php
// Disciplinary Records Management API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$school_id = get_current_school_id();
$type = $_GET['type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check auth without strict token verification for debugging
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized - No school session found', 401);
    }
    
    if ($type === 'records') {
        // Get disciplinary records
        try {
            $student_id = $_GET['student_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $action_type = $_GET['action_type'] ?? null;
            
            $query = "SELECT dr.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
                     c.class_name, st.stream_name
                     FROM disciplinary_records dr
                     JOIN students s ON dr.student_id = s.id
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN streams st ON s.stream_id = st.id
                     WHERE dr.school_id = ?";
            $params = [$school_id];
            
            if ($student_id) {
                $query .= " AND dr.student_id = ?";
                $params[] = $student_id;
            }
            
            if ($status) {
                $query .= " AND dr.status = ?";
                $params[] = $status;
            }
            
            if ($action_type) {
                $query .= " AND dr.action_type = ?";
                $params[] = $action_type;
            }
            
            $query .= " ORDER BY dr.incident_date DESC, dr.created_at DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $records = $stmt->fetchAll();
            
            success_response($records, 'Disciplinary records retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch disciplinary records: " . $e->getMessage());
            error_response('Failed to fetch disciplinary records', 500);
        }
        
    } elseif ($type === 'action_types') {
        // Get custom action types for school
        try {
            $stmt = $pdo->prepare("SELECT * FROM disciplinary_action_types WHERE school_id = ? AND is_active = 1 ORDER BY severity, action_name");
            $stmt->execute([$school_id]);
            $action_types = $stmt->fetchAll();
            
            success_response($action_types, 'Action types retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch action types: " . $e->getMessage());
            error_response('Failed to fetch action types', 500);
        }
        
    } elseif ($type === 'committee') {
        // Get disciplinary committee members
        try {
            $query = "SELECT dc.*, 
                     CASE 
                         WHEN dc.user_type = 'admin' THEN (SELECT CONCAT(school_name, ' (Admin)') FROM schools WHERE id = dc.user_id)
                         WHEN dc.user_type = 'teacher' THEN (SELECT CONCAT(first_name, ' ', last_name, ' (Teacher)') FROM teachers WHERE id = dc.user_id)
                         ELSE dc.user_type
                     END as member_name
                     FROM disciplinary_committee dc
                     WHERE dc.school_id = ? AND dc.is_active = 1
                     ORDER BY dc.role, dc.appointed_date";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$school_id]);
            $committee = $stmt->fetchAll();
            
            success_response($committee, 'Committee members retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch committee: " . $e->getMessage());
            error_response('Failed to fetch committee', 500);
        }
        
    } elseif ($type === 'stats') {
        // Get disciplinary statistics
        try {
            $stats = [];
            
            // Total records
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM disciplinary_records WHERE school_id = ?");
            $stmt->execute([$school_id]);
            $stats['total'] = $stmt->fetch()['total'];
            
            // By action type
            $stmt = $pdo->prepare("SELECT action_type, COUNT(*) as count FROM disciplinary_records WHERE school_id = ? GROUP BY action_type");
            $stmt->execute([$school_id]);
            $stats['by_action_type'] = $stmt->fetchAll();
            
            // By status
            $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM disciplinary_records WHERE school_id = ? GROUP BY status");
            $stmt->execute([$school_id]);
            $stats['by_status'] = $stmt->fetchAll();
            
            // Current year
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM disciplinary_records WHERE school_id = ? AND YEAR(incident_date) = YEAR(CURDATE())");
            $stmt->execute([$school_id]);
            $stats['this_year'] = $stmt->fetch()['count'];
            
            success_response($stats, 'Statistics retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch statistics: " . $e->getMessage());
            error_response('Failed to fetch statistics', 500);
        }
        
    } elseif ($type === 'view') {
        // Get single disciplinary record by ID
        $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
        
        if (!$id) {
            error_response('Record ID is required');
        }
        
        try {
            $stmt = $pdo->prepare("SELECT dr.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
                     c.class_name, st.stream_name, at.action_name
                     FROM disciplinary_records dr
                     JOIN students s ON dr.student_id = s.id
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN streams st ON s.stream_id = st.id
                     LEFT JOIN disciplinary_action_types at ON dr.action_type = at.action_type AND dr.school_id = at.school_id
                     WHERE dr.id = ? AND dr.school_id = ?");
            $stmt->execute([$id, $school_id]);
            $record = $stmt->fetch();
            
            if (!$record) {
                error_response('Record not found', 404);
            }
            
            success_response($record, 'Record retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch record: " . $e->getMessage());
            error_response('Failed to fetch record', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check auth without strict token verification for debugging
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized - No school session found', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    if ($type === 'record') {
        // Add disciplinary record
        $required_fields = ['student_id', 'action_type', 'severity', 'title', 'incident_date', 'action_date', 'reported_by', 'handled_by'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        try {
            // Verify student belongs to this school
            $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
            $stmt->execute([$input['student_id'], $school_id]);
            if (!$stmt->fetch()) {
                error_response('Student not found in this school', 404);
            }
            
            $stmt = $pdo->prepare("INSERT INTO disciplinary_records 
                (school_id, student_id, action_type, severity, title, description, incident_date, action_date, 
                 end_date, reported_by, handled_by, status, notes, evidence_files, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $school_id,
                $input['student_id'],
                $input['action_type'],
                $input['severity'],
                sanitize_input($input['title']),
                sanitize_input($input['description'] ?? ''),
                $input['incident_date'],
                $input['action_date'],
                $input['end_date'] ?? null,
                sanitize_input($input['reported_by']),
                sanitize_input($input['handled_by']),
                $input['status'] ?? 'pending',
                sanitize_input($input['notes'] ?? ''),
                $input['evidence_files'] ?? '',
                $_SESSION['school_id'] // Using school_id as created_by for now
            ]);
            
            $record_id = $pdo->lastInsertId();
            
            // If expulsion or death, update student status
            if ($input['action_type'] === 'expulsion') {
                $stmt = $pdo->prepare("UPDATE students SET status = 'expelled' WHERE id = ?");
                $stmt->execute([$input['student_id']]);
            } elseif ($input['action_type'] === 'death') {
                $stmt = $pdo->prepare("UPDATE students SET status = 'deceased' WHERE id = ?");
                $stmt->execute([$input['student_id']]);
            }
            
            success_response(['record_id' => $record_id], 'Disciplinary record added successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to add disciplinary record: " . $e->getMessage());
            error_response('Failed to add disciplinary record', 500);
        }
        
    } elseif ($type === 'action_type') {
        // Add custom action type
        $required_fields = ['action_name', 'severity'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO disciplinary_action_types (school_id, action_name, description, severity) VALUES (?, ?, ?, ?)");
            $stmt->execute([
                $school_id,
                sanitize_input($input['action_name']),
                sanitize_input($input['description'] ?? ''),
                $input['severity']
            ]);
            
            $type_id = $pdo->lastInsertId();
            
            success_response(['type_id' => $type_id], 'Action type added successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to add action type: " . $e->getMessage());
            error_response('Failed to add action type', 500);
        }
        
    } elseif ($type === 'committee') {
        // Add committee member
        $required_fields = ['user_id', 'user_type', 'role', 'appointed_date'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        try {
            $stmt = $pdo->prepare("INSERT INTO disciplinary_committee (school_id, user_id, user_type, role, appointed_date, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $school_id,
                $input['user_id'],
                $input['user_type'],
                $input['role'],
                $input['appointed_date'],
                sanitize_input($input['notes'] ?? '')
            ]);
            
            $member_id = $pdo->lastInsertId();
            
            success_response(['member_id' => $member_id], 'Committee member added successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to add committee member: " . $e->getMessage());
            error_response('Failed to add committee member', 500);
        }
        
    } elseif ($type === 'update') {
        // Update disciplinary record
        $required_fields = ['id', 'student_id', 'action_type', 'severity', 'title', 'incident_date', 'action_date', 'reported_by', 'handled_by'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        try {
            // Verify record belongs to this school
            $stmt = $pdo->prepare("SELECT id FROM disciplinary_records WHERE id = ? AND school_id = ?");
            $stmt->execute([$input['id'], $school_id]);
            if (!$stmt->fetch()) {
                error_response('Record not found in this school', 404);
            }
            
            // Verify student belongs to this school
            $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
            $stmt->execute([$input['student_id'], $school_id]);
            if (!$stmt->fetch()) {
                error_response('Student not found in this school', 404);
            }
            
            $stmt = $pdo->prepare("UPDATE disciplinary_records SET 
                student_id = ?, action_type = ?, severity = ?, title = ?, description = ?, 
                incident_date = ?, action_date = ?, end_date = ?, reported_by = ?, handled_by = ?, 
                status = ?, notes = ?
                WHERE id = ? AND school_id = ?");
            
            $stmt->execute([
                $input['student_id'],
                $input['action_type'],
                $input['severity'],
                sanitize_input($input['title']),
                sanitize_input($input['description'] ?? ''),
                $input['incident_date'],
                $input['action_date'],
                $input['end_date'] ?? null,
                sanitize_input($input['reported_by']),
                sanitize_input($input['handled_by']),
                $input['status'] ?? 'pending',
                sanitize_input($input['notes'] ?? ''),
                $input['id'],
                $school_id
            ]);
            
            success_response([], 'Disciplinary record updated successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to update disciplinary record: " . $e->getMessage());
            error_response('Failed to update disciplinary record', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Check auth without strict token verification for debugging
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized - No school session found', 401);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || empty($input['id'])) {
        error_response('ID is required');
    }
    
    if ($type === 'record') {
        // Update disciplinary record
        try {
            $update_fields = [];
            $params = [];
            
            if (!empty($input['status'])) {
                $update_fields[] = "status = ?";
                $params[] = $input['status'];
            }
            
            if (!empty($input['notes'])) {
                $update_fields[] = "notes = ?";
                $params[] = sanitize_input($input['notes']);
            }
            
            if (!empty($input['parent_response'])) {
                $update_fields[] = "parent_response = ?";
                $params[] = sanitize_input($input['parent_response']);
            }
            
            if (!empty($input['parent_notified'])) {
                $update_fields[] = "parent_notified = ?";
                $params[] = (bool)$input['parent_notified'];
            }
            
            if (!empty($input['appeal_details'])) {
                $update_fields[] = "appeal_details = ?";
                $params[] = sanitize_input($input['appeal_details']);
            }
            
            if (!empty($input['appeal_status'])) {
                $update_fields[] = "appeal_status = ?";
                $params[] = $input['appeal_status'];
            }
            
            if (empty($update_fields)) {
                error_response('No fields to update');
            }
            
            $params[] = $input['id'];
            
            $query = "UPDATE disciplinary_records SET " . implode(', ', $update_fields) . " WHERE id = ? AND school_id = ?";
            $params[] = $school_id;
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            success_response([], 'Disciplinary record updated successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to update disciplinary record: " . $e->getMessage());
            error_response('Failed to update disciplinary record', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check auth without strict token verification for debugging
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['school_id'])) {
        error_response('Unauthorized - No school session found', 401);
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        error_response('ID is required');
    }
    
    if ($type === 'record') {
        // Delete disciplinary record
        try {
            $stmt = $pdo->prepare("DELETE FROM disciplinary_records WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            
            success_response([], 'Disciplinary record deleted successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to delete disciplinary record: " . $e->getMessage());
            error_response('Failed to delete disciplinary record', 500);
        }
        
    } elseif ($type === 'committee') {
        // Remove committee member
        try {
            $stmt = $pdo->prepare("UPDATE disciplinary_committee SET is_active = 0 WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            
            success_response([], 'Committee member removed successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to remove committee member: " . $e->getMessage());
            error_response('Failed to remove committee member', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
