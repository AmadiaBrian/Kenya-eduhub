<?php
// Parent Performance API
// Disable error reporting for API responses
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

// Check authentication
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

try {
    // Get current year and term
    $current_year = date('Y');
    
    // Get available years from terms table
    $years = [];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT year FROM terms WHERE school_id = ? ORDER BY year DESC");
        $stmt->execute([$school_id]);
        $year_records = $stmt->fetchAll();
        foreach ($year_records as $year) {
            $years[] = $year['year'];
        }
    } catch (PDOException $e) {
        $years = [$current_year];
    }
    
    if (empty($years)) {
        $years = [$current_year];
    }
    
    // Get terms from database for current year
    $terms = [];
    try {
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
        $stmt->execute([$school_id, $current_year]);
        $term_records = $stmt->fetchAll();
        foreach ($term_records as $term) {
            $terms[] = $term['term_name'];
        }
    } catch (PDOException $e) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }
    
    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }
    
    // Get active term from database
    $active_term = null;
    try {
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? AND is_active = 1");
        $stmt->execute([$school_id, $current_year]);
        $term = $stmt->fetch();
        if ($term) {
            $active_term = $term['term_name'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch active term: " . $e->getMessage());
    }
    
    // Use active term if available, otherwise use first term
    $current_term = $active_term ?? ($terms[0] ?? 'Term 1');
    
    // Get children of this parent
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get student subject assignments for filtering
    $student_subject_assignments = [];
    try {
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.school_id = ? AND st.status = 'active'");
        $stmt->execute([$school_id]);
        $assignments = $stmt->fetchAll();

        // Group by admission_number for easier matching
        foreach ($assignments as $assignment) {
            $admission_number = $assignment['admission_number'];
            $subject_name = $assignment['subject_name'];
            $is_compulsory = $assignment['is_compulsory'] ?? 0;
            if (!isset($student_subject_assignments[$admission_number])) {
                $student_subject_assignments[$admission_number] = [];
            }
            $student_subject_assignments[$admission_number][] = [
                'subject_name' => $subject_name,
                'is_compulsory' => $is_compulsory
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch student subject assignments: " . $e->getMessage());
    }
    
    // Get performance data for all children
    $performance_data = [];
    
    foreach ($children as $child) {
        $stmt = $pdo->prepare("SELECT ap.*, et.exam_type_name, et.exam_type_code,
                                            (SELECT gs.points FROM grading_scales gs
                                            WHERE gs.school_id = ?
                                            AND ap.marks BETWEEN gs.min_score AND gs.max_score
                                            AND UPPER(ap.grade) = UPPER(gs.grade_name)
                                            LIMIT 1) as grade_points
                               FROM academic_performance ap
                               LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                               WHERE ap.student_id = ?
                               ORDER BY ap.created_at DESC");
        $stmt->execute([$school_id, $child['id']]);
        $child_performance = $stmt->fetchAll();
        
        // Get assigned subjects for this child
        $child_admission = $child['admission_number'];
        $assigned_subjects = $student_subject_assignments[$child_admission] ?? [];
        $assigned_subject_names = array_column($assigned_subjects, 'subject_name');
        
        // Filter performance to only include assigned subjects
        $filtered_performance = array_filter($child_performance, function($perf) use ($assigned_subject_names) {
            return in_array($perf['subject'], $assigned_subject_names);
        });
        
        if (!empty($filtered_performance)) {
            // Group performance by exam type
            $grouped_by_exam_type = [];
            foreach ($filtered_performance as $perf) {
                $exam_type = $perf['exam_type_name'] ?? 'Regular';
                if (!isset($grouped_by_exam_type[$exam_type])) {
                    $grouped_by_exam_type[$exam_type] = [];
                }
                $grouped_by_exam_type[$exam_type][] = [
                    'id' => $perf['id'],
                    'subject' => $perf['subject'] ?? 'Unknown',
                    'marks' => $perf['marks'],
                    'grade' => $perf['grade'],
                    'grade_points' => $perf['grade_points'],
                    'exam_type' => $exam_type,
                    'remarks' => $perf['remarks'] ?? null,
                    'term' => $perf['term'],
                    'year' => $perf['year'],
                    'created_at' => $perf['created_at']
                ];
            }
            
            $performance_data[$child['id']] = [
                'child' => [
                    'id' => $child['id'],
                    'name' => $child['first_name'] . ' ' . $child['last_name'],
                    'admission_number' => $child['admission_number'],
                    'class' => $child['class_name'] ?? 'Not assigned',
                    'stream' => $child['stream_name'] ?? 'Not assigned'
                ],
                'performance_by_exam_type' => $grouped_by_exam_type
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'children' => array_map(function($child) {
            return [
                'id' => $child['id'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class' => $child['class_name'] ?? 'Not assigned',
                'stream' => $child['stream_name'] ?? 'Not assigned'
            ];
        }, $children),
        'performance_data' => $performance_data,
        'current_term' => $current_term,
        'terms' => $terms,
        'years' => $years,
        'current_year' => $current_year
    ]);
    
} catch (PDOException $e) {
    error_log("Performance API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
