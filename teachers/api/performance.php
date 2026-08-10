<?php
// Teachers Performance API
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
    $teacher_type = $session['teacher_type'];

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

    // Get calendar status
    $calendar_status = getSchoolCalendarStatus($pdo, $school_id);

    // Get active term from calendar status
    $active_term = $calendar_status['current_term']['term_name'] ?? null;

    // Get terms from database for current year
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

    // Use active term if available, otherwise use first term
    $current_term = $active_term ?? ($terms[0] ?? 'Term 1');

    // Get streams for the teacher's class (for class teachers)
    $streams = [];
    if ($class_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM streams WHERE class_id = ?");
            $stmt->execute([$class_id]);
            $streams = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch streams: " . $e->getMessage());
        }
    }

    // Get subject assignments for subject teachers
    $subject_assignments = [];
    if ($teacher_type === 'subject_teacher') {
        try {
            $stmt = $pdo->prepare("SELECT ts.*, c.class_name, s.subject_name 
                                 FROM teacher_subjects ts
                                 JOIN classes c ON ts.class_id = c.id
                                 LEFT JOIN subjects s ON ts.subject_id = s.id
                                 WHERE ts.teacher_id = ?");
            $stmt->execute([$teacher_id]);
            $subject_assignments = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch subject assignments: " . $e->getMessage());
        }
    }

    // Get grading scales for the teacher's school with subject information
    $grading_scales = [];
    $all_subjects = [];
    $exam_types = [];
    $aggregate_distribution = [];
    try {
        // Fetch all subjects for the school
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
        $stmt->execute([$school_id]);
        $all_subjects = $stmt->fetchAll();

        // Fetch exam types for the school
        $stmt = $pdo->prepare("SELECT * FROM exam_types WHERE school_id = ? AND is_active = 1 ORDER BY exam_type_name");
        $stmt->execute([$school_id]);
        $exam_types = $stmt->fetchAll();

        // Get all grading scales for this school
        $stmt = $pdo->prepare("SELECT gs.*, s.subject_name, s.id as subject_db_id, s.school_id as subject_school_id
                              FROM grading_scales gs
                              LEFT JOIN subjects s ON gs.subject_id = s.id
                              WHERE gs.school_id = ?
                              ORDER BY gs.subject_id, gs.min_score");
        $stmt->execute([$school_id]);
        $grading_scales = $stmt->fetchAll();

        // Get aggregate points distribution
        $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
        $stmt->execute([$school_id]);
        $aggregate_distribution = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch performance data: " . $e->getMessage());
    }

    // Get subjects without performance records
    $subjects_without_performance = [];
    try {
        $stmt = $pdo->prepare("SELECT DISTINCT s.* FROM subjects s
                             WHERE s.school_id = ? AND s.status = 'active'
                             AND s.id NOT IN (
                                 SELECT DISTINCT ap.subject_id
                                 FROM academic_performance ap
                                 JOIN students st ON ap.student_id = st.id
                                 WHERE st.school_id = ?
                             )
                             ORDER BY s.subject_name");
        $stmt->execute([$school_id, $school_id]);
        $subjects_without_performance = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects without performance: " . $e->getMessage());
    }

    // Get student subject assignments for filtering total points calculation
    $student_subject_assignments = [];
    try {
        $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                              FROM student_subjects ss
                              JOIN students st ON ss.student_id = st.id
                              JOIN subjects s ON ss.subject_id = s.id
                              LEFT JOIN subject_categories sc ON s.category_id = sc.id
                              WHERE ss.school_id = ?");
        $stmt->execute([$school_id]);
        $assignments = $stmt->fetchAll();

        // Group by admission_number for easier matching
        foreach ($assignments as $assignment) {
            $admission_number = $assignment['admission_number'];
            $subject_name = $assignment['subject_name'];
            $is_compulsory = $assignment['is_compulsory'] ?? 0;
            if (!isset($student_subject_assignments[$admission_number])) {
                $student_subject_assignments[$admission_number] = [];
            }
            $student_subject_assignments[$admission_number][] = [
                'subject_name' => $subject_name,
                'is_compulsory' => $is_compulsory
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch student subject assignments: " . $e->getMessage());
    }

    // Get school minimum subjects requirement
    $school_min_subjects = 7;
    $school_max_subjects = 8;
    try {
        $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school_limits = $stmt->fetch();
        $school_min_subjects = $school_limits['min_subjects'] ?? 7;
        $school_max_subjects = $school_limits['max_subjects'] ?? 8;
    } catch (PDOException $e) {
        error_log("Failed to fetch school subject limits: " . $e->getMessage());
    }

    // Get performance records
    $performance_records = [];
    try {
        $query = "SELECT ap.*, st.admission_number, st.first_name, st.last_name, st.gender, 
                         c.class_name, s.subject_name, et.exam_type_name
                  FROM academic_performance ap
                  JOIN students st ON ap.student_id = st.id
                  LEFT JOIN classes c ON st.class_id = c.id
                  LEFT JOIN subjects s ON UPPER(ap.subject) = UPPER(s.subject_name) AND s.school_id = ?
                  LEFT JOIN exam_types et ON ap.exam_type_id = et.id
                  WHERE st.school_id = ?
                  ORDER BY ap.year DESC, ap.term, s.subject_name, ap.marks DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$school_id, $school_id]);
        $performance_records = $stmt->fetchAll();
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
        'student_subject_assignments' => $student_subject_assignments,
        'school_limits' => [
            'min_subjects' => $school_min_subjects,
            'max_subjects' => $school_max_subjects
        ],
        'performance_records' => $performance_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}
