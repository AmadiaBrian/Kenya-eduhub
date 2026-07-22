<?php
// Class Fee Statement Generation API for Finance Managers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if finance manager is logged in
if (!isset($_SESSION['finance_manager_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$finance_manager_id = $_SESSION['finance_manager_id'];
$school_id = $_SESSION['school_id'];

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $class_id = $_POST['class_id'] ?? null;
    
    if (!$class_id) {
        echo json_encode(['success' => false, 'error' => 'Class ID is required']);
        exit;
    }
    
    // Get school information
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    
    // Get class information
    $stmt = $pdo->prepare("SELECT c.class_name FROM classes c WHERE c.id = ?");
    $stmt->execute([$class_id]);
    $class = $stmt->fetch();
    
    // Get all students in the class
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                          FROM students s
                          LEFT JOIN classes c ON s.class_id = c.id
                          LEFT JOIN streams st ON s.stream_id = st.id
                          WHERE s.school_id = ? AND s.class_id = ? AND s.status = 'active'
                          ORDER BY s.first_name, s.last_name");
    $stmt->execute([$school_id, $class_id]);
    $students = $stmt->fetchAll();
    
    if (empty($students)) {
        echo json_encode(['success' => false, 'error' => 'No students found in class']);
        exit;
    }
    
    // Get current year fee data for all students
    $current_year = date('Y');
    $terms = ['Term 1', 'Term 2', 'Term 3'];
    
    $class_fee_data = [];
    $total_class_fees = 0;
    $total_class_paid = 0;
    
    foreach ($students as $student) {
        $student_id = $student['id'];
        
        // Get fee structure for this student's class
        $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
        $stmt->execute([$school_id, $class_id, $current_year]);
        $fee_structures = $stmt->fetchAll();
        
        // Get payments for this student
        $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
        $stmt->execute([$student_id, $current_year]);
        $payments = $stmt->fetchAll();
        
        // Calculate term-wise balances
        $term_data = [];
        $student_total_fees = 0;
        $student_total_paid = 0;
        
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
            
            $student_total_fees += $term_fees;
            $student_total_paid += $term_paid;
        }
        
        $student_balance = $student_total_fees - $student_total_paid;
        
        $class_fee_data[] = [
            'student' => $student,
            'term_data' => $term_data,
            'total_fees' => $student_total_fees,
            'total_paid' => $student_total_paid,
            'total_balance' => $student_balance
        ];
        
        $total_class_fees += $student_total_fees;
        $total_class_paid += $student_total_paid;
    }
    
    $total_class_balance = $total_class_fees - $total_class_paid;
    
    // Generate PDF class fee statement
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($school['school_name']);
    $pdf->SetTitle('Class Fee Statement - ' . $class['class_name']);
    
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
    $pdf->Cell(0, 8, $school['school_name'], 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $school['address'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Phone: ' . $school['phone'] . ' | Email: ' . $school['email'], 0, 1, 'C');
    
    $pdf->Ln(8);
    
    // Statement Title with border
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'CLASS FEE STATEMENT', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Statement Date and Class Info
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
    
    // Class Information Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'CLASS INFORMATION', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Class:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $class['class_name'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Total Students:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, count($students), 0, 1, 'L');
    
    $pdf->Ln(8);
    
    // Class Summary Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'CLASS SUMMARY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Summary table header
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 8, 'Term', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total Fees', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total Paid', 1, 0, 'C', true);
    $pdf->Cell(50, 8, 'Total Balance', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Calculate class term totals
    $class_term_totals = [];
    foreach ($terms as $term) {
        $class_term_totals[$term] = ['fees' => 0, 'paid' => 0, 'balance' => 0];
    }
    
    foreach ($class_fee_data as $student_data) {
        foreach ($terms as $term) {
            $class_term_totals[$term]['fees'] += $student_data['term_data'][$term]['fees'];
            $class_term_totals[$term]['paid'] += $student_data['term_data'][$term]['paid'];
            $class_term_totals[$term]['balance'] += $student_data['term_data'][$term]['balance'];
        }
    }
    
    // Summary table data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($terms as $term) {
        $totals = $class_term_totals[$term];
        $pdf->Cell(50, 7, $term, 1, 0, 'C');
        $pdf->Cell(40, 7, 'KES ' . number_format($totals['fees'], 2), 1, 0, 'R');
        $pdf->Cell(40, 7, 'KES ' . number_format($totals['paid'], 2), 1, 0, 'R');
        $pdf->Cell(50, 7, 'KES ' . number_format($totals['balance'], 2), 1, 1, 'R');
    }
    
    // Total row
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(50, 8, 'GRAND TOTAL', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_class_fees, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_class_paid, 2), 1, 0, 'R', true);
    $pdf->Cell(50, 8, 'KES ' . number_format($total_class_balance, 2), 1, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // Student-wise Details Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'STUDENT-WISE DETAILS', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Student details table header
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->Cell(60, 8, 'Student', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Term 1', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Term 2', 1, 0, 'C', true);
    $pdf->Cell(35, 8, 'Term 3', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Total', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Student details data
    $pdf->SetFont('helvetica', '', 8);
    foreach ($class_fee_data as $student_data) {
        $student = $student_data['student'];
        $student_name = $student['first_name'] . ' ' . $student['last_name'];
        $admission = $student['admission_number'];
        
        $pdf->Cell(60, 7, $admission . ' - ' . substr($student_name, 0, 20), 1, 0, 'L');
        $pdf->Cell(35, 7, number_format($student_data['term_data']['Term 1']['balance'], 2), 1, 0, 'R');
        $pdf->Cell(35, 7, number_format($student_data['term_data']['Term 2']['balance'], 2), 1, 0, 'R');
        $pdf->Cell(35, 7, number_format($student_data['term_data']['Term 3']['balance'], 2), 1, 0, 'R');
        $pdf->Cell(30, 7, number_format($student_data['total_balance'], 2), 1, 1, 'R');
    }
    
    $pdf->Ln(15);
    
    // Professional Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official class fee statement generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    
    // Output PDF
    $safe_class_name = preg_replace('/[^a-zA-Z0-9]/', '_', $class['class_name']);
    $statement_filename = 'class_fee_statement_' . $safe_class_name . '_' . $current_year . '.pdf';
    $statement_path = __DIR__ . '/../receipts/' . $statement_filename;
    
    // Create receipts directory if not exists
    if (!is_dir(__DIR__ . '/../receipts')) {
        mkdir(__DIR__ . '/../receipts', 0777, true);
    }
    
    $pdf->Output($statement_path, 'F');
    
    echo json_encode([
        'success' => true,
        'statement_url' => '/finance-managers/receipts/' . $statement_filename,
        'statement_filename' => $statement_filename
    ]);
    
} catch (Exception $e) {
    error_log("Class fee statement generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate class fee statement']);
}
