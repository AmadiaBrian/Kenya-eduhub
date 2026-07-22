<?php
// Receipt Generation API for Schools
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if school is logged in
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $payment_id = $_POST['payment_id'] ?? null;
    
    if (!$payment_id) {
        echo json_encode(['success' => false, 'error' => 'Payment ID is required']);
        exit;
    }
    
    // Get payment details
    $stmt = $pdo->prepare("
        SELECT fp.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.admission_number, 
               CONCAT(p.first_name, ' ', p.last_name) as parent_name, p.phone as parent_phone, 
               sch.school_name, sch.address as school_address, sch.phone as school_phone, sch.email as school_email
        FROM fee_payments fp
        JOIN students s ON fp.student_id = s.id
        JOIN parents p ON s.parent_id = p.id
        JOIN schools sch ON s.school_id = sch.id
        WHERE fp.id = ? AND s.school_id = ? AND fp.status = 'completed'
    ");
    $stmt->execute([$payment_id, $school_id]);
    $payment = $stmt->fetch();
    
    if (!$payment) {
        echo json_encode(['success' => false, 'error' => 'Payment not found or not completed']);
        exit;
    }
    
    // Generate receipt number if not exists
    if (empty($payment['receipt_number']) || strpos($payment['receipt_number'], 'FEE-') === 0) {
        $receipt_number = generateSequentialReceiptNumber($pdo, $school_id);
        $stmt = $pdo->prepare("UPDATE fee_payments SET receipt_number = ? WHERE id = ?");
        $stmt->execute([$receipt_number, $payment_id]);
        $payment['receipt_number'] = $receipt_number;
    }
    
    // Generate PDF receipt
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($payment['school_name']);
    $pdf->SetTitle('Receipt #' . $payment['receipt_number']);
    
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
    $pdf->Cell(0, 8, $payment['school_name'], 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $payment['school_address'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Phone: ' . $payment['school_phone'] . ' | Email: ' . $payment['school_email'], 0, 1, 'C');
    
    $pdf->Ln(8);
    
    // Receipt Title with border
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'OFFICIAL RECEIPT', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Receipt Number and Date in a box
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Rect(15, 65, 180, 20, 'F');
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect(15, 65, 180, 20, 'D');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Receipt No:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 7, $payment['receipt_number'], 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 7, 'Date:', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(20, 7, date('d M Y', strtotime($payment['payment_date'])), 0, 1, 'R');
    
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
    $pdf->Cell(140, 7, $payment['student_name'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Admission No:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $payment['admission_number'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Parent Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $payment['parent_name'], 0, 1, 'L');
    
    $pdf->Ln(8);
    
    // Payment Details Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'PAYMENT DETAILS', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Amount in highlighted box
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(50, 12, 'AMOUNT PAID', 0, 0, 'L', true);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(130, 12, 'KES ' . number_format($payment['amount'], 2), 0, 1, 'R', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Payment Method:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, strtoupper($payment['payment_method']), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Fee Type:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $payment['fee_type'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Term:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $payment['term'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Academic Year:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $payment['year'], 0, 1, 'L');
    
    if (!empty($payment['transaction_id'])) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(40, 7, 'Transaction ID:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(140, 7, $payment['transaction_id'], 0, 1, 'L');
    }
    
    $pdf->Ln(15);
    
    // Professional Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official receipt generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    
    // Output PDF
    $receipt_filename = 'receipt_' . $payment['receipt_number'] . '.pdf';
    $receipt_path = __DIR__ . '/../receipts/' . $receipt_filename;
    
    // Create receipts directory if not exists
    if (!is_dir(__DIR__ . '/../receipts')) {
        mkdir(__DIR__ . '/../receipts', 0777, true);
    }
    
    $pdf->Output($receipt_path, 'F');
    
    echo json_encode([
        'success' => true,
        'receipt_url' => '/schools/receipts/' . $receipt_filename,
        'receipt_number' => $payment['receipt_number']
    ]);
    
} catch (Exception $e) {
    error_log("Receipt generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate receipt']);
}

// Function to generate sequential receipt number
function generateSequentialReceiptNumber(PDO $pdo, int $school_id): string {
    $year = date('Y');
    $prefix = 'RCPT-' . $school_id . '-' . $year;
    
    // Get the last receipt number for this school and year
    $stmt = $pdo->prepare("
        SELECT receipt_number 
        FROM fee_payments 
        WHERE school_id = ? 
        AND receipt_number LIKE ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$school_id, $prefix . '%']);
    $last_receipt = $stmt->fetch();
    
    $sequence = 1;
    if ($last_receipt && preg_match('/' . preg_quote($prefix, '/') . '-(\d+)$/', $last_receipt['receipt_number'], $matches)) {
        $sequence = (int)$matches[1] + 1;
    }
    
    return $prefix . '-' . str_pad($sequence, 6, '0', STR_PAD_LEFT);
}
