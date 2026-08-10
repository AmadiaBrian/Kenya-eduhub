<?php
// Teachers Assignments API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get session token from header
$headers = getallheaders();
$session_token = $headers['Authorization'] ?? '';

if (empty($session_token)) {
    http_response_code(401);
    echo json_encode(['error' => 'No session token provided']);
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
        echo json_encode(['error' => 'Invalid or expired session']);
        exit;
    }

    $teacher_id = $session['teacher_id'];
    $teacher_name = $session['first_name'] . ' ' . $session['last_name'];
    $school_id = $session['school_id'];
    $class_id = $session['class_id'] ?? null;
    $stream_id = $session['stream_id'] ?? null;
    $teacher_type = $session['teacher_type'];

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

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
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
        $subject_assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Get available classes for the teacher
$classes = [];
try {
    if ($teacher_type === 'class_teacher' && $class_id) {
        // Class teacher sees only their assigned class
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $classes = $stmt->fetchAll();
    } elseif ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        // Subject teacher sees classes they teach
        $class_ids = array_unique(array_column($subject_assignments, 'class_id'));
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id IN ($placeholders)");
        $stmt->execute($class_ids);
        $classes = $stmt->fetchAll();
    }
    // For other teacher types, don't return any classes
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get available subjects
$subjects = [];
try {
    if ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        // Subject teacher sees only subjects they teach
        $subject_ids = array_unique(array_column($subject_assignments, 'subject_id'));
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE id IN ($placeholders)");
        $stmt->execute($subject_ids);
        $subjects = $stmt->fetchAll();
    } elseif ($teacher_type === 'class_teacher') {
        // Class teacher sees all subjects in the school
        $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $subjects = $stmt->fetchAll();
    }
    // For other teacher types, don't return any subjects
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
    
    // Filter by teacher's assigned classes/subjects
    if ($teacher_type === 'class_teacher' && $class_id) {
        $query .= " AND (a.class_id = ? OR a.class_id IS NULL)";
        $params[] = $class_id;
    } elseif ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        $subject_ids = array_column($subject_assignments, 'subject_id');
        $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
        $query .= " AND (a.subject_id IN ($placeholders) OR a.subject_id IS NULL)";
        $params = array_merge($params, $subject_ids);
    }
    
    $query .= " ORDER BY a.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get download counts and details
    foreach ($assignments as $key => $assignment) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_downloads WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['download_count'] = $stmt->fetch()['count'];
        
        // Get comment count
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM assignment_comments WHERE assignment_id = ?");
        $stmt->execute([$assignment['id']]);
        $assignments[$key]['comment_count'] = $stmt->fetch()['count'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch assignments: " . $e->getMessage());
}

// Format response
$response = [
    'success' => true,
    'teacher' => [
        'id' => $session['teacher_id'],
        'name' => $session['first_name'] . ' ' . $session['last_name'],
        'school_name' => $session['school_name'],
        'teacher_type' => $session['teacher_type'],
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
            'id' => $assignment['id'],
            'title' => $assignment['title'],
            'description' => $assignment['description'],
            'assignment_type' => $assignment['assignment_type'],
            'file_name' => $assignment['file_name'],
            'file_path' => $assignment['file_path'],
            'class_name' => $assignment['class_name'],
            'subject_name' => $assignment['subject_name'],
            'teacher_name' => $assignment['teacher_name'],
            'due_date' => $assignment['due_date'],
            'created_at' => $assignment['created_at'],
            'download_count' => $assignment['download_count'],
            'comment_count' => $assignment['comment_count'],
        ];
    }, $assignments),
];

echo json_encode($response);
