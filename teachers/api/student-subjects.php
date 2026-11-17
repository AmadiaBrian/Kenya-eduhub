<?php
// Student Subjects API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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

function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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
        $stmt = $pdo->prepare("SELECT ts.*, t.school_id
                              FROM teacher_sessions ts
                              JOIN teachers t ON ts.teacher_id = t.id
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

if (!$school_id) {
    error_response('Unauthorized or invalid session token', 401);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Handle request for subject IDs with details
        if (isset($_GET['get_subject_ids']) && isset($_GET['student_id'])) {
            $student_id = intval($_GET['student_id']);

            $query = "SELECT ss.subject_id, s.subject_name
                      FROM student_subjects ss
                      JOIN subjects s ON ss.subject_id = s.id
                      WHERE ss.student_id = ? AND ss.school_id = ?";

            $stmt = $pdo->prepare($query);
            $stmt->execute([$student_id, $school_id]);
            $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

            success_response($subjects, 'Student subject IDs retrieved successfully');
        }

        // Handle request for subject names (existing functionality)
        $student_ids = isset($_GET['student_ids']) ? explode(',', $_GET['student_ids']) : [];

        if (empty($student_ids)) {
            success_response([], 'No student IDs provided');
        }

        $clean_ids = array_filter(array_map('intval', $student_ids));
        if (empty($clean_ids)) {
            success_response([], 'No valid student IDs provided');
        }

        $placeholders = str_repeat('?,', count($clean_ids) - 1) . '?';

        $query = "SELECT ss.student_id, GROUP_CONCAT(s.subject_name SEPARATOR ', ') as subject_names
                  FROM student_subjects ss
                  JOIN subjects s ON ss.subject_id = s.id
                  WHERE ss.student_id IN ($placeholders) AND ss.school_id = ? GROUP BY ss.student_id";

        $params = array_merge($clean_ids, [$school_id]);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        success_response($assignments, 'Student subjects retrieved successfully');

    } catch (PDOException $e) {
        error_log("Failed to fetch student subjects: " . $e->getMessage());
        error_response('Failed to fetch student subjects: ' . $e->getMessage(), 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is auto-assign compulsory subjects request
    $input_json = json_decode(file_get_contents('php://input'), true);

    $is_auto_assign = isset($_POST['auto_assign_compulsory']) || isset($_GET['auto_assign_compulsory']) || (is_array($input_json) && !empty($input_json['auto_assign_compulsory']));

    if ($is_auto_assign) {
        // Auto-assign compulsory subjects to students in a class
        $class_id = intval($_POST['class_id'] ?? $_GET['class_id'] ?? ($input_json['class_id'] ?? 0));
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : (isset($_GET['stream_id']) ? intval($_GET['stream_id']) : (isset($input_json['stream_id']) ? intval($input_json['stream_id']) : null));

        if (!$class_id) {
            error_response('Class ID is required');
        }

        try {
            $pdo->beginTransaction();

            // Get all compulsory categories
            $stmt = $pdo->prepare("SELECT id FROM subject_categories WHERE school_id = ? AND is_compulsory = 1");
            $stmt->execute([$school_id]);
            $compulsory_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($compulsory_categories)) {
                $pdo->rollBack();
                success_response(['assigned_count' => 0], 'No compulsory categories found');
            }

            // Get all subjects in compulsory categories
            $placeholders = str_repeat('?,', count($compulsory_categories) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE school_id = ? AND category_id IN ($placeholders) AND status = 'active'");
            $params = array_merge([$school_id], $compulsory_categories);
            $stmt->execute($params);
            $compulsory_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($compulsory_subjects)) {
                $pdo->rollBack();
                success_response(['assigned_count' => 0], 'No compulsory subjects found');
            }

            // Get all students in the class/stream
            $query = "SELECT id FROM students WHERE school_id = ? AND class_id = ?";
            $params = [$school_id, $class_id];

            if ($stream_id) {
                $query .= " AND stream_id = ?";
                $params[] = $stream_id;
            }

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($students)) {
                $pdo->rollBack();
                success_response(['assigned_count' => 0], 'No students found in class');
            }

            // Assign all compulsory subjects to all students
            $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id, school_id) VALUES (?, ?, ?)");
            $assigned_count = 0;

            foreach ($students as $s_id) {
                foreach ($compulsory_subjects as $sub_id) {
                    try {
                        $stmt->execute([$s_id, $sub_id, $school_id]);
                        $assigned_count++;
                    } catch (PDOException $e) {
                        // Ignore duplicate entries
                        if ($e->getCode() != 23000) {
                            throw $e;
                        }
                    }
                }
            }

            $pdo->commit();

            success_response(['assigned_count' => $assigned_count], "Successfully assigned compulsory subjects to students");

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to auto-assign compulsory subjects: " . $e->getMessage());
            error_response('Failed to auto-assign compulsory subjects', 500);
        }
    } else {
        // Regular subject assignment (JSON input)
        $input = $input_json;

        if (!$input) {
            error_response('Invalid JSON input');
        }

        if (empty($input['student_ids']) || empty($input['subject_id'])) {
            error_response('Student IDs and subject ID are required');
        }

        $student_ids = $input['student_ids'];
        $subject_id = intval($input['subject_id']);

        if (!is_array($student_ids)) {
            error_response('Student IDs must be an array');
        }

        try {
            // Get school subject limits
            $stmt = $pdo->prepare("SELECT min_subjects, max_subjects FROM schools WHERE id = ?");
            $stmt->execute([$school_id]);
            $school_limits = $stmt->fetch(PDO::FETCH_ASSOC);

            $min_subjects = $school_limits['min_subjects'] ?? 7;
            $max_subjects = $school_limits['max_subjects'] ?? 8;

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id, school_id) VALUES (?, ?, ?)");

            $assigned_count = 0;
            $violations = [];

            foreach ($student_ids as $s_id) {
                // Check current subject count for this student
                $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_subjects WHERE student_id = ? AND school_id = ?");
                $check_stmt->execute([intval($s_id), $school_id]);
                $current_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];

                // Check if adding this subject would exceed max
                if ($current_count >= $max_subjects) {
                    $violations[] = "Student ID $s_id already has maximum subjects ($max_subjects)";
                    continue;
                }

                try {
                    $stmt->execute([intval($s_id), $subject_id, $school_id]);
                    $assigned_count++;
                } catch (PDOException $e) {
                    // Ignore duplicate entries (unique constraint)
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }

            $pdo->commit();

            if (!empty($violations)) {
                $message = "Assigned subject to $assigned_count students. Some students were skipped due to subject limits: " . implode('; ', $violations);
                success_response(['assigned_count' => $assigned_count, 'violations' => $violations], $message);
            } else {
                success_response(['assigned_count' => $assigned_count], "Successfully assigned subject to $assigned_count students");
            }

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to assign subjects: " . $e->getMessage());
            error_response('Failed to assign subjects', 500);
        }
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $student_id = $_GET['student_id'] ?? null;
    $subject_id = $_GET['subject_id'] ?? null;

    if (!$student_id || !$subject_id) {
        error_response('Student ID and subject ID are required');
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM student_subjects
                              WHERE student_id = ? AND subject_id = ? AND school_id = ?");
        $stmt->execute([intval($student_id), intval($subject_id), $school_id]);

        if ($stmt->rowCount() > 0) {
            success_response([], 'Subject assignment removed successfully');
        } else {
            error_response('Assignment not found', 404);
        }
    } catch (PDOException $e) {
        error_log("Failed to remove assignment: " . $e->getMessage());
        error_response('Failed to remove assignment', 500);
    }
}
