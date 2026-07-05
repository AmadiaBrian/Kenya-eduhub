<?php
// Attendance API
session_start();
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check authentication
$school_id = null;
if (isset($_SESSION['school_id'])) {
    $school_id = $_SESSION['school_id'];
} elseif (isset($_SESSION['teacher_id'])) {
    // Get school_id from teacher
    try {
        $stmt = $pdo->prepare("SELECT school_id FROM teachers WHERE id = ?");
        $stmt->execute([$_SESSION['teacher_id']]);
        $teacher = $stmt->fetch();
        if ($teacher) {
            $school_id = $teacher['school_id'];
        }
    } catch (PDOException $e) {
        error_log("Failed to get teacher school: " . $e->getMessage());
    }
}

if (!$school_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - No school ID found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save attendance
    $data = json_decode(file_get_contents('php://input'), true);
    
    error_log("Attendance save request received: " . print_r($data, true));
    
    if (!isset($data['attendance']) || !is_array($data['attendance'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid data format']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $saved_count = 0;
        
        foreach ($data['attendance'] as $record) {
            $student_id = $record['student_id'] ?? null;
            $date = $record['date'] ?? null;
            $status = $record['status'] ?? null;
            $remarks = $record['remarks'] ?? '';
            
            error_log("Processing record: student_id=$student_id, date=$date, status=$status");
            
            if (!$student_id || !$date || !$status) {
                error_log("Skipping incomplete record");
                continue;
            }
            
            // Check if student belongs to this school
            $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND school_id = ?");
            $stmt->execute([$student_id, $school_id]);
            if (!$stmt->fetch()) {
                error_log("Student $student_id does not belong to school $school_id");
                continue;
            }
            
            // Check if attendance already exists for this student on this date
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE student_id = ? AND date = ?");
            $stmt->execute([$student_id, $date]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $stmt = $pdo->prepare("UPDATE attendance SET status = ?, remarks = ? WHERE id = ?");
                $stmt->execute([$status, $remarks, $existing['id']]);
                error_log("Updated attendance record ID: " . $existing['id']);
            } else {
                // Insert new record
                $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, remarks) VALUES (?, ?, ?, ?)");
                $stmt->execute([$student_id, $date, $status, $remarks]);
                error_log("Inserted new attendance record for student $student_id");
            }
            $saved_count++;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Attendance saved successfully', 'count' => $saved_count]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Attendance save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to save attendance: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get attendance records
    $student_id = $_GET['student_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $class_id = $_GET['class_id'] ?? null;
    
    error_log("Attendance GET request: student_id=$student_id, date=$date, class_id=$class_id, school_id=$school_id");
    
    try {
        $query = "SELECT a.*, s.admission_number, s.first_name, s.last_name, c.class_name, st.stream_name 
                  FROM attendance a 
                  JOIN students s ON a.student_id = s.id 
                  LEFT JOIN classes c ON s.class_id = c.id 
                  LEFT JOIN streams st ON s.stream_id = st.id 
                  WHERE s.school_id = ?";
        $params = [$school_id];
        
        if ($student_id) {
            $query .= " AND a.student_id = ?";
            $params[] = $student_id;
        }
        
        if ($date) {
            $query .= " AND a.date = ?";
            $params[] = $date;
        }
        
        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
        }
        
        $query .= " ORDER BY a.date DESC, s.admission_number";
        
        error_log("Executing query: " . $query);
        error_log("Params: " . print_r($params, true));
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($attendance) . " attendance records");
        
        echo json_encode(['success' => true, 'data' => $attendance]);
    } catch (PDOException $e) {
        error_log("Attendance fetch error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch attendance: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
