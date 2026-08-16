<?php
// Parent Results API
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
    
    // Get current term based on actual date range from database (not is_active flag)
    $today = date('Y-m-d');
    $active_term = null;
    try {
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ? ORDER BY year DESC, term_number ASC LIMIT 1");
        $stmt->execute([$school_id, $today, $today]);
        $term = $stmt->fetch();
        if ($term) {
            $active_term = $term['term_name'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch current term: " . $e->getMessage());
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
    
    // Get all grading scales for this school
    $grading_scales = [];
    try {
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales: " . $e->getMessage());
    }
    
    // Get aggregate points distribution
    $aggregate_distribution = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch aggregate distribution: " . $e->getMessage());
    }
    
    // Get student subject assignments for filtering total points calculation
    $student_subject_assignments = [];
    try {
        // Get the parent's children IDs
        $child_ids = array_column($children, 'id');
        $child_ids_placeholder = implode(',', array_fill(0, count($child_ids), '?'));
        
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.school_id = ? AND st.status = 'active' AND ss.student_id IN ($child_ids_placeholder)");
        $params = array_merge([$school_id], $child_ids);
        $stmt->execute($params);
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
    
    // Get class-wide performance data for rankings using the same query as schools/api/performance.php
    $class_performance_data = [];
    
    // Get performance scope from request (default to 'stream')
    $performance_scope = $_GET['scope'] ?? 'stream'; // 'stream' or 'class'
    
    if (!empty($children)) {
        // Get first child's class/stream for filtering
        $first_child = $children[0];
        $target_class = $first_child['class_name'] ?? null;
        $target_stream = $first_child['stream_name'] ?? null;
        
        // Use the same comprehensive query as schools/api/performance.php
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
        
        // Filter by class based on scope
        if ($target_class) {
            $query .= " AND c.class_name = ?";
            $params[] = $target_class;
        }
        
        // Filter by stream only if scope is 'stream'
        if ($performance_scope === 'stream' && $target_stream) {
            $query .= " AND st.stream_name = ?";
            $params[] = $target_stream;
        }
        
        $query .= " ORDER BY ap.year DESC, ap.term DESC, s.admission_number";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $all_performance_records = $stmt->fetchAll();
        
        // Group by student
        foreach ($all_performance_records as $record) {
            $student_id = $record['student_id'];
            
            if (!isset($class_performance_data[$student_id])) {
                $class_performance_data[$student_id] = [
                    'student' => [
                        'id' => $record['student_id'],
                        'name' => $record['first_name'] . ' ' . $record['last_name'],
                        'admission_number' => $record['admission_number'],
                        'class' => $record['class_name'] ?? 'Not assigned',
                        'stream' => $record['stream_name'] ?? 'Not assigned'
                    ],
                    'performance' => []
                ];
            }
            
            $class_performance_data[$student_id]['performance'][] = [
                'id' => $record['id'],
                'subject' => $record['subject'] ?? 'Unknown',
                'marks' => $record['marks'],
                'grade' => $record['grade'],
                'grade_points' => $record['grade_points'],
                'exam_type' => $record['exam_type_name'] ?? 'Regular',
                'remarks' => $record['remarks'] ?? null,
                'term' => $record['term'],
                'year' => $record['year'],
                'created_at' => $record['created_at']
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
        'class_performance_data' => $class_performance_data,
        'current_term' => $current_term,
        'terms' => $terms,
        'current_year' => $current_year,
        'school_settings' => [
            'min_subjects' => $school_min_subjects,
            'max_subjects' => $school_max_subjects
        ],
        'grading_scales' => $grading_scales,
        'aggregate_distribution' => $aggregate_distribution,
        'student_subject_assignments' => $student_subject_assignments
    ]);
    
} catch (PDOException $e) {
    error_log("Results API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
