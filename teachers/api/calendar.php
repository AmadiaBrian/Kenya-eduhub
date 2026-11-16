<?php
// Teachers Calendar API
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

    $school_id = $session['school_id'];
    $today = date('Y-m-d');
    $current_year = date('Y');

    // Get Terms
    $stmt = $pdo->prepare("SELECT id, term_name, term_number, year, start_date, end_date FROM terms WHERE school_id = ? ORDER BY year DESC, term_number ASC");
    $stmt->execute([$school_id]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Holidays
    $stmt = $pdo->prepare("SELECT id, holiday_name, description, start_date, end_date, holiday_type FROM holidays WHERE school_id = ? AND is_active = 1 ORDER BY start_date ASC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get School Events
    $stmt = $pdo->prepare("SELECT id, event_name, description, event_date, event_time, event_type FROM school_events WHERE school_id = ? AND is_active = 1 ORDER BY event_date ASC");
    $stmt->execute([$school_id]);
    $school_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine Current Status
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ? ORDER BY year DESC, term_number ASC LIMIT 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_term = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_term) {
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY is_active DESC, year DESC, term_number ASC LIMIT 1");
        $stmt->execute([$school_id]);
        $current_term = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_holiday = $stmt->fetch(PDO::FETCH_ASSOC);

    $is_holiday = (bool)$current_holiday;
    $school_status = $is_holiday ? 'holiday' : ($current_term ? 'in_session' : 'break');

    $current_status = [
        'current_year' => $current_year,
        'school_status' => $school_status,
        'is_holiday' => $is_holiday,
        'current_term' => $current_term ? [
            'id' => $current_term['id'],
            'term_name' => $current_term['term_name'],
            'term_number' => $current_term['term_number'],
            'start_date' => $current_term['start_date'],
            'end_date' => $current_term['end_date']
        ] : null,
        'current_holiday' => $current_holiday ? [
            'id' => $current_holiday['id'],
            'holiday_name' => $current_holiday['holiday_name'],
            'description' => $current_holiday['description'] ?? '',
            'start_date' => $current_holiday['start_date'],
            'end_date' => $current_holiday['end_date']
        ] : null
    ];

    echo json_encode([
        'success' => true,
        'current_status' => $current_status,
        'terms' => $terms,
        'holidays' => $holidays,
        'school_events' => $school_events
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
