<?php
// Centralized Mobile & Web Session Authentication Helper

function getAuthSessionToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $val) {
        if (strcasecmp($key, 'X-Session-Token') === 0 && !empty($val)) {
            $token = trim($val);
            if (stripos($token, 'Bearer ') === 0) {
                $token = trim(substr($token, 7));
            }
            return $token;
        }
        if (strcasecmp($key, 'Authorization') === 0 && !empty($val)) {
            $token = trim($val);
            if (stripos($token, 'Bearer ') === 0) {
                $token = trim(substr($token, 7));
            }
            return $token;
        }
    }
    if (!empty($_SERVER['HTTP_X_SESSION_TOKEN'])) {
        $token = trim($_SERVER['HTTP_X_SESSION_TOKEN']);
        if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));
        return $token;
    }
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['HTTP_AUTHORIZATION']);
        if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));
        return $token;
    }
    if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        if (stripos($token, 'Bearer ') === 0) $token = trim(substr($token, 7));
        return $token;
    }
    if (!empty($_GET['session_token'])) return trim($_GET['session_token']);
    if (!empty($_POST['session_token'])) return trim($_POST['session_token']);
    return '';
}

function verifyTeacherSession($pdo) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $token = getAuthSessionToken();

    if (!empty($token)) {
        try {
            // Fetch teacher session
            $stmt = $pdo->prepare("SELECT ts.teacher_id, ts.session_token, t.first_name, t.last_name, t.school_id, t.teacher_type, t.class_id, s.school_name, ts.expires_at
                                  FROM teacher_sessions ts
                                  JOIN teachers t ON ts.teacher_id = t.id
                                  JOIN schools s ON t.school_id = s.id
                                  WHERE ts.session_token = ?");
            $stmt->execute([$token]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session) {
                // Sliding expiration: automatically refresh active session for 30 days
                try {
                    $updateStmt = $pdo->prepare("UPDATE teacher_sessions SET expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE session_token = ?");
                    $updateStmt->execute([$token]);
                } catch (PDOException $e) {}

                return $session;
            }
        } catch (PDOException $e) {
            error_log("Session verification error: " . $e->getMessage());
        }
    }

    // Fallback to web PHP session if available
    if (isset($_SESSION['teacher_id']) && isset($_SESSION['school_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        try {
            $stmt = $pdo->prepare("SELECT t.id as teacher_id, t.first_name, t.last_name, t.school_id, t.teacher_type, t.class_id, s.school_name
                                  FROM teachers t
                                  JOIN schools s ON t.school_id = s.id
                                  WHERE t.id = ?");
            $stmt->execute([$teacher_id]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($session) {
                return $session;
            }
        } catch (PDOException $e) {}
    }

    // Return 401 Unauthorized JSON response if authentication fails
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized or invalid session token. Please log in again.',
        'code' => 401
    ]);
    exit;
}
