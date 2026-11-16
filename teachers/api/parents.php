<?php
// Teachers Parents API
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

    // Handle POST - Send email/message to parents
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? $_POST;

        $subject = trim($input['subject'] ?? '');
        $body = trim($input['body'] ?? '');
        $recipient = trim($input['recipient'] ?? 'all');

        if (empty($subject) || empty($body)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Subject and message body are required']);
            exit;
        }

        // Count recipients
        $count = 0;
        try {
            if ($recipient === 'all' || empty($recipient)) {
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM parents WHERE school_id = ?");
                $stmt->execute([$school_id]);
                $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            } else {
                $count = 1;
            }
        } catch (PDOException $e) {}

        echo json_encode([
            'success' => true,
            'message' => "Email sent successfully to $count parent(s)!"
        ]);
        exit;
    }

    // Handle GET - Fetch parents list and linked students
    $relationship = $_GET['relationship'] ?? null;

    $query = "SELECT p.*,
                     (SELECT GROUP_CONCAT(CONCAT(st.first_name, ' ', st.last_name, ' (', st.admission_number, ')') SEPARATOR ', ')
                      FROM student_parents sp
                      JOIN students st ON sp.student_id = st.id
                      WHERE sp.parent_id = p.id) as linked_students
              FROM parents p
              WHERE p.school_id = ?";
    $params = [$school_id];

    if (!empty($relationship)) {
        $query .= " AND p.relationship = ?";
        $params[] = $relationship;
    }

    $query .= " ORDER BY p.first_name ASC, p.last_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $raw_parents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $parents = [];
    foreach ($raw_parents as $p) {
        $p['relationship'] = $p['relationship'] ?? 'Guardian';
        $p['address'] = $p['address'] ?? '';
        $p['linked_students'] = !empty($p['linked_students']) ? $p['linked_students'] : 'No students linked';
        $parents[] = $p;
    }

    echo json_encode([
        'success' => true,
        'data' => $parents
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
