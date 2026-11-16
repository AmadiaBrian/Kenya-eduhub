<?php
// Teachers Dashboard API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

function getAuthSessionToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $val) {
        if (strcasecmp($key, 'X-Session-Token') === 0 && !empty($val)) {
            return trim($val);
        }
        if (strcasecmp($key, 'Authorization') === 0 && !empty($val)) {
            if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) {
                return trim($matches[1]);
            }
            return trim($val);
        }
    }
    if (!empty($_SERVER['HTTP_X_SESSION_TOKEN'])) return trim($_SERVER['HTTP_X_SESSION_TOKEN']);
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $val = $_SERVER['HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) return trim($matches[1]);
        return trim($val);
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $val = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) return trim($matches[1]);
        return trim($val);
    }
    if (!empty($_GET['session_token'])) return trim($_GET['session_token']);
    if (!empty($_POST['session_token'])) return trim($_POST['session_token']);
    return '';
}

$session_token = getAuthSessionToken();

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
    $current_date = date('Y-m-d');
    $current_year = date('Y');

    $calendar_status = [
        'is_holiday' => false,
        'school_status' => 'in_session',
        'current_term' => null,
        'current_year' => $current_year
    ];
    
    try {
        // Check for holiday
        $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$school_id, $current_date, $current_date]);
        $holiday = $stmt->fetch();
        
        if ($holiday) {
            $calendar_status['is_holiday'] = true;
            $calendar_status['school_status'] = 'holiday';
            $calendar_status['current_holiday'] = [
                'id' => $holiday['id'],
                'holiday_name' => $holiday['holiday_name'],
                'start_date' => $holiday['start_date'],
                'end_date' => $holiday['end_date']
            ];
        }

        // Find current or active term
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ? LIMIT 1");
        $stmt->execute([$school_id, $current_date, $current_date]);
        $term = $stmt->fetch();

        if (!$term) {
            $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY is_active DESC, year DESC, term_number ASC LIMIT 1");
            $stmt->execute([$school_id]);
            $term = $stmt->fetch();
        }

        if ($term) {
            if (!$calendar_status['is_holiday']) {
                $calendar_status['school_status'] = 'in_session';
            }
            $calendar_status['current_term'] = [
                'id' => $term['id'],
                'term_name' => $term['term_name'],
                'term_number' => $term['term_number'],
                'start_date' => $term['start_date'],
                'end_date' => $term['end_date']
            ];
        } else {
            if (!$calendar_status['is_holiday']) {
                $calendar_status['school_status'] = 'break';
            }
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch calendar status: " . $e->getMessage());
    }

    // Get statistics
    $stats = [
        'total_students' => 0,
        'attendance_today' => 0,
        'present_today' => 0,
        'attendance_rate' => 0.0,
        'performance_records' => 0
    ];
    
    try {
        // 1. Total Students
        if ($class_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $stats['total_students'] = (int)$stmt->fetch()['total'];
        }

        if ($stats['total_students'] === 0 && $school_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ?");
            $stmt->execute([$school_id]);
            $stats['total_students'] = (int)$stmt->fetch()['total'];
        }

        // 2. Attendance
        if ($class_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE()");
            $stmt->execute([$class_id]);
            $stats['attendance_today'] = (int)$stmt->fetch()['total'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = CURDATE() AND LOWER(a.status) = 'present'");
            $stmt->execute([$class_id]);
            $stats['present_today'] = (int)$stmt->fetch()['total'];
        }

        if ($stats['attendance_today'] === 0 && $school_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ? AND a.date = CURDATE()");
            $stmt->execute([$school_id]);
            $stats['attendance_today'] = (int)$stmt->fetch()['total'];

            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ? AND a.date = CURDATE() AND LOWER(a.status) = 'present'");
            $stmt->execute([$school_id]);
            $stats['present_today'] = (int)$stmt->fetch()['total'];
        }

        // Fallback: If no attendance records for today, check the most recent date with attendance
        if ($stats['attendance_today'] === 0) {
            $stmt = $pdo->prepare("SELECT MAX(date) as latest_date FROM attendance");
            $stmt->execute();
            $latest_date = $stmt->fetch()['latest_date'] ?? null;

            if ($latest_date) {
                if ($class_id > 0) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = ?");
                    $stmt->execute([$class_id, $latest_date]);
                    $stats['attendance_today'] = (int)$stmt->fetch()['total'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.class_id = ? AND a.date = ? AND LOWER(a.status) = 'present'");
                    $stmt->execute([$class_id, $latest_date]);
                    $stats['present_today'] = (int)$stmt->fetch()['total'];
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ? AND a.date = ?");
                    $stmt->execute([$school_id, $latest_date]);
                    $stats['attendance_today'] = (int)$stmt->fetch()['total'];

                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance a JOIN students s ON a.student_id = s.id WHERE s.school_id = ? AND a.date = ? AND LOWER(a.status) = 'present'");
                    $stmt->execute([$school_id, $latest_date]);
                    $stats['present_today'] = (int)$stmt->fetch()['total'];
                }
            }
        }

        if ($stats['attendance_today'] > 0) {
            $stats['attendance_rate'] = round(($stats['present_today'] / $stats['attendance_today']) * 100, 1);
        } else {
            $stats['attendance_rate'] = 0.0;
        }

        // 3. Performance Records
        if ($class_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.class_id = ?");
            $stmt->execute([$class_id]);
            $stats['performance_records'] = (int)$stmt->fetch()['total'];
        }
        if ($stats['performance_records'] === 0 && $school_id > 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance ap JOIN students s ON ap.student_id = s.id WHERE s.school_id = ?");
            $stmt->execute([$school_id]);
            $stats['performance_records'] = (int)$stmt->fetch()['total'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch statistics: " . $e->getMessage());
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
            'teacher_type' => $session['teacher_type'] ?? 'teacher'
        ],
        'calendar_status' => $calendar_status,
        'stats' => $stats
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
