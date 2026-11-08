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

// Check authentication - support session, GET, or POST parent_id
$parent_id = $_SESSION['parent_id'] ?? $_GET['parent_id'] ?? $_POST['parent_id'] ?? null;

if (!$parent_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get school_id if not in session
if (!isset($_SESSION['school_id']) || empty($_SESSION['school_id'])) {
    $stmt = $pdo->prepare("SELECT school_id FROM parents WHERE id = ?");
    $stmt->execute([$parent_id]);
    $p = $stmt->fetch();
    $school_id = $p['school_id'] ?? 0;
} else {
    $school_id = $_SESSION['school_id'];
}

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
    
    // Get current term based on actual date range from database
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
    
    // Get children of this parent (removed strict status check)
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ?
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get filter parameters from request
    $filter_term = $_GET['term'] ?? $current_term;
    $filter_year = $_GET['year'] ?? $current_year;
    $filter_child_id = $_GET['child_id'] ?? '';
    $filter_exam_type = $_GET['exam_type'] ?? '';

    // Get performance data for all children
    $performance_data = [];
    
    foreach ($children as $child) {
        if ($filter_child_id && $child['id'] != $filter_child_id) {
            continue;
        }

        $query = "SELECT ap.*, et.exam_type_name, et.exam_type_code,
                            (SELECT gs.points FROM grading_scales gs
                            WHERE gs.school_id = ?
                            AND ap.marks BETWEEN gs.min_score AND gs.max_score
                            AND UPPER(ap.grade) = UPPER(gs.grade_name)
                            LIMIT 1) as grade_points
                   FROM academic_performance ap
                   LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                   WHERE ap.student_id = ?";
        
        $params = [$school_id, $child['id']];

        if ($filter_term) {
            $query .= " AND ap.term = ?";
            $params[] = $filter_term;
        }

        if ($filter_year) {
            $query .= " AND ap.year = ?";
            $params[] = $filter_year;
        }

        if ($filter_exam_type) {
            $query .= " AND ap.exam_type_id = ?";
            $params[] = $filter_exam_type;
        }
        
        $query .= " ORDER BY ap.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $child_performance = $stmt->fetchAll();
        
        // Use all performance records directly without filtering out unassigned subjects
        $filtered_performance = $child_performance;
        
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