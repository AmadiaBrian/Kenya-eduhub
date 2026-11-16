<?php
// Teachers Timetable API
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
    $school_id = $session['school_id'];
    $today = date('Y-m-d');
    $current_year = date('Y');

    // 1. Get Calendar Status
    $calendar_status = [
        'is_holiday' => false,
        'school_status' => 'in_session',
        'current_term' => null,
        'current_year' => $current_year
    ];

    try {
        $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$school_id, $today, $today]);
        $holiday = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($holiday) {
            $calendar_status['is_holiday'] = true;
            $calendar_status['school_status'] = 'holiday';
            $calendar_status['current_holiday'] = [
                'id' => $holiday['id'],
                'holiday_name' => $holiday['holiday_name'],
                'start_date' => $holiday['start_date'],
                'end_date' => $holiday['end_date']
            ];
        } else {
            $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ? LIMIT 1");
            $stmt->execute([$school_id, $today, $today]);
            $term = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$term) {
                $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY is_active DESC, year DESC, term_number ASC LIMIT 1");
                $stmt->execute([$school_id]);
                $term = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($term) {
                $calendar_status['school_status'] = 'in_session';
                $calendar_status['current_term'] = [
                    'id' => $term['id'],
                    'term_name' => $term['term_name'],
                    'term_number' => $term['term_number'],
                    'start_date' => $term['start_date'],
                    'end_date' => $term['end_date']
                ];
            } else {
                $calendar_status['school_status'] = 'break';
            }
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch calendar status in timetable: " . $e->getMessage());
    }

    // 2. Fetch School Breaks
    $school_breaks = [];
    try {
        $stmt = $pdo->prepare("SELECT id, break_name, start_time, end_time FROM school_breaks WHERE school_id = ? AND is_active = 1 ORDER BY start_time");
        $stmt->execute([$school_id]);
        $school_breaks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch school breaks: " . $e->getMessage());
    }

    // 3. Fetch Timetable Assignments
    $timetable_assignments = [];
    try {
        $stmt = $pdo->prepare("
            SELECT ta.id, ta.notes, ts.day_of_week, ts.start_time, ts.end_time, ts.break_type,
                   s.subject_name, t.name as timetable_name, t.year, t.term, t.status,
                   c.class_name, st.stream_name
            FROM timetable_assignments ta
            JOIN timetable_slots ts ON ta.slot_id = ts.id
            JOIN subjects s ON ta.subject_id = s.id
            JOIN timetables t ON ta.timetable_id = t.id
            JOIN classes c ON ta.class_id = c.id
            JOIN streams st ON ta.stream_id = st.id
            WHERE ta.teacher_id = ? AND ta.school_id = ?
            ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
        ");
        $stmt->execute([$teacher_id, $school_id]);
        $timetable_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch teacher timetable: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'calendar_status' => $calendar_status,
        'school_breaks' => $school_breaks,
        'timetable_assignments' => $timetable_assignments
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
