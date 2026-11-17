<?php
// Teachers Attendance API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/auth_helper.php';

$session = verifyTeacherSession($pdo);
$teacher_id = $session['teacher_id'];
$school_id = $session['school_id'];

$teacher_name = trim(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? ''));
$class_id = $session['class_id'] ?? null;
$teacher_type = $session['teacher_type'] ?? 'class_teacher';

// Get class name
$class_name = null;
if ($class_id) {
    try {
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $cls = $stmt->fetch(PDO::FETCH_ASSOC);
        $class_name = $cls['class_name'] ?? null;
    } catch (PDOException $e) {}
}

// Get calendar status
$calendar_status = [
    'is_holiday' => false,
    'school_status' => 'in_session',
    'current_term' => null,
    'current_year' => date('Y')
];

try {
    $current_date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
    $stmt->execute([$school_id, $current_date, $current_date]);
    $holiday = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($holiday) {
        $calendar_status['is_holiday'] = true;
        $calendar_status['current_holiday'] = [
            'holiday_name' => $holiday['holiday_name'],
            'start_date' => $holiday['start_date'],
            'end_date' => $holiday['end_date']
        ];
    } else {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
        $stmt->execute([$school_id, $current_date, $current_date]);
        $term = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($term) {
            $calendar_status['school_status'] = 'in_session';
            $calendar_status['current_term'] = [
                'term_name' => $term['term_name'],
                'start_date' => $term['start_date'],
                'end_date' => $term['end_date']
            ];
        } else {
            $calendar_status['school_status'] = 'break';
        }
    }
} catch (PDOException $e) {
    error_log("Failed to fetch calendar status: " . $e->getMessage());
}

// Get subject assignments for subject teachers
$subject_assignments = [];
if ($teacher_type === 'subject_teacher') {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, c.class_name, sub.subject_name
                             FROM teacher_subjects ts
                             JOIN classes c ON ts.class_id = c.id
                             JOIN subjects sub ON ts.subject_id = sub.id
                             WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $subject_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Get available classes for the teacher
$classes = [];
try {
    if ($teacher_type === 'class_teacher' && $class_id) {
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } elseif ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        $class_ids = array_unique(array_filter(array_column($subject_assignments, 'class_id')));
        if (!empty($class_ids)) {
            $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id IN ($placeholders)");
            $stmt->execute($class_ids);
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (empty($classes)) {
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
        $stmt->execute([$school_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_students') {
        $target_class_id = intval($_GET['class_id'] ?? $class_id ?? 0);
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (!$target_class_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Class ID is required']);
            exit;
        }
        
        try {
            // Get students
            $stmt = $pdo->prepare("SELECT s.id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name
                                  FROM students s
                                  LEFT JOIN streams st ON s.stream_id = st.id
                                  WHERE s.class_id = ? AND s.status = 'active'
                                  ORDER BY s.admission_number");
            $stmt->execute([$target_class_id]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get existing attendance for this date
            $attendance_records = [];
            if (!empty($students)) {
                $student_ids = array_column($students, 'id');
                $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';
                $params = array_merge($student_ids, [$date]);

                $stmt = $pdo->prepare("SELECT student_id, status, remarks
                                      FROM attendance
                                      WHERE student_id IN ($placeholders) AND date = ?");
                $stmt->execute($params);
                $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($records as $record) {
                    $attendance_records[$record['student_id']] = $record;
                }
            }
            
            // Combine student data with attendance
            $students_with_attendance = array_map(function($student) use ($attendance_records) {
                $student_id = $student['id'];
                if (isset($attendance_records[$student_id])) {
                    $student['status'] = $attendance_records[$student_id]['status'];
                    $student['remarks'] = $attendance_records[$student_id]['remarks'];
                } else {
                    $student['status'] = null;
                    $student['remarks'] = null;
                }
                return $student;
            }, $students);
            
            echo json_encode([
                'success' => true,
                'date' => $date,
                'students' => $students_with_attendance
            ]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch students: ' . $e->getMessage()]);
            exit;
        }

    } elseif ($action === 'get_attendance_history') {
        $target_class_id = intval($_GET['class_id'] ?? $class_id ?? 0);
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        
        if (!$target_class_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Class ID is required']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT a.date as attendance_date, a.status, COUNT(*) as count
                                  FROM attendance a
                                  JOIN students s ON a.student_id = s.id
                                  WHERE s.class_id = ? AND a.date BETWEEN ? AND ?
                                  GROUP BY a.date, a.status
                                  ORDER BY a.date DESC");
            $stmt->execute([$target_class_id, $start_date, $end_date]);
            $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to fetch attendance history: ' . $e->getMessage()]);
            exit;
        }

    } else {
        // Default: return teacher info, classes, and subjects
        echo json_encode([
            'success' => true,
            'teacher' => [
                'id' => $teacher_id,
                'name' => $teacher_name,
                'school_name' => $session['school_name'] ?? '',
                'teacher_type' => $teacher_type,
                'class_name' => $class_name,
                'class_id' => $class_id
            ],
            'calendar_status' => [
                'is_holiday' => $calendar_status['is_holiday'],
                'school_status' => $calendar_status['school_status'],
                'current_holiday' => $calendar_status['current_holiday'] ?? null,
                'current_term' => $calendar_status['current_term'] ?? null,
                'current_year' => $calendar_status['current_year'] ?? null,
            ],
            'classes' => array_map(function($c) {
                return [
                    'id' => intval($c['id']),
                    'class_name' => $c['class_name']
                ];
            }, $classes),
            'subject_assignments' => array_map(function($s) {
                return [
                    'id' => intval($s['id']),
                    'teacher_id' => intval($s['teacher_id'] ?? 0),
                    'class_id' => intval($s['class_id'] ?? 0),
                    'subject_id' => intval($s['subject_id'] ?? 0),
                    'class_name' => $s['class_name'] ?? '',
                    'subject_name' => $s['subject_name'] ?? ''
                ];
            }, $subject_assignments)
        ]);
        exit;
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true) ?: $_POST;

    $action = $input['action'] ?? ($_POST['action'] ?? '');
    
    if ($action === 'save_attendance') {
        $target_class_id = intval($input['class_id'] ?? ($_POST['class_id'] ?? 0));
        $date = $input['date'] ?? ($_POST['date'] ?? date('Y-m-d'));
        $attendance = $input['attendance'] ?? ($_POST['attendance'] ?? []);
        
        if (!$target_class_id || empty($attendance)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Class ID and attendance data are required']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Delete existing attendance for students in this class for this date
            $stmt = $pdo->prepare("DELETE FROM attendance 
                                  WHERE student_id IN (SELECT id FROM students WHERE class_id = ?) AND date = ?");
            $stmt->execute([$target_class_id, $date]);
            
            // Insert new attendance records
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, remarks, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())");
            
            $saved_count = 0;
            foreach ($attendance as $record) {
                $student_id = intval($record['student_id'] ?? 0);
                $status = trim($record['status'] ?? '');
                $remarks = trim($record['remarks'] ?? '');
                
                if ($student_id > 0 && !empty($status)) {
                    $stmt->execute([$student_id, $date, $status, $remarks ?: null]);
                    $saved_count++;
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Attendance saved successfully! ($saved_count records)"
            ]);
            exit;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to save attendance: ' . $e->getMessage()]);
            exit;
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }
}
