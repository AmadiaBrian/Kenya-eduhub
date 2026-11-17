<?php
// Teachers Students API
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
    $class_id = $_GET['class_id'] ?? null;
    $stream_id = $_GET['stream_id'] ?? null;
    $status_filter = $_GET['status'] ?? null;

    // Fetch classes and streams for filtering
    $classes = [];
    try {
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE school_id = ? ORDER BY class_name");
        $stmt->execute([$school_id]);
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    $streams = [];
    try {
        $stmt = $pdo->prepare("SELECT id, stream_name, class_id FROM streams WHERE school_id = ? ORDER BY stream_name");
        $stmt->execute([$school_id]);
        $streams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {}

    // Query students
    $query = "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.gender, s.date_of_birth, s.admission_date,
                     s.status, s.class_id, s.stream_id, c.class_name, str.stream_name
              FROM students s
              LEFT JOIN classes c ON s.class_id = c.id
              LEFT JOIN streams str ON s.stream_id = str.id
              WHERE s.school_id = ?";
    $params = [$school_id];

    if (!empty($class_id)) {
        $query .= " AND s.class_id = ?";
        $params[] = $class_id;
    }

    if (!empty($stream_id)) {
        $query .= " AND s.stream_id = ?";
        $params[] = $stream_id;
    }

    if (!empty($status_filter)) {
        $query .= " AND s.status = ?";
        $params[] = $status_filter;
    }

    $query .= " ORDER BY s.admission_number ASC, s.first_name ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $raw_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate real fee balances per student matching fees API & web code
    $current_year = date('Y');
    $student_fee_summary_map = [];

    try {
        $fee_query = "SELECT s.id as student_id,
                             COALESCE(fs.fee_type, 'Tuition Fee') as fee_type,
                             COALESCE(fs.amount, 0) as fee_amount,
                             COALESCE(SUM(fp.amount), 0) as paid_amount
                      FROM students s
                      LEFT JOIN classes c ON s.class_id = c.id
                      LEFT JOIN fee_structure fs ON c.id = fs.class_id AND fs.year = ?
                      LEFT JOIN fee_payments fp ON s.id = fp.student_id AND fs.term = fp.term AND fs.year = fp.year AND fp.status = 'completed'
                          AND (fp.fee_type = fs.fee_type OR (fp.fee_type IS NULL AND fs.fee_type = 'Tuition'))
                      WHERE s.school_id = ? AND s.status = 'active'
                      GROUP BY s.id, fs.id, fs.fee_type, fs.amount";
        $fee_stmt = $pdo->prepare($fee_query);
        $fee_stmt->execute([$current_year, $school_id]);
        $fee_rows = $fee_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($fee_rows as $frow) {
            $sid = $frow['student_id'];
            $ftype = $frow['fee_type'] ?? 'Tuition Fee';
            $fee_amt = floatval($frow['fee_amount'] ?? 0);
            $paid_amt = floatval($frow['paid_amount'] ?? 0);

            if (!isset($student_fee_summary_map[$sid])) {
                $student_fee_summary_map[$sid] = [];
            }
            if (!isset($student_fee_summary_map[$sid][$ftype])) {
                $student_fee_summary_map[$sid][$ftype] = [
                    'total_fees' => 0.0,
                    'total_paid' => 0.0,
                    'balance' => 0.0
                ];
            }

            $student_fee_summary_map[$sid][$ftype]['total_fees'] += $fee_amt;
            $student_fee_summary_map[$sid][$ftype]['total_paid'] += $paid_amt;
            $student_fee_summary_map[$sid][$ftype]['balance'] += ($fee_amt - $paid_amt);
        }
    } catch (PDOException $e) {
        error_log("Failed to calculate student fee balances in students API: " . $e->getMessage());
    }

    $students = [];
    foreach ($raw_students as $student) {
        $sid = $student['id'];
        $fee_balances = $student_fee_summary_map[$sid] ?? [];

        if (empty($fee_balances)) {
            $fee_balances['Tuition Fee'] = [
                'total_fees' => 0.0,
                'total_paid' => 0.0,
                'balance' => 0.0
            ];
        }

        $total_stu_fees = 0.0;
        $total_stu_paid = 0.0;
        $total_stu_bal = 0.0;
        foreach ($fee_balances as $fb) {
            $total_stu_fees += floatval($fb['total_fees']);
            $total_stu_paid += floatval($fb['total_paid']);
            $total_stu_bal += floatval($fb['balance']);
        }

        $student['status'] = $student['status'] ?? 'active';
        $student['gender'] = $student['gender'] ?? 'Male';
        $student['fee_balances'] = $fee_balances;
        $student['total_fees'] = $total_stu_fees;
        $student['total_paid'] = $total_stu_paid;
        $student['total_balance'] = $total_stu_bal;
        $students[] = $student;
    }

    // Fetch disciplinary records
    $disciplinary_records = [];
    try {
        $dquery = "SELECT dr.id, dr.student_id, dr.incident_date, dr.action_type, dr.severity, dr.title, dr.status,
                          st.admission_number, CONCAT(st.first_name, ' ', st.last_name) as student_name
                   FROM disciplinary_records dr
                   JOIN students st ON dr.student_id = st.id
                   WHERE st.school_id = ?";
        $dparams = [$school_id];
        if (!empty($class_id)) {
            $dquery .= " AND st.class_id = ?";
            $dparams[] = $class_id;
        }
        $dquery .= " ORDER BY dr.incident_date DESC";

        $stmt = $pdo->prepare($dquery);
        $stmt->execute($dparams);
        $disciplinary_records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch disciplinary records: " . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'data' => $students,
        'classes' => $classes,
        'streams' => $streams,
        'disciplinary_records' => $disciplinary_records
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
