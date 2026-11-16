<?php
// Teachers Profile API
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
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Invalid or expired session']);
        exit;
    }

    $teacher_id = $session['teacher_id'];
    $school_id = $session['school_id'];

    // Handle POST - Update profile or password
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $json_raw = file_get_contents('php://input');
        $input = json_decode($json_raw, true) ?? $_POST;
        
        $action = $input['action'] ?? 'update_profile';

        if ($action === 'change_password') {
            $current_password = $input['current_password'] ?? '';
            $new_password = $input['new_password'] ?? '';
            $confirm_password = $input['confirm_password'] ?? '';

            if (empty($current_password) || empty($new_password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Current and new password are required']);
                exit;
            }

            if ($new_password !== $confirm_password) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Passwords do not match']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT password FROM teachers WHERE id = ?");
            $stmt->execute([$teacher_id]);
            $t = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$t || !password_verify($current_password, $t['password'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Current password is incorrect']);
                exit;
            }

            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE teachers SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $teacher_id]);

            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            exit;
        } else {
            // Update Profile
            $first_name = trim($input['first_name'] ?? '');
            $last_name = trim($input['last_name'] ?? '');
            $email = trim($input['email'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $id_number = trim($input['id_number'] ?? '');
            $address = trim($input['address'] ?? '');
            $subject = trim($input['subject'] ?? '');

            $errors = [];
            if (empty($first_name)) $errors[] = 'First name is required';
            if (empty($last_name)) $errors[] = 'Last name is required';
            if (empty($email)) $errors[] = 'Email is required';
            if (empty($phone)) $errors[] = 'Phone is required';
            if (empty($id_number)) $errors[] = 'ID number is required';

            if (!empty($errors)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, address = ?, subject = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $address, $subject, $teacher_id]);

            // Re-fetch updated profile
            $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
            $stmt->execute([$teacher_id]);
            $updated_teacher = $stmt->fetch(PDO::FETCH_ASSOC);

            $full_name = trim(($updated_teacher['first_name'] ?? '') . ' ' . ($updated_teacher['last_name'] ?? ''));

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'teacher' => [
                    'id' => $updated_teacher['id'],
                    'first_name' => $updated_teacher['first_name'],
                    'last_name' => $updated_teacher['last_name'],
                    'name' => $full_name,
                    'email' => $updated_teacher['email'],
                    'phone' => $updated_teacher['phone'],
                    'id_number' => $updated_teacher['id_number'],
                    'address' => $updated_teacher['address'] ?? '',
                    'subject' => $updated_teacher['subject'] ?? '',
                    'teacher_type' => $updated_teacher['teacher_type'] ?? 'teacher',
                    'school_id' => $updated_teacher['school_id'],
                    'school_name' => $updated_teacher['school_name'] ?? ''
                ]
            ]);
            exit;
        }
    }

    // GET request - Return teacher details and active school subjects
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Teacher profile not found']);
        exit;
    }

    $subjects = [];
    try {
        $stmt = $pdo->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
        $stmt->execute([$school_id]);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects in profile API: " . $e->getMessage());
    }

    $full_name = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));

    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $teacher['id'],
            'first_name' => $teacher['first_name'],
            'last_name' => $teacher['last_name'],
            'name' => $full_name,
            'email' => $teacher['email'],
            'phone' => $teacher['phone'],
            'id_number' => $teacher['id_number'],
            'address' => $teacher['address'] ?? '',
            'subject' => $teacher['subject'] ?? '',
            'teacher_type' => $teacher['teacher_type'] ?? 'teacher',
            'school_id' => $teacher['school_id'],
            'school_name' => $teacher['school_name'] ?? ''
        ],
        'subjects' => $subjects
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
