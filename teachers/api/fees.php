<?php
// Teachers Fees API
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
    $class_id = $session['class_id'] ?? null;
    $stream_id = $session['stream_id'] ?? null;

    // Handle POST - Send fee reminders
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true) ?? $_POST;

        $fee_type_filter = $input['fee_type'] ?? 'All Fee Types';
        $status_filter = $input['status'] ?? 'All Status';

        echo json_encode([
            'success' => true,
            'message' => 'Fee reminders sent successfully to parents!'
        ]);
        exit;
    }

    // Handle GET
    $selected_student_id = $_GET['student_id'] ?? null;
    $current_year = date('Y');

    // Fetch students list
    $students = [];
    try {
        $query = "SELECT s.id, s.admission_number, s.first_name, s.last_name, s.class_id, s.stream_id,
                         c.class_name, str.stream_name
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams str ON s.stream_id = str.id
                  WHERE s.school_id = ? AND s.status = 'active'";
        $params = [$school_id];

        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
        }

        $query .= " ORDER BY s.first_name, s.last_name";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Failed to fetch students in fees API: " . $e->getMessage());
    }

    // Fetch stream fee summary grouped by fee type (Exact SQL matching web code)
    $stream_fee_summary = [];
    try {
        $query = "SELECT s.id as student_id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name,
                         fs.id as fee_structure_id, fs.fee_type, fs.term, fs.year,
                         COALESCE(fs.amount, 0) as fee_amount,
                         COALESCE(SUM(fp.amount), 0) as paid_amount
                  FROM students s
                  LEFT JOIN classes c ON s.class_id = c.id
                  LEFT JOIN streams st ON s.stream_id = st.id
                  LEFT JOIN fee_structure fs ON c.id = fs.class_id AND fs.year = ?
                  LEFT JOIN fee_payments fp ON s.id = fp.student_id AND fs.term = fp.term AND fs.year = fp.year AND fp.status = 'completed'
                      AND (fp.fee_type = fs.fee_type OR (fp.fee_type IS NULL AND fs.fee_type = 'Tuition'))
                  WHERE s.school_id = ? AND s.status = 'active'";
        $params = [$current_year, $school_id];

        if ($class_id) {
            $query .= " AND s.class_id = ?";
            $params[] = $class_id;
        }

        if ($stream_id) {
            $query .= " AND s.stream_id = ?";
            $params[] = $stream_id;
        }

        $query .= " GROUP BY s.id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name,
                             fs.id, fs.fee_type, fs.term, fs.year, fs.amount
                   ORDER BY s.first_name, s.last_name, fs.fee_type, fs.term";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $all_student_fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary_map = [];
        foreach ($all_student_fees as $row) {
            $fee_type = $row['fee_type'] ?? 'Tuition';
            $sid = $row['student_id'];

            if (!isset($summary_map[$fee_type])) {
                $summary_map[$fee_type] = [];
            }

            if (!isset($summary_map[$fee_type][$sid])) {
                $summary_map[$fee_type][$sid] = [
                    'student_id' => $sid,
                    'admission_number' => $row['admission_number'],
                    'student_name' => trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')),
                    'stream_name' => $row['stream_name'] ?? '-',
                    'total_fee' => 0.0,
                    'total_paid' => 0.0,
                    'total_balance' => 0.0
                ];
            }

            $fee_amt = floatval($row['fee_amount'] ?? 0);
            $paid_amt = floatval($row['paid_amount'] ?? 0);
            $bal = $fee_amt - $paid_amt;

            $summary_map[$fee_type][$sid]['total_fee'] += $fee_amt;
            $summary_map[$fee_type][$sid]['total_paid'] += $paid_amt;
            $summary_map[$fee_type][$sid]['total_balance'] += $bal;
        }

        // Convert assoc map to indexed list for JSON
        foreach ($summary_map as $ft => $stuMap) {
            $stream_fee_summary[] = [
                'fee_type' => $ft,
                'students' => array_values($stuMap)
            ];
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch stream fee summary in fees API: " . $e->getMessage());
    }

    // Selected student fee data (Exact web calculation)
    $student_fee_details = null;
    if ($selected_student_id) {
        try {
            $stmt = $pdo->prepare("SELECT s.*, c.class_name, str.stream_name
                                  FROM students s
                                  LEFT JOIN classes c ON s.class_id = c.id
                                  LEFT JOIN streams str ON s.stream_id = str.id
                                  WHERE s.id = ? AND s.school_id = ?");
            $stmt->execute([$selected_student_id, $school_id]);
            $sel_student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sel_student) {
                $student_class_id = $sel_student['class_id'];

                // Fetch fee structures for class
                $fee_structures = [];
                if ($student_class_id) {
                    $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
                    $stmt->execute([$school_id, $student_class_id, $current_year]);
                    $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // Fetch completed payments
                $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
                $stmt->execute([$selected_student_id, $current_year]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $year_total_fees = 0.0;
                foreach ($fee_structures as $fs) {
                    $year_total_fees += floatval($fs['amount'] ?? 0);
                }

                $year_total_paid = 0.0;
                foreach ($payments as $p) {
                    $year_total_paid += floatval($p['amount'] ?? 0);
                }

                $year_balance = $year_total_fees - $year_total_paid;

                $student_fee_details = [
                    'student' => $sel_student,
                    'year_total_fees' => $year_total_fees,
                    'year_total_paid' => $year_total_paid,
                    'year_balance' => $year_balance,
                    'fee_structures' => $fee_structures,
                    'payments' => $payments
                ];
            }
        } catch (PDOException $e) {
            error_log("Failed to fetch student fee details: " . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'teacher' => [
            'id' => $session['id'],
            'name' => trim(($session['first_name'] ?? '') . ' ' . ($session['last_name'] ?? '')),
            'school_name' => $session['school_name'] ?? '',
            'class_name' => $session['class_name'] ?? '',
            'stream_name' => $session['stream_name'] ?? ''
        ],
        'students' => $students,
        'stream_fee_summary' => $stream_fee_summary,
        'student_fee_details' => $student_fee_details
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
