<?php
// Teachers Performance API
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
    $teacher_type = $session['teacher_type'] ?? 'teacher';

    // Get grading scales for the teacher's school first so they are available for grade calculation
    $grading_scales = [];
    $all_subjects = [];
    $exam_types = [];
    $aggregate_distribution = [];
    try {
        $stmt = $pdo->prepare("SELECT id, subject_name FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
        $stmt->execute([$school_id]);
        $all_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT id, exam_type_name, exam_type_code FROM exam_types WHERE school_id = ? AND is_active = 1 ORDER BY exam_type_name");
        $stmt->execute([$school_id]);
        $exam_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch grading data: " . $e->getMessage());
    }

    // Handle POST request to save/update performance records
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json_data = file_get_contents('php://input');
        $data = json_decode($json_data, true);

        $records = $data['performance'] ?? [];
        if (!empty($records)) {
            try {
                $pdo->beginTransaction();
                $count = 0;

                foreach ($records as $record) {
                    $student_id = intval($record['student_id'] ?? 0);
                    $term = trim($record['term'] ?? '');
                    $year = trim($record['year'] ?? '');
                    $exam_type_id = intval($record['exam_type_id'] ?? 0);
                    $subject = trim($record['subject'] ?? '');
                    $marks = floatval($record['marks'] ?? 0);
                    $grade = trim($record['grade'] ?? '');
                    $remarks = trim($record['remarks'] ?? '');

                    if ($student_id > 0 && !empty($term) && !empty($year) && !empty($subject)) {
                        // Check if record exists
                        $stmt = $pdo->prepare("SELECT id FROM academic_performance WHERE student_id = ? AND term = ? AND year = ? AND subject = ? AND exam_type_id = ?");
                        $stmt->execute([$student_id, $term, $year, $subject, $exam_type_id]);
                        $existing = $stmt->fetch();

                        if ($existing) {
                            $stmt = $pdo->prepare("UPDATE academic_performance SET marks = ?, grade = ?, remarks = ? WHERE id = ?");
                            $stmt->execute([$marks, $grade, $remarks, $existing['id']]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO academic_performance (student_id, term, year, subject, exam_type_id, marks, grade, remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$student_id, $term, $year, $subject, $exam_type_id, $marks, $grade, $remarks]);
                        }
                        $count++;
                    }
                }

                $pdo->commit();
                echo json_encode(['success' => true, 'count' => $count, 'message' => "Successfully saved $count performance records"]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Failed to save performance: ' . $e->getMessage()]);
                exit;
            }
        }
    }

    // Get class name
    $class_name = null;
    if ($class_id) {
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();
        $class_name = $class['class_name'] ?? null;
    }

    // Load calendar helpers
    require_once __DIR__ . '/../../includes/calendar_helpers.php';
    $calendar_status = getSchoolCalendarStatus($pdo, $school_id);
    $active_term = $calendar_status['current_term']['term_name'] ?? null;

    $terms = [];
    try {
        $current_year = date('Y');
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
        $stmt->execute([$school_id, $current_year]);
        $term_records = $stmt->fetchAll();
        foreach ($term_records as $term) {
            $terms[] = $term['term_name'];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch terms: " . $e->getMessage());
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    $current_term = $active_term ?? ($terms[0] ?? 'Term 1');

    $streams = [];
    if ($class_id) {
        try {
            $stmt = $pdo->prepare("SELECT id, stream_name FROM streams WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch streams: " . $e->getMessage());
        }
    }

    $subject_assignments = [];
    if ($teacher_type === 'subject_teacher') {
        try {
            $stmt = $pdo->prepare("SELECT ts.*, c.class_name, s.subject_name 
                                 FROM teacher_subjects ts
                                 JOIN classes c ON ts.class_id = c.id
                                 LEFT JOIN subjects s ON ts.subject_id = s.id
                                 WHERE ts.teacher_id = ?");
            $stmt->execute([$teacher_id]);
            $subject_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to fetch subject assignments: " . $e->getMessage());
        }
    }

    $subjects_without_performance = [];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT s.id, s.subject_name FROM subjects s
                             WHERE s.school_id = ? AND s.status = 'active'
                             AND s.id NOT IN (
                                 SELECT DISTINCT ap.subject_id
                                 FROM academic_performance ap
                                 JOIN students st ON ap.student_id = st.id
                                 WHERE st.school_id = ?
                             )
                             ORDER BY s.subject_name");
        $stmt->execute([$school_id, $school_id]);
        $subjects_without_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects without performance: " . $e->getMessage());
    }

    // Get performance records with exact subquery for points from grading_scales table
    $performance_records = [];
    try {
        $query = "SELECT ap.*, st.admission_number, st.first_name, st.last_name, st.gender, 
                         c.class_name, str.stream_name, s.subject_name, et.exam_type_name,
                         (SELECT gs.points FROM grading_scales gs
                          WHERE gs.school_id = ?
                          AND ap.marks BETWEEN gs.min_score AND gs.max_score
                          AND UPPER(ap.grade) = UPPER(gs.grade_name)
                          LIMIT 1) as grade_points
                  FROM academic_performance ap
                  JOIN students st ON ap.student_id = st.id
                  LEFT JOIN classes c ON st.class_id = c.id
                  LEFT JOIN streams str ON st.stream_id = str.id
                  LEFT JOIN subjects s ON UPPER(ap.subject) = UPPER(s.subject_name) AND s.school_id = ?
                  LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                  WHERE st.school_id = ?
                  ORDER BY ap.year DESC, ap.term, s.subject_name, ap.marks DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id, $school_id, $school_id]);
        $raw_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($raw_records as $record) {
            $marks = floatval($record['marks'] ?? 0);
            $grade = trim($record['grade'] ?? '');
            $points = $record['grade_points'];

            // Fallback if subquery points was null (e.g. if grade name differed in case or marks slightly off)
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
        error_log("Failed to fetch performance records: " . $e->getMessage());
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $teacher_id,
            'name' => $teacher_name,
            'school_name' => $session['school_name'],
            'teacher_type' => $teacher_type,
            'class_id' => $class_id,
            'class_name' => $class_name
        ],
        'calendar_status' => $calendar_status,
        'terms' => $terms,
        'current_term' => $current_term,
        'streams' => $streams,
        'subject_assignments' => $subject_assignments,
        'grading_scales' => $grading_scales,
        'all_subjects' => $all_subjects,
        'exam_types' => $exam_types,
        'aggregate_distribution' => $aggregate_distribution,
        'subjects_without_performance' => $subjects_without_performance,
        'performance_records' => $performance_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
