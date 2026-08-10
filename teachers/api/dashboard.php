<?php
// Teachers Dashboard API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Get session token from header
$headers = getallheaders();
$session_token = $headers['Authorization'] ?? '';

if (empty($session_token)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No session token provided']);
    exit;
}

try {
    // Verify session token
    $stmt = $pdo->prepare("SELECT ts.*, t.*, s.school_name 
                          FROM teacher_sessions ts
                          JOIN teachers t ON ts.teacher_id = t.id
                          JOIN schools s ON t.school_id = s.id
                          WHERE ts.session_token = ? AND ts.expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();
    
    if (!$session) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
        exit;
    }
    
    $teacher_id = $session['teacher_id'];
    $teacher_name = $session['first_name'] . ' ' . $session['last_name'];
    $school_id = $session['school_id'];
    $class_id = $session['class_id'] ?? null;
    $stream_id = $session['stream_id'] ?? null;
    
    // Get class and stream names
    $class_name = null;
    $stream_name = null;
    if ($class_id) {
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();
        $class_name = $class['class_name'] ?? null;
    }
    if ($stream_id) {
        $stmt = $pdo->prepare("SELECT stream_name FROM streams WHERE id = ?");
        $stmt->execute([$stream_id]);
        $stream = $stmt->fetch();
        $stream_name = $stream['stream_name'] ?? null;
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
        $holiday = $stmt->fetch();
        
        if ($holiday) {
            $calendar_status['is_holiday'] = true;
            $calendar_status['current_holiday'] = [
                'holiday_name' => $holiday['holiday_name'],
                'start_date' => $holiday['start_date'],
                'end_date' => $holiday['end_date']
            ];
        } else {
            // Check for active term
            $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
            $stmt->execute([$school_id, $current_date, $current_date]);
            $term = $stmt->fetch();
            
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
    
    // Get current term for statistics
    $current_term = $calendar_status['current_term']['term_name'] ?? 'Term 1';
    
    // Get statistics
    $stats = [
        'total_students' => 0,
        'attendance_today' => 0,
        'present_today' => 0,
        'performance_records' => 0
    ];
    
    try {
        if ($session['teacher_type'] === 'class_teacher' && $class_id) {
            // Class teacher statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ? AND status = 'active'");
            $stmt->execute([$class_id]);
            $stats['total_students'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE()");
            $stmt->execute([$class_id]);
            $stats['attendance_today'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE() AND a.status = 'present'");
            $stmt->execute([$class_id]);
            $stats['present_today'] = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id = ? AND ap.term = ? AND ap.year = YEAR(CURDATE())");
            $stmt->execute([$class_id, $current_term]);
            $stats['performance_records'] = $stmt->fetch()['total'];
        } elseif ($session['teacher_type'] === 'subject_teacher') {
            // Subject teacher - get assigned classes
            $stmt = $pdo->prepare("SELECT DISTINCT class_id FROM teacher_subjects WHERE teacher_id = ?");
            $stmt->execute([$teacher_id]);
            $class_ids = array_column($stmt->fetchAll(), 'class_id');
            
            if (!empty($class_ids)) {
                $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id IN ($placeholders) AND status = 'active'");
                $stmt->execute($class_ids);
                $stats['total_students'] = $stmt->fetch()['total'];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id IN ($placeholders) AND a.date = CURDATE()");
                $stmt->execute($class_ids);
                $stats['attendance_today'] = $stmt->fetch()['total'];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id IN ($placeholders) AND a.date = CURDATE() AND a.status = 'present'");
                $stmt->execute($class_ids);
                $stats['present_today'] = $stmt->fetch()['total'];
                
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id IN ($placeholders) AND ap.term = ? AND ap.year = YEAR(CURDATE())");
                $stmt->execute(array_merge($class_ids, [$current_term]));
                $stats['performance_records'] = $stmt->fetch()['total'];
            }
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch statistics: " . $e->getMessage());
    }
    
    // Calculate attendance rate
    $attendance_rate = 0;
    if ($stats['attendance_today'] > 0) {
        $attendance_rate = round(($stats['present_today'] / $stats['attendance_today']) * 100, 1);
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'teacher' => [
            'id' => $teacher_id,
            'name' => $teacher_name,
            'school_name' => $session['school_name'],
            'class_name' => $class_name,
            'stream_name' => $stream_name,
            'teacher_type' => $session['teacher_type']
        ],
        'calendar_status' => $calendar_status,
        'stats' => [
            'total_students' => $stats['total_students'],
            'attendance_today' => $stats['attendance_today'],
            'present_today' => $stats['present_today'],
            'attendance_rate' => $attendance_rate,
            'performance_records' => $stats['performance_records']
        ],
        'quick_actions' => [
            ['id' => 'attendance', 'title' => 'Take Attendance', 'icon' => 'checkmark-circle-outline', 'description' => 'Mark daily attendance'],
            ['id' => 'performance', 'title' => 'Record Performance', 'icon' => 'trending-up-outline', 'description' => 'Update student grades'],
            ['id' => 'students', 'title' => 'View Students', 'icon' => 'people-outline', 'description' => 'Manage student records'],
            ['id' => 'parents', 'title' => 'View Parents', 'icon' => 'people-outline', 'description' => 'Contact parents'],
            ['id' => 'assignments', 'title' => 'Assignments', 'icon' => 'list-outline', 'description' => 'Manage assignments'],
            ['id' => 'timetable', 'title' => 'Timetable', 'icon' => 'calendar-outline', 'description' => 'View schedule'],
            ['id' => 'results', 'title' => 'Results', 'icon' => 'trophy-outline', 'description' => 'View student results'],
            ['id' => 'profile', 'title' => 'Profile', 'icon' => 'person-circle-outline', 'description' => 'View profile']
        ]
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
