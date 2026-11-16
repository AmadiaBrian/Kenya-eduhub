<?php
// Individual Student Fee Statement Generation API for Teachers (PDF Output)
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (ob_get_length()) ob_clean();
    http_response_code(200);
    exit;
}

function sendJsonResponse($data, $code = 200) {
    if (ob_get_length()) ob_clean();
    http_response_code($code);
    echo json_encode($data);
    exit;
}

require_once __DIR__ . '/../../config.php';

if (file_exists(__DIR__ . '/../../tools/fpdf/fpdf.php')) {
    require_once __DIR__ . '/../../tools/fpdf/fpdf.php';
} elseif (file_exists(__DIR__ . '/../../libraries/fpdf/fpdf.php')) {
    require_once __DIR__ . '/../../libraries/fpdf/fpdf.php';
} else {
    sendJsonResponse(['success' => false, 'error' => 'FPDF library not found']);
}

function getAuthSessionToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    foreach ($headers as $key => $val) {
        if (strcasecmp($key, 'X-Session-Token') === 0 && !empty($val)) return trim($val);
        if (strcasecmp($key, 'Authorization') === 0 && !empty($val)) {
            if (preg_match('/Bearer\s+(.*)$/i', $val, $matches)) return trim($matches[1]);
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
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (isset($_SESSION['teacher_id'])) {
        $teacher_id = $_SESSION['teacher_id'];
        $school_id = $_SESSION['school_id'];
        $class_id = $_SESSION['class_id'] ?? null;
        $stream_id = $_SESSION['stream_id'] ?? null;
    } else {
        sendJsonResponse(['success' => false, 'error' => 'Unauthorized: No session token provided']);
    }
} else {
    $stmt = $pdo->prepare("SELECT ts.*, t.*, s.school_name, c.class_name
                          FROM teacher_sessions ts
                          JOIN teachers t ON ts.teacher_id = t.id
                          JOIN schools s ON t.school_id = s.id
                          LEFT JOIN classes c ON t.class_id = c.id
                          WHERE ts.session_token = ? AND ts.expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        sendJsonResponse(['success' => false, 'error' => 'Invalid or expired session token']);
    }

    $teacher_id = $session['teacher_id'];
    $school_id = $session['school_id'];
    $class_id = $session['class_id'] ?? null;
    $stream_id = null;
}

// Load calendar helpers
require_once __DIR__ . '/../../includes/calendar_helpers.php';

// Get calendar status
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
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

$current_term = $active_term ?? ($terms[0] ?? 'Term 1');

try {
    $raw_input = file_get_contents('php://input');
    $json_input = json_decode($raw_input, true) ?? [];
    $student_id = $json_input['student_id'] ?? ($_POST['student_id'] ?? ($_GET['student_id'] ?? null));

    if (!$student_id) {
        sendJsonResponse(['success' => false, 'error' => 'Student ID is required']);
    }

    // Get school information
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get student details
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name
                          FROM students s
                          LEFT JOIN classes c ON s.class_id = c.id
                          LEFT JOIN streams st ON s.stream_id = st.id
                          WHERE s.id = ? AND s.school_id = ?");
    $stmt->execute([$student_id, $school_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        sendJsonResponse(['success' => false, 'error' => 'Student not found']);
    }

    $current_year = date('Y');
    $student_class_id = $student['class_id'];

    // Get fee structure
    $fee_structures = [];
    if ($student_class_id) {
        $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
        $stmt->execute([$school_id, $student_class_id, $current_year]);
        $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get payments
    $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
    $stmt->execute([$student_id, $current_year]);
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $year_total_fees = 0;
    foreach ($fee_structures as $fs) {
        $year_total_fees += floatval($fs['amount'] ?? 0);
    }

    $year_total_paid = 0;
    foreach ($payments as $p) {
        $year_total_paid += floatval($p['amount'] ?? 0);
    }

    $year_balance = $year_total_fees - $year_total_paid;

    $safe_admission_number = preg_replace('/[^a-zA-Z0-9_-]/', '_', $student['admission_number'] ?? 'student');
    $filename = "student_fee_statement_" . ($safe_admission_number ?: 'student') . "_" . date('Y_m_d_His') . ".pdf";

    $receipts_dir = __DIR__ . "/../receipts";
    if (!is_dir($receipts_dir)) {
        mkdir($receipts_dir, 0777, true);
    }
    $filepath = $receipts_dir . "/" . $filename;

    // FPDF Class
    if (!class_exists('StudentFeePDF')) {
        class StudentFeePDF extends FPDF {
            public $schoolName;
            public $studentName;
            public $admNo;
            public $className;
            public $streamName;
            public $currentYear;

            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(255, 107, 53);
                $this->Cell(0, 8, strtoupper($this->schoolName), 0, 1, 'C');

                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(32, 33, 36);
                $this->Cell(0, 6, 'OFFICIAL STUDENT FEE STATEMENT', 0, 1, 'C');

                $this->SetFont('Arial', '', 10);
                $this->SetTextColor(95, 99, 104);
                $this->Cell(0, 5, "Academic Year: {$this->currentYear} | Date: " . date('F j, Y'), 0, 1, 'C');

                $this->Ln(3);
                $this->SetDrawColor(255, 107, 53);
                $this->SetLineWidth(0.8);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                $this->Ln(5);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(128, 128, 128);
                $this->Cell(0, 10, 'KENYA EDUHUB TEACHER PORTAL - OFFICIAL STUDENT STATEMENT | Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }
    }

    $pdf = new StudentFeePDF('P', 'mm', 'A4');
    $pdf->schoolName = $school['school_name'] ?? 'Kenya EduHub';
    $pdf->studentName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
    $pdf->admNo = $student['admission_number'] ?? '';
    $pdf->className = $student['class_name'] ?? '-';
    $pdf->streamName = $student['stream_name'] ?? '-';
    $pdf->currentYear = $current_year;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Student Bio Box
    $pdf->SetFillColor(240, 244, 249);
    $pdf->SetDrawColor(220, 224, 230);
    $pdf->Rect(10, $pdf->GetY(), 190, 18, 'DF');

    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(90, 5, "Candidate: " . strtoupper($pdf->studentName), 0, 0);
    $pdf->Cell(90, 5, "Class: " . $pdf->className . " (" . $pdf->streamName . ")", 0, 1);

    $pdf->SetX(15);
    $pdf->Cell(90, 5, "Admission No: " . $pdf->admNo, 0, 0);
    $pdf->Cell(90, 5, "Status: Active Candidate", 0, 1);

    $pdf->Ln(8);

    // Fee Structure Header
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(0, 6, 'Fee Structure Breakdown', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(232, 236, 239);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->Cell(80, 7, 'Fee Type', 1, 0, 'L', true);
    $pdf->Cell(35, 7, 'Term', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Year', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Amount (KES)', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    foreach ($fee_structures as $idx => $fs) {
        $fill = ($idx % 2 == 1);
        $pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
        $pdf->Cell(80, 6, $fs['fee_type'], 1, 0, 'L', true);
        $pdf->Cell(35, 6, $fs['term'], 1, 0, 'C', true);
        $pdf->Cell(35, 6, $fs['year'], 1, 0, 'C', true);
        $pdf->Cell(40, 6, number_format($fs['amount'], 2), 1, 1, 'R', true);
    }

    $pdf->Ln(8);

    // Payment History Header
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(0, 6, 'Completed Payment History', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(232, 236, 239);
    $pdf->Cell(35, 7, 'Date', 1, 0, 'C', true);
    $pdf->Cell(45, 7, 'Fee Type', 1, 0, 'L', true);
    $pdf->Cell(35, 7, 'Method', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Reference', 1, 0, 'C', true);
    $pdf->Cell(35, 7, 'Paid (KES)', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);
    if (empty($payments)) {
        $pdf->Cell(190, 7, 'No completed payments recorded for this academic year.', 1, 1, 'C');
    } else {
        foreach ($payments as $pidx => $p) {
            $fill = ($pidx % 2 == 1);
            $pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
            $pdf->Cell(35, 6, date('M d, Y', strtotime($p['payment_date'])), 1, 0, 'C', true);
            $pdf->Cell(45, 6, $p['fee_type'] ?? 'Tuition', 1, 0, 'L', true);
            $pdf->Cell(35, 6, $p['payment_method'] ?? 'M-Pesa', 1, 0, 'C', true);
            $pdf->Cell(40, 6, $p['receipt_number'] ?? '-', 1, 0, 'C', true);
            $pdf->Cell(35, 6, number_format($p['amount'], 2), 1, 1, 'R', true);
        }
    }

    $pdf->Ln(8);

    // Outstanding Balance Summary Box
    $pdf->SetFillColor(240, 244, 249);
    $pdf->SetDrawColor(255, 107, 53);
    $pdf->Rect(10, $pdf->GetY(), 190, 20, 'DF');

    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, 'Year Statement Summary', 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(15);
    $summaryText = "Total Required Fees: KES " . number_format($year_total_fees, 2) .
                   " | Total Paid: KES " . number_format($year_total_paid, 2) .
                   " | Outstanding Balance: KES " . number_format($year_balance, 2);
    $pdf->Cell(0, 5, $summaryText, 0, 1, 'L');

    $pdf->Output('F', $filepath);

    sendJsonResponse([
        'success' => true,
        'statement_url' => '/teachers/receipts/' . $filename,
        'statement_filename' => $filename
    ]);

} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
