<?php
// Student Subjects API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

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

function require_auth() {
    if (!isset($_SESSION['teacher_id']) && !isset($_SESSION['school_id'])) {
        error_response('Unauthorized', 401);
    }
}

require_auth();

$school_id = $_SESSION['school_id'] ?? null;

if (!$school_id) {
    error_response('School ID required', 400);
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
            $subjects = $stmt->fetchAll();

            success_response($subjects, 'Student subject IDs retrieved successfully');
        }

        // Handle request for subject names (existing functionality)
        $student_ids = isset($_GET['student_ids']) ? explode(',', $_GET['student_ids']) : [];

        if (empty($student_ids)) {
            success_response([], 'No student IDs provided');
        }

        $placeholders = str_repeat('?,', count($student_ids) - 1) . '?';

        $query = "SELECT ss.student_id, GROUP_CONCAT(s.subject_name) as subject_names
                  FROM student_subjects ss
                  JOIN subjects s ON ss.subject_id = s.id
                  WHERE ss.student_id IN ($placeholders) AND ss.school_id = ? GROUP BY ss.student_id";

        $params = array_merge($student_ids, [$school_id]);

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();

        success_response($assignments, 'Student subjects retrieved successfully');

    } catch (PDOException $e) {
        error_log("Failed to fetch student subjects: " . $e->getMessage());
        error_response('Failed to fetch student subjects', 500);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is auto-assign compulsory subjects request
    if (isset($_POST['auto_assign_compulsory'])) {
        // Auto-assign compulsory subjects to students in a class
        $class_id = intval($_POST['class_id'] ?? 0);
        $stream_id = isset($_POST['stream_id']) ? intval($_POST['stream_id']) : null;

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
                success_response([], 'No compulsory categories found');
            }

            // Get all subjects in compulsory categories
            $placeholders = str_repeat('?,', count($compulsory_categories) - 1) . '?';
            $stmt = $pdo->prepare("SELECT id FROM subjects WHERE school_id = ? AND category_id IN ($placeholders) AND status = 'active'");
            $params = array_merge([$school_id], $compulsory_categories);
            $stmt->execute($params);
            $compulsory_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (empty($compulsory_subjects)) {
                $pdo->rollBack();
                success_response([], 'No compulsory subjects found');
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
                success_response([], 'No students found in class');
            }

            // Assign all compulsory subjects to all students
            $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id, school_id) VALUES (?, ?, ?)");
            $assigned_count = 0;

            foreach ($students as $student_id) {
                foreach ($compulsory_subjects as $subject_id) {
                    try {
                        $stmt->execute([$student_id, $subject_id, $school_id]);
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
            $pdo->rollBack();
            error_log("Failed to auto-assign compulsory subjects: " . $e->getMessage());
            error_response('Failed to auto-assign compulsory subjects', 500);
        }
    } else {
        // Regular subject assignment (JSON input)
        $input = json_decode(file_get_contents('php://input'), true);

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
            $school_limits = $stmt->fetch();

            $min_subjects = $school_limits['min_subjects'] ?? 7;
            $max_subjects = $school_limits['max_subjects'] ?? 8;

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id, school_id) VALUES (?, ?, ?)");

            $assigned_count = 0;
            $violations = [];

            foreach ($student_ids as $student_id) {
                // Check current subject count for this student
                $check_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_subjects WHERE student_id = ? AND school_id = ?");
                $check_stmt->execute([intval($student_id), $school_id]);
                $current_count = $check_stmt->fetch()['count'];

                // Check if adding this subject would exceed max
                if ($current_count >= $max_subjects) {
                    $violations[] = "Student ID $student_id already has maximum subjects ($max_subjects)";
                    continue;
                }

                try {
                    $stmt->execute([intval($student_id), $subject_id, $school_id]);
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
            $pdo->rollBack();
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
