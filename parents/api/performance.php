<?php
// Parent Performance API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

// Load calendar helpers
require_once __DIR__ . '/../../includes/calendar_helpers.php';

// Get calendar status to find active term
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);
$active_term = $calendar_status['current_term']['term_name'] ?? null;

// Get terms from database for current year
$terms = [];
try {
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $term_records = $stmt->fetchAll();
    foreach ($term_records as $term) {
        $terms[] = $term['term_name'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

// Use active term if available, otherwise use first term
$default_term = $active_term ?? ($terms[0] ?? 'Term 1');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $child_id = $_GET['child_id'] ?? null;
        $term = $_GET['term'] ?? $default_term;
        $year = $_GET['year'] ?? date('Y');
        
        if (!$child_id) {
            echo json_encode(['success' => false, 'error' => 'Child ID is required']);
            exit;
        }
        
        // Verify this child belongs to the parent
        $stmt = $pdo->prepare("SELECT id FROM students WHERE id = ? AND parent_id = ?");
        $stmt->execute([$child_id, $parent_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized access to this child\'s data']);
            exit;
        }
        
        // Get performance records for the child
        $stmt = $pdo->prepare("SELECT ap.*, s.subject_name, c.class_name, st.stream_name
                               FROM academic_performance ap
                               JOIN subjects s ON ap.subject_id = s.id
                               LEFT JOIN classes c ON ap.class_id = c.id
                               LEFT JOIN streams st ON ap.stream_id = st.id
                               WHERE ap.student_id = ? AND ap.term = ? AND ap.year = ?
                               ORDER BY ap.created_at DESC");
        $stmt->execute([$child_id, $term, $year]);
        $performance = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'data' => $performance]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    error_log("Parent performance API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
