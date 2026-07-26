<?php
// Performance API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

header('Content-Type: application/json');

// Check authentication
$school_id = null;
$teacher_id = null;
$parent_id = null;

if (isset($_SESSION['school_id'])) {
    $school_id = $_SESSION['school_id'];
} elseif (isset($_SESSION['teacher_id'])) {
    // Get school_id from teacher
    try {
        $stmt = $pdo->prepare("SELECT school_id, id FROM teachers WHERE id = ?");
        $stmt->execute([$_SESSION['teacher_id']]);
        $teacher = $stmt->fetch();
        if ($teacher) {
            $school_id = $teacher['school_id'];
            $teacher_id = $teacher['id'];
        }
    } catch (PDOException $e) {
        error_log("Failed to get teacher school: " . $e->getMessage());
    }
} elseif (isset($_SESSION['parent_id'])) {
    // Get school_id from parent
    try {
        $stmt = $pdo->prepare("SELECT school_id, id FROM parents WHERE id = ?");
        $stmt->execute([$_SESSION['parent_id']]);
        $parent = $stmt->fetch();
        if ($parent) {
            $school_id = $parent['school_id'];
            $parent_id = $parent['id'];
        }
    } catch (PDOException $e) {
        error_log("Failed to get parent school: " . $e->getMessage());
    }
}

if (!$school_id) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - No school ID found']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save performance
    $data = json_decode(file_get_contents('php://input'), true);
    
    error_log("Performance save request received: " . print_r($data, true));
    
    if (!isset($data['performance']) || !is_array($data['performance'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid data format']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $saved_count = 0;
        
        foreach ($data['performance'] as $record) {
            $student_id = $record['student_id'] ?? null;
            $term = $record['term'] ?? null;
            $year = $record['year'] ?? null;
            $exam_type_id = $record['exam_type_id'] ?? null;
            $subject = strtoupper(trim($record['subject'] ?? '')); // Normalize subject to uppercase
            $marks = $record['marks'] ?? null;
            $grade = $record['grade'] ?? null;
            $remarks = $record['remarks'] ?? '';
            
            error_log("Processing record: student_id=$student_id, term=$term, year=$year, exam_type_id=$exam_type_id, subject=$subject, marks=$marks");
            
            if (!$student_id || !$term || !$year || !$subject || $marks === null) {
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
            
            // Check if performance already exists for this student, term, year, subject, and exam_type_id (case-insensitive)
            $checkQuery = "SELECT id FROM academic_performance WHERE student_id = ? AND term = ? AND year = ? AND UPPER(subject) = UPPER(?)";
            $checkParams = [$student_id, $term, $year, $subject];
            
            if ($exam_type_id) {
                $checkQuery .= " AND exam_type_id = ?";
                $checkParams[] = $exam_type_id;
            }
            
            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute($checkParams);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing record
                $updateQuery = "UPDATE academic_performance SET marks = ?, grade = ?, remarks = ?";
                $updateParams = [$marks, $grade, $remarks];
                
                if ($exam_type_id) {
                    $updateQuery .= ", exam_type_id = ?";
                    $updateParams[] = $exam_type_id;
                }
                
                $updateQuery .= " WHERE id = ?";
                $updateParams[] = $existing['id'];
                
                $stmt = $pdo->prepare($updateQuery);
                $stmt->execute($updateParams);
                error_log("Updated performance record ID: " . $existing['id']);
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO academic_performance (student_id, term, year, subject, marks, grade, remarks";
                $insertValues = "VALUES (?, ?, ?, ?, ?, ?, ?";
                $insertParams = [$student_id, $term, $year, $subject, $marks, $grade, $remarks];
                
                if ($exam_type_id) {
                    $insertQuery .= ", exam_type_id";
                    $insertValues .= ", ?";
                    $insertParams[] = $exam_type_id;
                }
                
                $insertQuery .= ") " . $insertValues . ")";
                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute($insertParams);
                error_log("Inserted new performance record for student $student_id");
            }
            $saved_count++;
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Performance saved successfully', 'count' => $saved_count]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Performance save error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to save performance: ' . $e->getMessage()]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get performance records
    $student_id = $_GET['student_id'] ?? null;
    $term = $_GET['term'] ?? null;
    $year = $_GET['year'] ?? null;
    $subject = $_GET['subject'] ?? null;
    $class_id = $_GET['class_id'] ?? null;

    error_log("Performance GET request: student_id=$student_id, term=$term, year=$year, subject=$subject, class_id=$class_id, school_id=$school_id, teacher_id=$teacher_id");

    try {
        $query = "SELECT ap.*, s.admission_number, s.first_name, s.last_name, c.class_name, st.stream_name,
                         et.exam_type_name, et.exam_type_code,
                         (SELECT gs.points FROM grading_scales gs
                          WHERE gs.school_id = ?
                          AND ap.marks BETWEEN gs.min_score AND gs.max_score
                          AND UPPER(ap.grade) = UPPER(gs.grade_name)
                          LIMIT 1) as grade_points
                  FROM academic_performance ap
                  JOIN students s ON ap.student_id = s.id
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                  WHERE s.school_id = ?";
        $params = [$school_id, $school_id];

        // Filter by teacher if teacher is logged in
        if ($teacher_id) {
            $query .= " AND (
                s.class_id IN (SELECT class_id FROM teacher_subjects WHERE teacher_id = ?)
                OR s.stream_id IN (SELECT stream_id FROM teachers WHERE id = ?)
            )";
            $params[] = $teacher_id;
            $params[] = $teacher_id;
        }

        if ($student_id) {
            $query .= " AND ap.student_id = ?";
            $params[] = $student_id;
        }

        if ($term) {
            $query .= " AND ap.term = ?";
            $params[] = $term;
        }

        if ($year) {
            $query .= " AND ap.year = ?";
            $params[] = $year;
        }

        if ($subject) {
            $query .= " AND ap.subject = ?";
            $params[] = $subject;
        }

        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
        }
        
        $query .= " ORDER BY ap.year DESC, ap.term DESC, s.admission_number";
        
        error_log("Executing query: " . $query);
        error_log("Params: " . print_r($params, true));
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($performance) . " performance records");
        
        echo json_encode(['success' => true, 'data' => $performance]);
    } catch (PDOException $e) {
        error_log("Performance fetch error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch performance: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
