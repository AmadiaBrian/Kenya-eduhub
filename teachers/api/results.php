<?php
// API endpoint for teacher results data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

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

    // Get teacher details
    $teacher = null;
    try {
        $stmt = $pdo->prepare("SELECT t.*, s.school_name, s.sms_enabled FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
        $stmt->execute([$teacher_id]);
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch teacher in results API: " . $e->getMessage());
    }

    if (!$teacher) {
        echo json_encode(['success' => false, 'error' => 'Teacher details not found']);
        exit;
    }

    // Get terms
    $terms = [];
    try {
        $current_year = date('Y');
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
        $stmt->execute([$school_id, $current_year]);
        $terms = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        error_log("Failed to fetch terms in results API: " . $e->getMessage());
    }

    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    // Get grading scales
    $grading_scales = [];
    try {
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name FROM grading_scales gs LEFT JOIN subjects s ON gs.subject_id = s.id WHERE gs.school_id = ? ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch grading scales in results API: " . $e->getMessage());
    }

    // Get aggregate points distribution
    $aggregate_distribution = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch aggregate distribution in results API: " . $e->getMessage());
    }

    // Get school subject limits
    $school_limits = ['min_subjects' => 7, 'max_subjects' => 8];
    try {
        $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $fetched_limits = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fetched_limits) {
            $school_limits = $fetched_limits;
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch school limits in results API: " . $e->getMessage());
    }

    // Get classes
    $classes = [];
    try {
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
        $stmt->execute([$school_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch classes in results API: " . $e->getMessage());
    }

    // Get streams
    $streams = [];
    try {
        $stmt = $pdo->prepare("SELECT id, stream_name, class_id FROM streams WHERE school_id = ? ORDER BY stream_name");
        $stmt->execute([$school_id]);
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch streams in results API: " . $e->getMessage());
    }

    // Get exam types
    $exam_types = [];
    try {
        $stmt = $pdo->prepare("SELECT id, exam_type_name, exam_type_code FROM exam_types WHERE school_id = ? AND is_active = 1 ORDER BY exam_type_name");
        $stmt->execute([$school_id]);
        $exam_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch exam types in results API: " . $e->getMessage());
    }

    // Get student subject assignments
    $student_subject_assignments = [];
    try {
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE st.school_id = ?");
        $stmt->execute([$school_id]);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($assignments as $assignment) {
            $admission_number = $assignment['admission_number'];
            $subject_name = $assignment['subject_name'];
            $is_compulsory = intval($assignment['is_compulsory'] ?? 0);
            if (!isset($student_subject_assignments[$admission_number])) {
                $student_subject_assignments[$admission_number] = [];
            }
            $student_subject_assignments[$admission_number][] = [
                'subject_name' => $subject_name,
                'is_compulsory' => $is_compulsory
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch student subject assignments in results API: " . $e->getMessage());
    }

    // Get performance records with both 'subject' and 'subject_name' fields
    $performance_records = [];
    try {
        $query = "SELECT ap.*, st.admission_number, st.first_name, st.last_name,
                         c.class_name, str.stream_name,
                         et.exam_type_name, et.exam_type_code,
                         COALESCE(s.subject_name, ap.subject) as subject,
                         COALESCE(s.subject_name, ap.subject) as subject_name,
                         (SELECT gs.points FROM grading_scales gs
                          WHERE gs.school_id = ?
                          AND ap.marks BETWEEN gs.min_score AND gs.max_score
                          AND UPPER(ap.grade) = UPPER(gs.grade_name)
                          LIMIT 1) as grade_points
                  FROM academic_performance ap
                  JOIN students st ON ap.student_id = st.id
                  LEFT JOIN classes c ON st.class_id = c.id
                  LEFT JOIN streams str ON st.stream_id = str.id
                  LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                  LEFT JOIN subjects s ON UPPER(ap.subject) = UPPER(s.subject_name) AND s.school_id = ?
                  WHERE st.school_id = ?
                  ORDER BY ap.year DESC, ap.term, st.admission_number, ap.subject";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id, $school_id, $school_id]);
        $raw_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw_records as $record) {
            $marks = floatval($record['marks'] ?? 0);
            $grade = trim($record['grade'] ?? '');
            $points = $record['grade_points'];

            if ($points === null || $points === false) {
                foreach ($grading_scales as $scale) {
                    if (!empty($grade) && strtoupper($grade) === strtoupper($scale['grade_name'] ?? '')) {
                        $points = intval($scale['points'] ?? 0);
                        break;
                    }
                    if ($marks >= $scale['min_score'] && $marks <= $scale['max_score']) {
                        $points = intval($scale['points'] ?? 0);
                        if (empty($grade)) {
                            $grade = $scale['grade_name'] ?? '';
                        }
                        break;
                    }
                }
            }

            $record['grade'] = $grade;
            $record['grade_points'] = intval($points ?? 0);
            $performance_records[] = $record;
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch performance records in results API: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'teacher' => $teacher,
        'terms' => $terms,
        'classes' => $classes,
        'streams' => $streams,
        'exam_types' => $exam_types,
        'grading_scales' => $grading_scales,
        'aggregate_distribution' => $aggregate_distribution,
        'school_limits' => $school_limits,
        'student_subject_assignments' => $student_subject_assignments,
        'performance_records' => $performance_records
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
