<?php
// Debug script to test attendance API response
session_start();

// Simulate teacher session
$_SESSION['teacher_id'] = 1;
$_SESSION['school_id'] = 1;
$_SESSION['teacher_name'] = 'Test Teacher';

// Simulate GET request with date parameters
$_GET['start_date'] = '2026-07-01';
$_GET['end_date'] = '2026-07-31';

// Set request method
$_SERVER['REQUEST_METHOD'] = 'GET';

require_once __DIR__ . '/../../config.php';

// Copy the relevant code from attendance.php
$teacher_id = $_SESSION['teacher_id'];
$school_id = $_SESSION['school_id'];

// Get teacher details
$teacher = null;
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
}

echo "<h1>Teacher Info</h1>";
echo "<pre>";
print_r($teacher);
echo "</pre>";

$teacher_type = $teacher['teacher_type'];
$class_id = $teacher['class_id'];

echo "<h2>Teacher Type: $teacher_type</h2>";
echo "<h2>Class ID: $class_id</h2>";

// Get attendance statistics for the current month or custom date range
$attendance_stats = [];
$monthly_summary = null;
$student_attendance_details = [];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

echo "<h2>Date Range: $start_date to $end_date</h2>";

try {
    if ($teacher_type === 'class_teacher' && $class_id) {
        echo "<h3>Class Teacher Query</h3>";
        
        // Get stats for class teacher's class
        $stmt = $pdo->prepare("SELECT a.date, a.status, COUNT(*) as count
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id = ? AND a.date BETWEEN ? AND ?
                              GROUP BY a.date, a.status
                              ORDER BY a.date DESC");
        $stmt->execute([$class_id, $start_date, $end_date]);
        $attendance_stats = $stmt->fetchAll();
        
        echo "<h4>Daily Stats</h4>";
        echo "<pre>";
        print_r($attendance_stats);
        echo "</pre>";
        
        // Calculate overall statistics
        $stmt = $pdo->prepare("SELECT 
                              COUNT(CASE WHEN a.status = 'present' THEN 1 END) as total_present,
                              COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as total_absent,
                              COUNT(CASE WHEN a.status = 'late' THEN 1 END) as total_late,
                              COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as total_excused,
                              COUNT(*) as total_records,
                              COUNT(DISTINCT a.date) as days_recorded
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id = ? AND a.date BETWEEN ? AND ?");
        $stmt->execute([$class_id, $start_date, $end_date]);
        $monthly_summary = $stmt->fetch();
        
        echo "<h4>Monthly Summary</h4>";
        echo "<pre>";
        print_r($monthly_summary);
        echo "</pre>";
        
        // Get student attendance details
        echo "<h4>Student Attendance Details Query</h4>";
        $stmt = $pdo->prepare("SELECT s.id, s.admission_number, s.first_name, s.last_name,
                              GROUP_CONCAT(
                                CONCAT(a.date, ':', COALESCE(a.status, ''))
                                ORDER BY a.date
                                SEPARATOR '|'
                              ) as attendance_records
                              FROM students s
                              LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
                              WHERE s.class_id = ? AND s.status = 'active'
                              GROUP BY s.id
                              ORDER BY s.admission_number");
        $stmt->execute([$start_date, $end_date, $class_id]);
        $student_attendance_details = $stmt->fetchAll();
        
        echo "<pre>";
        print_r($student_attendance_details);
        echo "</pre>";
    }
} catch (PDOException $e) {
    error_log("Failed to fetch attendance statistics: " . $e->getMessage());
    echo "<h3>Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}

// Build the JSON response that would be sent to the app
echo "<h2>JSON Response that would be sent to App</h2>";
echo "<pre>";
echo json_encode([
    'success' => true,
    'teacher' => [
        'id' => $teacher['id'],
        'first_name' => $teacher['first_name'],
        'last_name' => $teacher['last_name'],
        'email' => $teacher['email'],
        'school_name' => $teacher['school_name'],
        'teacher_type' => $teacher['teacher_type'],
        'class_id' => $teacher['class_id']
    ],
    'attendance_stats' => $attendance_stats,
    'monthly_summary' => $monthly_summary,
    'student_attendance_details' => $student_attendance_details
], JSON_PRETTY_PRINT);
echo "</pre>";
?>
