<?php
// Class Fee Statement Generation API for Teachers (PDF Output)
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
        $class_name = $_SESSION['class_name'] ?? '';
        $stream_name = $_SESSION['stream_name'] ?? '';
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
    $class_name = $session['class_name'] ?? '';
    $stream_name = '';
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
    if (!$class_id) {
        sendJsonResponse(['success' => false, 'error' => 'No class assigned to teacher']);
    }
    
    // Get school information
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get teacher information
    $stmt = $pdo->prepare("SELECT CONCAT(t.first_name, ' ', t.last_name) as teacher_name FROM teachers t WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get all students in class
    $query = "SELECT s.*, c.class_name, st.stream_name 
              FROM students s
              LEFT JOIN classes c ON s.class_id = c.id
              LEFT JOIN streams st ON s.stream_id = st.id
              WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'";
    $params = [$school_id, $class_id];
    
    if ($stream_id) {
        $query .= " AND s.stream_id = ?";
        $params[] = $stream_id;
    }
    
    $query .= " ORDER BY s.first_name, s.last_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        sendJsonResponse(['success' => false, 'error' => 'No active students found in class']);
    }
    
    $current_year = date('Y');
    $class_fee_data = [];
    $total_class_fees = 0;
    $total_class_paid = 0;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
        $stmt->execute([$school_id, $class_id, $current_year]);
        $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
        $stmt->execute([$student_id, $current_year]);
        $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $student_total_fees = 0;
        $student_total_paid = 0;
        
        foreach ($fee_structures as $fs) {
            $student_total_fees += floatval($fs['amount'] ?? 0);
        }

        foreach ($payments as $payment) {
            $student_total_paid += floatval($payment['amount'] ?? 0);
        }

        $class_fee_data[] = [
            'student' => $student,
            'total_fees' => $student_total_fees,
            'total_paid' => $student_total_paid,
            'balance' => $student_total_fees - $student_total_paid
        ];
        
        $total_class_fees += $student_total_fees;
        $total_class_paid += $student_total_paid;
    }
    
    $total_class_balance = $total_class_fees - $total_class_paid;

    $filename = "class_fee_statement_" . strtolower(str_replace(' ', '_', $class_name ?: "class")) . "_" . date('Y_m_d_His') . ".pdf";

    $receipts_dir = __DIR__ . "/../receipts";
    if (!is_dir($receipts_dir)) {
        mkdir($receipts_dir, 0777, true);
    }
    $filepath = $receipts_dir . "/" . $filename;

    // FPDF Class
    if (!class_exists('ClassFeePDF')) {
        class ClassFeePDF extends FPDF {
            public $schoolName;
            public $className;
            public $streamName;
            public $currentYear;
            public $currentTerm;
            public $teacherName;

            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(255, 107, 53);
                $this->Cell(0, 8, strtoupper($this->schoolName), 0, 1, 'C');

                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(32, 33, 36);
                $titleStr = "CLASS FEE STATEMENT - " . strtoupper($this->className . ($this->streamName ? " ({$this->streamName})" : ""));
                $this->Cell(0, 6, $titleStr, 0, 1, 'C');

                $this->SetFont('Arial', '', 10);
                $this->SetTextColor(95, 99, 104);
                $this->Cell(0, 5, "Academic Year: {$this->currentYear} | Term: {$this->currentTerm} | Date: " . date('F j, Y'), 0, 1, 'C');

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
                $this->Cell(0, 10, 'KENYA EDUHUB TEACHER PORTAL - OFFICIAL CLASS STATEMENT | Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }
    }

    $pdf = new ClassFeePDF('P', 'mm', 'A4');
    $pdf->schoolName = $school['school_name'] ?? 'Kenya EduHub';
    $pdf->className = $class_name ?: 'Class';
    $pdf->streamName = $stream_name;
    $pdf->currentYear = $current_year;
    $pdf->currentTerm = $current_term;
    $pdf->teacherName = $teacher['teacher_name'] ?? '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Table Header
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(232, 236, 239);
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.3);

    $pdf->Cell(10, 8, '#', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Admission No', 1, 0, 'C', true);
    $pdf->Cell(55, 8, 'Student Name', 1, 0, 'L', true);
    $pdf->Cell(25, 8, 'Stream', 1, 0, 'C', true);
    $pdf->Cell(23, 8, 'Total Fees', 1, 0, 'R', true);
    $pdf->Cell(23, 8, 'Total Paid', 1, 0, 'R', true);
    $pdf->Cell(24, 8, 'Balance', 1, 1, 'R', true);

    $pdf->SetFont('Arial', '', 9);

    foreach ($class_fee_data as $index => $data) {
        $fill = ($index % 2 == 1);
        if ($fill) {
            $pdf->SetFillColor(250, 250, 250);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }

        $pdf->Cell(10, 7, ($index + 1), 1, 0, 'C', true);
        $pdf->Cell(30, 7, $data['student']['admission_number'], 1, 0, 'C', true);
        $pdf->Cell(55, 7, strtoupper($data['student']['first_name'] . ' ' . $data['student']['last_name']), 1, 0, 'L', true);
        $pdf->Cell(25, 7, $data['student']['stream_name'] ?? '-', 1, 0, 'C', true);
        $pdf->Cell(23, 7, number_format($data['total_fees']), 1, 0, 'R', true);
        $pdf->Cell(23, 7, number_format($data['total_paid']), 1, 0, 'R', true);

        if ($data['balance'] > 0) {
            $pdf->SetTextColor(197, 34, 31);
        } else {
            $pdf->SetTextColor(19, 115, 51);
        }
        $pdf->Cell(24, 7, number_format($data['balance']), 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->Ln(8);

    // Summary Box
    $pdf->SetFillColor(240, 244, 249);
    $pdf->SetDrawColor(255, 107, 53);
    $pdf->Rect(10, $pdf->GetY(), 190, 22, 'DF');

    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, 'Class Financial Summary', 0, 1, 'L');

    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(15);
    $summaryLine = "Total Candidates: " . count($class_fee_data) .
                   " | Fees: KES " . number_format($total_class_fees) .
                   " | Paid: KES " . number_format($total_class_paid) .
                   " | Balance: KES " . number_format($total_class_balance);
    $pdf->Cell(0, 5, $summaryLine, 0, 1, 'L');

    $pdf->Output('F', $filepath);

    sendJsonResponse([
        'success' => true,
        'statement_url' => '/teachers/receipts/' . $filename,
        'statement_filename' => $filename
    ]);

} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
