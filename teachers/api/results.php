<?php
// API endpoint for teacher results data
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../../config.php';

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
    $school_id = $session['school_id'];

    // Get teacher details
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();

    if (!$teacher) {
        echo json_encode(['success' => false, 'error' => 'Teacher not found']);
        exit;
    }

    // Get terms
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $terms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    // Get grading scales
    $stmt = $pdo->prepare("SELECT gs.*, s.subject_name FROM grading_scales gs LEFT JOIN subjects s ON gs.subject_id = s.id WHERE gs.school_id = ? ORDER BY gs.subject_id, gs.min_score");
    $stmt->execute([$school_id]);
    $grading_scales = $stmt->fetchAll();

    // Get aggregate points distribution
    $stmt = $pdo->prepare("SELECT * FROM aggregate_points_distribution WHERE school_id = ? ORDER BY min_points DESC");
    $stmt->execute([$school_id]);
    $aggregate_distribution = $stmt->fetchAll();

    // Get school subject limits
    $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school_limits = $stmt->fetch();

    // Get streams
    $stmt = $pdo->prepare("SELECT * FROM streams WHERE school_id = ? ORDER BY stream_name");
    $stmt->execute([$school_id]);
    $streams = $stmt->fetchAll();

    // Get exam types
    $stmt = $pdo->prepare("SELECT * FROM exam_types WHERE school_id = ? ORDER BY exam_type_name");
    $stmt->execute([$school_id]);
    $exam_types = $stmt->fetchAll();

    // Get student subject assignments
    $stmt = $pdo->prepare("SELECT ss.student_id, st.admission_number, s.subject_name, s.id as subject_id, sc.is_compulsory
                          FROM student_subjects ss
                          JOIN students st ON ss.student_id = st.id
                          JOIN subjects s ON ss.subject_id = s.id
                          LEFT JOIN subject_categories sc ON s.category_id = sc.id
                          WHERE ss.school_id = ?");
    $stmt->execute([$school_id]);
    $assignments = $stmt->fetchAll();

    $student_subject_assignments = [];
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

    // Get performance records
    $stmt = $pdo->prepare("SELECT ap.*, st.admission_number, st.first_name, st.last_name, et.exam_type_name, et.exam_type_code, s.subject_name 
                          FROM academic_performance ap
                          JOIN students st ON ap.student_id = st.id
                          JOIN exam_types et ON ap.exam_type_id = et.id
                          LEFT JOIN subjects s ON ap.subject = s.subject_name
                          WHERE ap.school_id = ?
                          ORDER BY st.admission_number, ap.subject");
    $stmt->execute([$school_id]);
    $performance_records = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'teacher' => $teacher,
        'terms' => $terms,
        'grading_scales' => $grading_scales,
        'aggregate_distribution' => $aggregate_distribution,
        'school_limits' => $school_limits,
        'streams' => $streams,
        'exam_types' => $exam_types,
        'student_subject_assignments' => $student_subject_assignments,
        'performance_records' => $performance_records
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
