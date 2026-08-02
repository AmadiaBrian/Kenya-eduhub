<?php
// Performance API for Examiners
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check examiner authentication
if (!isset($_SESSION['examiner_id']) || !isset($_SESSION['examiner_token'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['examiner_school_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get performance records
    $student_id = $_GET['student_id'] ?? null;
    $term = $_GET['term'] ?? null;
    $year = $_GET['year'] ?? null;
    $subject = $_GET['subject'] ?? null;
    $class_id = $_GET['class_id'] ?? null;

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
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $performance]);
    } catch (PDOException $e) {
        error_log("Performance fetch error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch performance: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>
