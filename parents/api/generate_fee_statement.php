<?php
// Fee Statement Generation API for Parents
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to output
ini_set('log_errors', 1);

header('Content-Type: application/json');

try {
    // Check if parent is logged in
    if (!isset($_SESSION['parent_id'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    $parent_id = $_SESSION['parent_id'];
    $school_id = $_SESSION['school_id'];

    // Get terms from database for current year
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
        error_log("Failed to fetch terms: " . $e->getMessage());
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }

    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
        exit;
    }

    $student_id = $_POST['student_id'] ?? null;

    if (!$student_id) {
        echo json_encode(['success' => false, 'error' => 'Student ID is required']);
        exit;
    }

    // Verify student belongs to this parent
    $stmt = $pdo->prepare("
        SELECT s.*, sch.school_name, sch.address as school_address, sch.phone as school_phone, sch.email as school_email
        FROM students s
        JOIN student_parents sp ON s.id = sp.student_id
        JOIN schools sch ON s.school_id = sch.id
        WHERE s.id = ? AND sp.parent_id = ?
    ");
    $stmt->execute([$student_id, $parent_id]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }

    // Get class information
    $stmt = $pdo->prepare("SELECT c.class_name FROM classes c WHERE c.id = ?");
    $stmt->execute([$student['class_id']]);
    $class = $stmt->fetch();
    $class_name = $class['class_name'] ?? 'Not Assigned';

    // Get parent information
    $stmt = $pdo->prepare("
        SELECT CONCAT(p.first_name, ' ', p.last_name) as parent_name, p.phone as parent_phone
        FROM parents p
        WHERE p.id = ?
    ");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();

    // Get current year fee structure
    $current_year = date('Y');

    $stmt = $pdo->prepare("
        SELECT * FROM fee_structure
        WHERE school_id = ? AND class_id = ? AND year = ?
        ORDER BY term, fee_type
    ");
    $stmt->execute([$school_id, $student['class_id'], $current_year]);
    $fee_structures = $stmt->fetchAll();

    // Get payment history for current year
    $stmt = $pdo->prepare("
        SELECT * FROM fee_payments
        WHERE student_id = ? AND year = ? AND status = 'completed'
        ORDER BY payment_date DESC
    ");
    $stmt->execute([$student_id, $current_year]);
    $payments = $stmt->fetchAll();

    // Calculate term-wise balances
    $term_data = [];
    $total_fees = 0;
    $total_paid = 0;

    foreach ($terms as $term) {
        $term_fees = 0;
        $term_paid = 0;
        $term_payments = [];

        // Get fee structure for this term
        foreach ($fee_structures as $fs) {
            if ($fs['term'] === $term) {
                $term_fees += $fs['amount'];
            }
        }

        // Get payments for this term
        foreach ($payments as $payment) {
            if ($payment['term'] === $term) {
                $term_paid += $payment['amount'];
                $term_payments[] = $payment;
            }
        }

        $term_balance = $term_fees - $term_paid;

        $term_data[$term] = [
            'fees' => $term_fees,
            'paid' => $term_paid,
            'balance' => $term_balance,
            'payments' => $term_payments
        ];

        $total_fees += $term_fees;
        $total_paid += $term_paid;
    }

    $total_balance = $total_fees - $total_paid;
    
    // Generate PDF fee statement
    require_once __DIR__ . '/../../tools/fpdf/fpdf.php';

    if (!class_exists('ParentFeeStatementPDF')) {
        class ParentFeeStatementPDF extends FPDF {
            public $schoolName;
            public $schoolAddress;
            public $schoolContact;
            public $currentYear;

            function Header() {
                $this->SetFont('Arial', 'B', 16);
                $this->SetTextColor(255, 107, 53);
                $this->Cell(0, 8, strtoupper($this->schoolName), 0, 1, 'C');

                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(95, 99, 104);
                $schoolDetails = trim($this->schoolAddress . ($this->schoolContact ? ' | ' . $this->schoolContact : ''), ' |');
                $this->Cell(0, 4, $schoolDetails, 0, 1, 'C');

                $this->SetFont('Arial', 'B', 12);
                $this->SetTextColor(32, 33, 36);
                $this->Cell(0, 6, 'OFFICIAL STUDENT FEE STATEMENT', 0, 1, 'C');

                $this->SetFont('Arial', '', 10);
                $this->SetTextColor(95, 99, 104);
                $this->Cell(0, 5, 'Academic Year: ' . $this->currentYear . ' | Date: ' . date('F j, Y'), 0, 1, 'C');

                $this->Ln(3);
                $this->SetDrawColor(255, 107, 53);
                $this->SetLineWidth(0.8);
                $this->Line(10, $this->GetY(), 200, $this->GetY());
                $this->Ln(5);
                $this->SetLineWidth(0.3);
                $this->SetTextColor(0, 0, 0);
            }

            function Footer() {
                $this->SetY(-15);
                $this->SetFont('Arial', 'I', 8);
                $this->SetTextColor(128, 128, 128);
                $this->Cell(0, 10, 'KENYA EDUHUB PARENT PORTAL - OFFICIAL FEE STATEMENT | Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
            }
        }
    }

    $pdf = new ParentFeeStatementPDF('P', 'mm', 'A4');
    $pdf->schoolName = $student['school_name'] ?? 'Kenya EduHub';
    $pdf->schoolAddress = $student['school_address'] ?? '';
    $school_contact = [];
    if (!empty($student['school_phone'])) {
        $school_contact[] = 'Phone: ' . $student['school_phone'];
    }
    if (!empty($student['school_email'])) {
        $school_contact[] = 'Email: ' . $student['school_email'];
    }
    $pdf->schoolContact = implode(' | ', $school_contact);
    $pdf->currentYear = $current_year;
    $pdf->SetTitle('Fee Statement - ' . $student['admission_number']);
    $pdf->SetAuthor($student['school_name']);
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetMargins(10, 15, 10);
    $pdf->SetAutoPageBreak(true, 18);
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetLineWidth(0.3);

    // Student Information Section
    $pdf->SetFillColor(240, 244, 249);
    $pdf->SetDrawColor(220, 224, 230);
    $pdf->Rect(10, $pdf->GetY(), 190, 25, 'DF');
    $pdf->SetXY(15, $pdf->GetY() + 3);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(90, 5, 'Candidate: ' . strtoupper($student['first_name'] . ' ' . $student['last_name']), 0, 0);
    $pdf->Cell(90, 5, 'Class: ' . $class_name, 0, 1);
    $pdf->SetX(15);
    $pdf->Cell(90, 5, 'Admission No: ' . $student['admission_number'], 0, 0);
    $pdf->Cell(90, 5, 'Parent: ' . ($parent['parent_name'] ?? '-'), 0, 1);
    $pdf->Ln(8);

    // Fee Summary Section
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(0, 8, 'FEE SUMMARY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetDrawColor(255, 107, 53);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);

    // Summary table header
    $pdf->SetFillColor(232, 236, 239);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(55, 8, 'Term', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Fees', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Paid', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'Balance', 1, 1, 'C', true);

    // Summary table data
    $pdf->SetFont('Arial', '', 9);
    foreach ($terms as $row_index => $term) {
        $data = $term_data[$term];
        $fill = ($row_index % 2 === 1);
        $pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
        $pdf->Cell(55, 7, $term, 1, 0, 'C', true);
        $pdf->Cell(45, 7, 'KES ' . number_format($data['fees'], 2), 1, 0, 'R', true);
        $pdf->Cell(45, 7, 'KES ' . number_format($data['paid'], 2), 1, 0, 'R', true);
        $pdf->SetTextColor($data['balance'] > 0 ? 197 : 19, $data['balance'] > 0 ? 34 : 115, $data['balance'] > 0 ? 31 : 51);
        $pdf->Cell(45, 7, 'KES ' . number_format($data['balance'], 2), 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
    }

    // Total row
    $pdf->SetFillColor(240, 244, 249);
    $pdf->SetDrawColor(255, 107, 53);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(55, 8, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(45, 8, 'KES ' . number_format($total_fees, 2), 1, 0, 'R', true);
    $pdf->Cell(45, 8, 'KES ' . number_format($total_paid, 2), 1, 0, 'R', true);
    $pdf->SetTextColor($total_balance > 0 ? 197 : 19, $total_balance > 0 ? 34 : 115, $total_balance > 0 ? 31 : 51);
    $pdf->Cell(45, 8, 'KES ' . number_format($total_balance, 2), 1, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->Ln(8);

    // Payment History Section
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->SetTextColor(255, 107, 53);
    $pdf->Cell(0, 8, 'PAYMENT HISTORY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetDrawColor(255, 107, 53);
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(3);

    if (empty($payments)) {
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 7, 'No payments recorded for this academic year.', 0, 1, 'C');
    } else {
        // Payment history table header
        $pdf->SetFillColor(232, 236, 239);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Term', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Fee Type', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Method', 1, 1, 'C', true);

        // Payment history data
        $pdf->SetFont('Arial', '', 8);
        foreach ($payments as $payment_index => $payment) {
            $fill = ($payment_index % 2 === 1);
            $pdf->SetFillColor($fill ? 250 : 255, $fill ? 250 : 255, $fill ? 250 : 255);
            $pdf->Cell(30, 7, date('d M Y', strtotime($payment['payment_date'])), 1, 0, 'C', true);
            $pdf->Cell(40, 7, $payment['term'], 1, 0, 'C', true);
            $pdf->Cell(35, 7, $payment['fee_type'] ?? 'Tuition', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'KES ' . number_format($payment['amount'], 2), 1, 0, 'R', true);
            $pdf->Cell(45, 7, strtoupper($payment['payment_method']), 1, 1, 'C', true);
        }
    }
    
    // Output PDF
    $safe_admission = preg_replace('/[^a-zA-Z0-9]/', '_', $student['admission_number']);
    $statement_filename = 'fee_statement_' . $safe_admission . '_' . $current_year . '.pdf';
    $statement_path = __DIR__ . '/../receipts/' . $statement_filename;
    
    // Create receipts directory if not exists
    if (!is_dir(__DIR__ . '/../receipts')) {
        mkdir(__DIR__ . '/../receipts', 0777, true);
    }
    
    $pdf->Output($statement_path, 'F');
    
    echo json_encode([
        'success' => true,
        'statement_url' => '/parents/receipts/' . $statement_filename,
        'statement_filename' => $statement_filename
    ]);
    
} catch (Exception $e) {
    error_log("Fee statement generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate fee statement: ' . $e->getMessage()]);
} catch (Error $e) {
    error_log("Fee statement generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate fee statement: ' . $e->getMessage()]);
}
