<?php
// Fee Structure Document Generation API for Parents
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

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
    $terms = ['Term 1', 'Term 2', 'Term 3'];
    
    $stmt = $pdo->prepare("
        SELECT * FROM fee_structure 
        WHERE school_id = ? AND class_id = ? AND year = ? 
        ORDER BY term, fee_type
    ");
    $stmt->execute([$school_id, $student['class_id'], $current_year]);
    $fee_structures = $stmt->fetchAll();
    
    // Organize fee structures by term and fee type
    $organized_fees = [];
    $total_fees = 0;
    
    foreach ($terms as $term) {
        $term_fees = [];
        $term_total = 0;
        
        foreach ($fee_structures as $fs) {
            if ($fs['term'] === $term) {
                $term_fees[] = $fs;
                $term_total += $fs['amount'];
            }
        }
        
        $organized_fees[$term] = [
            'fees' => $term_fees,
            'total' => $term_total
        ];
        
        $total_fees += $term_total;
    }
    
    // Generate PDF fee structure document
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($student['school_name']);
    $pdf->SetTitle('Fee Structure - ' . $class_name);
    
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
    
    // Document Title with border
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'FEE STRUCTURE DOCUMENT', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Document Date and Academic Year
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Rect(15, 65, 180, 20, 'F');
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect(15, 65, 180, 20, 'D');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Document Date:', 0, 0, 'L');
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
    $pdf->Cell(140, 7, $class_name, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Parent Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $parent['parent_name'], 0, 1, 'L');
    
    $pdf->Ln(8);
    
    // Fee Structure Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'FEE STRUCTURE', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    if (empty($fee_structures)) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, 'No fee structure has been set for this class and academic year.', 0, 1, 'C');
    } else {
        foreach ($terms as $term) {
            $term_data = $organized_fees[$term];
            
            if (!empty($term_data['fees'])) {
                // Term header
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->SetFillColor(255, 102, 0);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->Cell(0, 8, $term, 0, 1, 'L', true);
                $pdf->SetTextColor(0, 0, 0);
                
                // Fee table header
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(100, 7, 'Fee Type', 1, 0, 'L', true);
                $pdf->Cell(80, 7, 'Amount (KES)', 1, 1, 'R', true);
                
                // Fee table data
                $pdf->SetFont('helvetica', '', 9);
                foreach ($term_data['fees'] as $fee) {
                    $pdf->Cell(100, 7, $fee['fee_type'], 1, 0, 'L');
                    $pdf->Cell(80, 7, number_format($fee['amount'], 2), 1, 1, 'R');
                }
                
                // Term total
                $pdf->SetFillColor(255, 102, 0);
                $pdf->SetTextColor(255, 255, 255);
                $pdf->SetFont('helvetica', 'B', 10);
                $pdf->Cell(100, 8, $term . ' Total', 1, 0, 'L', true);
                $pdf->Cell(80, 8, number_format($term_data['total'], 2), 1, 1, 'R', true);
                $pdf->SetTextColor(0, 0, 0);
                
                $pdf->Ln(5);
            }
        }
        
        // Grand total
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(100, 10, 'ANNUAL TOTAL', 1, 0, 'L', true);
        $pdf->Cell(80, 10, number_format($total_fees, 2), 1, 1, 'R', true);
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $pdf->Ln(10);
    
    // Notes Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'IMPORTANT NOTES', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 9);
    $pdf->MultiCell(0, 5, '• This fee structure is subject to change by the school administration. Please confirm with the school office for the most current fee information.', 0, 'L');
    $pdf->Ln(2);
    $pdf->MultiCell(0, 5, '• Payment deadlines and methods are as communicated by the school. Late payments may attract penalties.', 0, 'L');
    $pdf->Ln(2);
    $pdf->MultiCell(0, 5, '• For any inquiries regarding fees, please contact the school administration office.', 0, 'L');
    
    $pdf->Ln(15);
    
    // Professional Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official fee structure document generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    
    // Output PDF
    $safe_admission = preg_replace('/[^a-zA-Z0-9]/', '_', $student['admission_number']);
    $structure_filename = 'fee_structure_' . $safe_admission . '_' . $current_year . '.pdf';
    $structure_path = __DIR__ . '/../receipts/' . $structure_filename;
    
    // Create receipts directory if not exists
    if (!is_dir(__DIR__ . '/../receipts')) {
        mkdir(__DIR__ . '/../receipts', 0777, true);
    }
    
    $pdf->Output($structure_path, 'F');
    
    echo json_encode([
        'success' => true,
        'structure_url' => '/parents/receipts/' . $structure_filename,
        'structure_filename' => $structure_filename
    ]);
    
} catch (Exception $e) {
    error_log("Fee structure generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate fee structure document']);
}
