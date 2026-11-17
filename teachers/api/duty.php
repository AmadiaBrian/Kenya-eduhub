<?php
// Teachers Duty API - Weekly Duty & Duty Management
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

function success_response($data, $message = 'Success') {
    echo json_encode(['success' => true, 'message' => $message, 'data' => $data]);
    exit;
}

function error_response($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

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

$school_id = null;
$teacher_id = null;

$session_token = getAuthSessionToken();

if (!empty($session_token)) {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, t.school_id, t.first_name, t.last_name, s.school_name
                              FROM teacher_sessions ts
                              JOIN teachers t ON ts.teacher_id = t.id
                              JOIN schools s ON t.school_id = s.id
                              WHERE ts.session_token = ? AND ts.expires_at > NOW()");
        $stmt->execute([$session_token]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($session) {
            $teacher_id = $session['teacher_id'];
            $school_id = $session['school_id'];
        }
    } catch (PDOException $e) {
        error_log("Failed to verify session token: " . $e->getMessage());
    }
}

if (!$school_id && isset($_SESSION['school_id'])) {
    $school_id = $_SESSION['school_id'];
    $teacher_id = $_SESSION['teacher_id'] ?? null;
}

if (!$school_id || !$teacher_id) {
    error_response('Unauthorized or invalid session token', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Teacher details
        $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get current duty
        $current_date = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT da.*
            FROM duty_assignments da
            WHERE da.teacher_id = ? AND da.school_id = ?
            AND da.week_start <= ? AND da.week_end >= ?
            AND da.duty_type = 'weekly'
            LIMIT 1
        ");
        $stmt->execute([$teacher_id, $school_id, $current_date, $current_date]);
        $current_duty = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        // Get students list
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, admission_number FROM students WHERE school_id = ? AND status = 'active' ORDER BY first_name, last_name");
        $stmt->execute([$school_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent leaveout chits
        $stmt = $pdo->prepare("
            SELECT lc.*, s.first_name, s.last_name, s.admission_number
            FROM leaveout_chits lc
            JOIN students s ON lc.student_id = s.id
            WHERE lc.school_id = ? AND lc.teacher_id = ?
            ORDER BY lc.created_at DESC LIMIT 15
        ");
        $stmt->execute([$school_id, $teacher_id]);
        $leaveout_chits = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent incident reports
        $stmt = $pdo->prepare("
            SELECT * FROM incident_reports
            WHERE school_id = ? AND teacher_id = ?
            ORDER BY created_at DESC LIMIT 15
        ");
        $stmt->execute([$school_id, $teacher_id]);
        $incidents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent cleanliness checks
        $stmt = $pdo->prepare("
            SELECT * FROM cleanliness_checks
            WHERE school_id = ? AND teacher_id = ?
            ORDER BY created_at DESC LIMIT 15
        ");
        $stmt->execute([$school_id, $teacher_id]);
        $cleanliness_checks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response_data = [
            'teacher' => $teacher,
            'current_duty' => $current_duty,
            'students' => $students,
            'leaveout_chits' => $leaveout_chits,
            'incidents' => $incidents,
            'cleanliness_checks' => $cleanliness_checks
        ];

        success_response($response_data, 'Duty data retrieved successfully');

    } catch (PDOException $e) {
        error_log("Failed to fetch duty data: " . $e->getMessage());
        error_response('Failed to fetch duty data: ' . $e->getMessage(), 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true) ?: $_POST;

    $action = $input['action'] ?? ($_POST['action'] ?? null);

    if (isset($input['create_leaveout']) || $action === 'create_leaveout') {
        $student_id = intval($input['student_id'] ?? ($_POST['student_id'] ?? 0));
        $reason = trim($input['reason'] ?? ($_POST['reason'] ?? ''));

        if (!$student_id || empty($reason)) {
            error_response('Student ID and reason are required');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO leaveout_chits (school_id, teacher_id, student_id, reason, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $teacher_id, $student_id, $reason, $teacher_id]);

            success_response(['id' => $pdo->lastInsertId()], 'Leaveout chit created successfully');
        } catch (PDOException $e) {
            error_log("Failed to create leaveout chit: " . $e->getMessage());
            error_response('Failed to create leaveout chit: ' . $e->getMessage(), 500);
        }

    } elseif (isset($input['report_incident']) || $action === 'report_incident') {
        $incident_type = trim($input['incident_type'] ?? ($_POST['incident_type'] ?? ''));
        $description = trim($input['description'] ?? ($_POST['description'] ?? ''));
        $severity = trim($input['severity'] ?? ($_POST['severity'] ?? 'low'));

        if (empty($incident_type) || empty($description)) {
            error_response('Incident type and description are required');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO incident_reports (school_id, teacher_id, incident_type, description, severity, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $teacher_id, $incident_type, $description, $severity, $teacher_id]);

            success_response(['id' => $pdo->lastInsertId()], 'Incident reported successfully');
        } catch (PDOException $e) {
            error_log("Failed to report incident: " . $e->getMessage());
            error_response('Failed to report incident: ' . $e->getMessage(), 500);
        }

    } elseif (isset($input['record_cleanliness']) || $action === 'record_cleanliness') {
        $area = trim($input['area'] ?? ($_POST['area'] ?? ''));
        $status = trim($input['status'] ?? ($_POST['status'] ?? 'good'));
        $notes = trim($input['notes'] ?? ($_POST['notes'] ?? ''));

        if (empty($area) || empty($status)) {
            error_response('Area and status are required');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO cleanliness_checks (school_id, teacher_id, area, status, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $teacher_id, $area, $status, $notes, $teacher_id]);

            success_response(['id' => $pdo->lastInsertId()], 'Cleanliness check recorded successfully');
        } catch (PDOException $e) {
            error_log("Failed to record cleanliness check: " . $e->getMessage());
            error_response('Failed to record cleanliness check: ' . $e->getMessage(), 500);
        }

    } else {
        error_response('Invalid duty action request');
    }
}
