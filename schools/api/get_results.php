<?php
// API to get student results for school
session_start();
require_once '../../config.php';

header('Content-Type: application/json');

// Check if school is logged in
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$school_id = $_SESSION['school_id'];

// Get school subject limits
$school_min_subjects = 7;
$school_max_subjects = 8;
try {
    $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school_limits = $stmt->fetch();
    $school_min_subjects = $school_limits['min_subjects'] ?? 7;
    $school_max_subjects = $school_limits['max_subjects'] ?? 8;
} catch (PDOException $e) {
    error_log("Failed to fetch school subject limits: " . $e->getMessage());
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$class_id = $input['class_id'] ?? null;
$stream_id = $input['stream_id'] ?? null;
$term = $input['term'] ?? null;
$exam_type_id = $input['exam_type'] ?? null;
$year = $input['year'] ?? date('Y');

try {
    // Use academic_performance table like teachers results page does
    $sql = "SELECT ap.*, s.admission_number, s.first_name, s.last_name, c.class_name, st.stream_name,
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

    if ($class_id) {
        $sql .= " AND s.class_id = ?";
        $params[] = $class_id;
    }

    if ($stream_id) {
        $sql .= " AND s.stream_id = ?";
        $params[] = $stream_id;
    }

    if ($term) {
        $sql .= " AND ap.term = ?";
        $params[] = $term;
    }

    if ($year) {
        $sql .= " AND ap.year = ?";
        $params[] = $year;
    }

    if ($exam_type_id) {
        $sql .= " AND ap.exam_type_id = ?";
        $params[] = $exam_type_id;
    }

    $sql .= " ORDER BY ap.year DESC, ap.term DESC, s.admission_number";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $performance = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("Query: " . $sql);
    error_log("Params: " . json_encode($params));
    error_log("Results count: " . count($performance));

    if (empty($performance)) {
        echo json_encode(['success' => true, 'results' => [], 'message' => 'No results found for selected filters']);
        exit();
    }

    // Get all student subject assignments first
    $student_assignments = [];
    $stmt = $pdo->prepare("SELECT ss.student_id, s.subject_name
                          FROM student_subjects ss
                          JOIN subjects s ON ss.subject_id = s.id
                          WHERE s.school_id = ?");
    $stmt->execute([$school_id]);
    $assignments = $stmt->fetchAll();
    foreach ($assignments as $assignment) {
        $student_id = $assignment['student_id'];
        $subject_name = $assignment['subject_name'];
        if (!isset($student_assignments[$student_id])) {
            $student_assignments[$student_id] = [];
        }
        $student_assignments[$student_id][] = $subject_name;
    }

    // Pivot results to have subjects as columns
    $pivoted = [];
    foreach ($performance as $row) {
        $student_id = $row['student_id'];
        $subject_name = $row['subject'];

        // Only include if student is assigned to this subject
        if (!isset($student_assignments[$student_id]) || !in_array($subject_name, $student_assignments[$student_id])) {
            continue; // Skip this record - student not assigned to subject
        }

        $key = $row['student_id'] . '_' . $row['term'] . '_' . $row['year'] . '_' . ($row['exam_type_id'] ?? '');

        if (!isset($pivoted[$key])) {
            $pivoted[$key] = [
                'student_id' => $row['student_id'],
                'admission_number' => $row['admission_number'],
                'student_name' => $row['first_name'] . ' ' . ($row['last_name'] ?? ''),
                'class_name' => $row['class_name'],
                'stream_name' => $row['stream_name'],
                'term' => $row['term'],
                'exam_type' => $row['exam_type_name'],
                'year' => $row['year'],
                'total_marks' => 0,
                'total_points' => 0,
                'grade' => '-'
            ];
        }

        // Add subject mark
        $pivoted[$key][$subject_name] = $row['marks'];
        $pivoted[$key]['total_marks'] += $row['marks'];
    }

    // Now calculate grading_subjects for each student BEFORE returning
    foreach ($pivoted as &$student) {
        // Get student subject assignments
        $assigned_subjects = [];
        $stmt = $pdo->prepare("SELECT s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.student_id = ? AND s.school_id = ?");
        $stmt->execute([$student['student_id'], $school_id]);
        $assignments = $stmt->fetchAll();

        foreach ($assignments as $assignment) {
            $assigned_subjects[] = $assignment['subject_name'];
        }

        // Get grading scales for this school
        $grading_scales = [];
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();

        // Get subjects that have grading scales defined
        $graded_subjects = [];
        foreach ($grading_scales as $scale) {
            if ($scale['subject_name']) {
                $graded_subjects[] = $scale['subject_name'];
            }
        }

        // Calculate points based on assigned subjects that have grading scales
        $total_points = 0;
        $grading_subjects = [];
        $subject_points = [];

        foreach ($student as $key => $value) {
            if (!in_array($key, ['student_id', 'admission_number', 'student_name', 'class_name', 'stream_name', 'term', 'exam_type', 'year', 'total_marks', 'total_points', 'grade'])) {
                // Only include if subject is assigned to student AND has grading scale
                if (in_array($key, $assigned_subjects) && in_array($key, $graded_subjects)) {
                    $mark = floatval($value);
                    $points = calculatePoints($mark);
                    $subject_points[$key] = $points;
                }
            }
        }

        // Sort subjects by points (highest first) and take only up to max_subjects
        arsort($subject_points);
        $count = 0;
        foreach ($subject_points as $subject => $points) {
            if ($count < $school_max_subjects) {
                $total_points += $points;
                $grading_subjects[] = $subject;
                $count++;
            }
        }

        $student['total_points'] = $total_points;
        $student['grading_subjects'] = $grading_subjects; // Track which subjects were used for grading

        // Calculate grade from aggregate points distribution
        $stmt = $pdo->prepare("SELECT grade_name FROM aggregate_points_distribution 
                              WHERE school_id = ? AND ? >= min_points AND ? <= max_points 
                              ORDER BY min_points DESC LIMIT 1");
        $stmt->execute([$school_id, $total_points, $total_points]);
        $grade_result = $stmt->fetch();

        $student['grade'] = $grade_result ? $grade_result['grade_name'] : '-';
    }

    echo json_encode(['success' => true, 'results' => array_values($pivoted)]);

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function calculatePoints($mark) {
    if ($mark >= 80) return 12;
    if ($mark >= 75) return 11;
    if ($mark >= 70) return 10;
    if ($mark >= 65) return 9;
    if ($mark >= 60) return 8;
    if ($mark >= 55) return 7;
    if ($mark >= 50) return 6;
    if ($mark >= 45) return 5;
    if ($mark >= 40) return 4;
    if ($mark >= 35) return 3;
    if ($mark >= 30) return 2;
    if ($mark >= 25) return 1;
    return 0;
}
?>
