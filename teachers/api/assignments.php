<?php
// Teachers Assignments API
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

$school_id = null;
$teacher_id = null;
$session = null;

$session_token = getAuthSessionToken();

if (!empty($session_token)) {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, t.*, s.school_name
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
    if ($teacher_id) {
        $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
        $stmt->execute([$teacher_id]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$school_id || !$teacher_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized or missing session token']);
    exit;
}

$class_id = $session['class_id'] ?? ($_SESSION['class_id'] ?? null);
$stream_id = $session['stream_id'] ?? ($_SESSION['stream_id'] ?? null);
$teacher_type = $session['teacher_type'] ?? ($_SESSION['teacher_type'] ?? 'subject_teacher');

// Get class and stream names
$class_name = null;
$stream_name = null;
if ($class_id) {
    $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch(PDO::FETCH_ASSOC);
    $class_name = $class['class_name'] ?? null;
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
        // Check for active term
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
    } else {
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
        $stmt->execute([$school_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get available subjects
$subjects = [];
try {
    if ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        $subject_ids = array_unique(array_column($subject_assignments, 'subject_id'));
        if (!empty($subject_ids)) {
            $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE id IN ($placeholders)");
            $stmt->execute($subject_ids);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    if (empty($subjects)) {
        $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE school_id = ? ORDER BY subject_name");
        $stmt->execute([$school_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch subjects: " . $e->getMessage());
}

// Get assignments
$assignments = [];
try {
    $query = "SELECT a.*, c.class_name, s.subject_name, 
              CONCAT(t.first_name, ' ', t.last_name) as teacher_name
              FROM assignments a
              LEFT JOIN classes c ON a.class_id = c.id
              LEFT JOIN subjects s ON a.subject_id = s.id
              JOIN teachers t ON a.teacher_id = t.id
              WHERE a.school_id = ?";
    $params = [$school_id];

    $query .= " ORDER BY a.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($assignments as $key => $assignment) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_downloads WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['download_count'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_comments WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['comment_count'] = intval($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    }
} catch (PDOException $e) {
    error_log("Failed to fetch assignments: " . $e->getMessage());
}

// Format response
$response = [
    'success' => true,
    'teacher' => [
        'id' => $teacher_id,
        'name' => ($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? ''),
        'school_name' => $session['school_name'] ?? '',
        'teacher_type' => $teacher_type,
        'class_name' => $class_name,
    ],
    'calendar_status' => [
        'is_holiday' => $calendar_status['is_holiday'],
        'school_status' => $calendar_status['school_status'],
        'current_holiday' => $calendar_status['current_holiday'] ?? null,
        'current_term' => $calendar_status['current_term'] ?? null,
        'current_year' => $calendar_status['current_year'] ?? null,
    ],
    'classes' => $classes,
    'subjects' => $subjects,
    'assignments' => array_map(function($assignment) {
        return [
            'id' => intval($assignment['id']),
            'title' => $assignment['title'],
            'description' => $assignment['description'],
            'assignment_type' => $assignment['assignment_type'],
            'file_name' => $assignment['file_name'],
            'file_path' => $assignment['file_path'],
            'class_id' => $assignment['class_id'] ? intval($assignment['class_id']) : null,
            'subject_id' => $assignment['subject_id'] ? intval($assignment['subject_id']) : null,
            'class_name' => $assignment['class_name'],
            'subject_name' => $assignment['subject_name'],
            'teacher_name' => $assignment['teacher_name'],
            'due_date' => $assignment['due_date'],
            'created_at' => $assignment['created_at'],
            'download_count' => intval($assignment['download_count']),
            'comment_count' => intval($assignment['comment_count']),
        ];
    }, $assignments),
];

echo json_encode($response);
