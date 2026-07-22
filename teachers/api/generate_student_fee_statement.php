<?php
// Individual Student Fee Statement Generation API for Teachers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$teacher_id = $_SESSION['teacher_id'];
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $student_id = $_POST['student_id'] ?? null;
    
    if (!$student_id) {
        echo json_encode(['success' => false, 'error' => 'Student ID is required']);
        exit;
    }
    
    // Verify student belongs to teacher's class
    $query = "SELECT s.*, c.class_name, st.stream_name, sch.school_name, sch.address as school_address, sch.phone as school_phone, sch.email as school_email
              FROM students s
              LEFT JOIN classes c ON s.class_id = c.id
              LEFT JOIN streams st ON s.stream_id = st.id
              JOIN schools sch ON s.school_id = sch.id
              WHERE s.id = ? AND s.school_id = ?";
    $params = [$student_id, $school_id];
    
    if ($class_id) {
        $query .= " AND s.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($stream_id) {
        $query .= " AND s.stream_id = ?";
        $params[] = $stream_id;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }
    
    // Get parent information
    $stmt = $pdo->prepare("
        SELECT CONCAT(p.first_name, ' ', p.last_name) as parent_name, p.phone as parent_phone
        FROM student_parents sp
        JOIN parents p ON sp.parent_id = p.id
        WHERE sp.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $parent = $stmt->fetch();
    
    // Get current year fee structure
    $current_year = date('Y');
    $terms = ['Term 1', 'Term 2', 'Term 3'];
    
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
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($student['school_name']);
    $pdf->SetTitle('Fee Statement - ' . $student['admission_number']);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Professional Header Box
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect(15, 15, 180, 35, 'F');
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(15, 15, 180, 35, 'D');
    
    // School Header
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 8, $student['school_name'], 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $student['school_address'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Phone: ' . $student['school_phone'] . ' | Email: ' . $student['school_email'], 0, 1, 'C');
    
    $pdf->Ln(8);
    
    // Statement Title with border
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'FEE STATEMENT', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Statement Date and Academic Year
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Rect(15, 65, 180, 20, 'F');
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect(15, 65, 180, 20, 'D');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Statement Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 7, date('d M Y'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 7, 'Academic Year:', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(20, 7, $current_year, 0, 1, 'R');
    
    $pdf->Ln(10);
    
    // Student Information Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'STUDENT INFORMATION', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Student Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $student['first_name'] . ' ' . $student['last_name'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Admission No:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $student['admission_number'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Class:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $student['class_name'] . ($student['stream_name'] ? ' - ' . $student['stream_name'] : ''), 0, 1, 'L');
    
    if ($parent) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 7, 'Parent Name:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(140, 7, $parent['parent_name'], 0, 1, 'L');
    }
    
    $pdf->Ln(8);
    
    // Fee Summary Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'FEE SUMMARY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Summary table header
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 8, 'Term', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Fees', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Paid', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Balance', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Summary table data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($terms as $term) {
        $data = $term_data[$term];
        $pdf->Cell(50, 7, $term, 1, 0, 'C');
        $pdf->Cell(40, 7, 'KES ' . number_format($data['fees'], 2), 1, 0, 'R');
        $pdf->Cell(40, 7, 'KES ' . number_format($data['paid'], 2), 1, 0, 'R');
        $pdf->Cell(50, 7, 'KES ' . number_format($data['balance'], 2), 1, 1, 'R');
    }
    
    // Total row
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 8, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_fees, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_paid, 2), 1, 0, 'R', true);
    $pdf->Cell(50, 8, 'KES ' . number_format($total_balance, 2), 1, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // Payment History Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'PAYMENT HISTORY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    if (empty($payments)) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, 'No payments recorded for this academic year.', 0, 1, 'C');
    } else {
        // Payment history table header
        $pdf->SetFillColor(255, 102, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Term', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Fee Type', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Method', 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Payment history data
        $pdf->SetFont('helvetica', '', 8);
        foreach ($payments as $payment) {
            $pdf->Cell(30, 7, date('d M Y', strtotime($payment['payment_date'])), 1, 0, 'C');
            $pdf->Cell(40, 7, $payment['term'], 1, 0, 'C');
            $pdf->Cell(35, 7, $payment['fee_type'] ?? 'Tuition', 1, 0, 'C');
            $pdf->Cell(40, 7, 'KES ' . number_format($payment['amount'], 2), 1, 0, 'R');
            $pdf->Cell(45, 7, strtoupper($payment['payment_method']), 1, 1, 'C');
        }
    }
    
    $pdf->Ln(15);
    
    // Professional Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official fee statement generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    
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
        'statement_url' => '/teachers/receipts/' . $statement_filename,
        'statement_filename' => $statement_filename
    ]);
    
} catch (Exception $e) {
    error_log("Student fee statement generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate fee statement']);
}
