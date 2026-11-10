<?php
// Parent Attendance API
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

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];
$selected_child_id = $_GET['child_id'] ?? null;
$selected_term = $_GET['term'] ?? null;
$selected_year = $_GET['year'] ?? date('Y');

// Load calendar helpers
require_once __DIR__ . '/../../includes/calendar_helpers.php';
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);
$active_term = $calendar_status['current_term']['term_name'] ?? null;

try {
    // Get terms for year
    $terms = [];
    $stmt = $pdo->prepare("SELECT term_name, year, start_date, end_date FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $selected_year]);
    $term_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($term_records as $tr) {
        $terms[] = $tr['term_name'];
    }
    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }
    if (!$selected_term) {
        $selected_term = $active_term ?? $terms[0];
    }

    // Get years list
    $years = [];
    $stmt = $pdo->prepare("SELECT DISTINCT year FROM terms WHERE school_id = ? ORDER BY year DESC");
    $stmt->execute([$school_id]);
    $year_records = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $years = $year_records ?: [date('Y')];

    // Get children of this parent
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!$selected_child_id && !empty($children)) {
        $selected_child_id = $children[0]['id'];
    }
    
    $attendance_data = [
        'records' => [],
        'total_days' => 0,
        'present_count' => 0,
        'absent_count' => 0,
        'late_count' => 0,
        'excused_count' => 0,
        'attendance_percentage' => 0
    ];

    if ($selected_child_id) {
        // Find term date range
        $start_date = null;
        $end_date = null;
        foreach ($term_records as $tr) {
            if ($tr['term_name'] === $selected_term) {
                $start_date = $tr['start_date'];
                $end_date = $tr['end_date'];
                break;
            }
        }

        if ($start_date && $end_date) {
            $stmt = $pdo->prepare("SELECT a.*, s.admission_number, s.first_name, s.last_name
                                   FROM attendance a
                                   JOIN students s ON a.student_id = s.id
                                   WHERE a.student_id = ? AND a.date BETWEEN ? AND ?
                                   ORDER BY a.date DESC");
            $stmt->execute([$selected_child_id, $start_date, $end_date]);
        } else {
            $stmt = $pdo->prepare("SELECT a.*, s.admission_number, s.first_name, s.last_name
                                   FROM attendance a
                                   JOIN students s ON a.student_id = s.id
                                   WHERE a.student_id = ? AND YEAR(a.date) = ?
                                   ORDER BY a.date DESC");
            $stmt->execute([$selected_child_id, $selected_year]);
        }
        $attendance_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $total_days = count($attendance_records);
        $present_count = 0;
        $absent_count = 0;
        $late_count = 0;
        $excused_count = 0;
        
        foreach ($attendance_records as $record) {
            switch(strtolower($record['status'])) {
                case 'present': $present_count++; break;
                case 'absent': $absent_count++; break;
                case 'late': $late_count++; break;
                case 'excused': $excused_count++; break;
            }
        }
        
        $attendance_data = [
            'records' => $attendance_records,
            'total_days' => $total_days,
            'present_count' => $present_count,
            'absent_count' => $absent_count,
            'late_count' => $late_count,
            'excused_count' => $excused_count,
            'attendance_percentage' => $total_days > 0 ? round(($present_count / $total_days) * 100, 1) : 0
        ];
    }

    echo json_encode([
        'success' => true,
        'children' => array_map(function($child) {
            return [
                'id' => $child['id'],
                'first_name' => $child['first_name'],
                'last_name' => $child['last_name'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class_name' => $child['class_name'] ?? 'Not assigned',
                'stream_name' => $child['stream_name'] ?? 'Not assigned'
            ];
        }, $children),
        'selected_child_id' => $selected_child_id,
        'terms' => $terms,
        'years' => array_map('intval', $years),
        'current_term' => $selected_term,
        'current_year' => intval($selected_year),
        'attendance_data' => $attendance_data
    ]);

} catch (PDOException $e) {
    error_log("Parent attendance API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
} catch (Exception $e) {
    error_log("Parent attendance API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}