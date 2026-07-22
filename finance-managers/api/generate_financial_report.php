<?php
// Financial Report Generation API for Finance Managers
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
    $report_type = $_POST['report_type'] ?? null;
    $format = $_POST['format'] ?? 'pdf';
    $year = $_POST['year'] ?? date('Y');
    $term = $_POST['term'] ?? '';
    $class_id = $_POST['class_id'] ?? '';
    
    if (!$report_type) {
        echo json_encode(['success' => false, 'error' => 'Report type is required']);
        exit;
    }
    
    // Get school information
    $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    
    // Generate report based on type
    $report_data = [];
    
    switch ($report_type) {
        case 'daily_revenue':
            $report_data = generateDailyRevenueReport($pdo, $school_id, $year);
            $report_title = 'Daily Revenue Report';
            break;
        case 'monthly_revenue':
            $report_data = generateMonthlyRevenueReport($pdo, $school_id, $year);
            $report_title = 'Monthly Revenue Report';
            break;
        case 'class_collection':
            $report_data = generateClassCollectionReport($pdo, $school_id, $year, $term, $class_id);
            $report_title = 'Class-wise Fee Collection Report';
            break;
        case 'payment_method':
            $report_data = generatePaymentMethodReport($pdo, $school_id, $year);
            $report_title = 'Payment Method Breakdown Report';
            break;
        case 'term_summary':
            $report_data = generateTermSummaryReport($pdo, $school_id, $year, $term);
            $report_title = 'Term-wise Financial Summary';
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid report type']);
            exit;
    }
    
    // Generate output based on format
    if ($format === 'pdf') {
        $result = generatePDFReport($school, $report_title, $report_data, $year, $term);
    } elseif ($format === 'excel') {
        $result = generateExcelReport($report_title, $report_data, $year, $term);
    } elseif ($format === 'csv') {
        $result = generateCSVReport($report_title, $report_data, $year, $term);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid format']);
        exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Financial report generation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate report']);
}

// Report Generation Functions
function generateDailyRevenueReport($pdo, $school_id, $year) {
    $stmt = $pdo->prepare("
        SELECT DATE(fp.payment_date) as payment_date, 
               COUNT(*) as transaction_count,
               SUM(fp.amount) as total_amount,
               fp.payment_method
        FROM fee_payments fp 
        JOIN students s ON fp.student_id = s.id 
        WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
        GROUP BY DATE(fp.payment_date), fp.payment_method
        ORDER BY payment_date DESC, fp.payment_method
    ");
    $stmt->execute([$school_id, $year]);
    return $stmt->fetchAll();
}

function generateMonthlyRevenueReport($pdo, $school_id, $year) {
    $stmt = $pdo->prepare("
        SELECT MONTH(fp.payment_date) as month,
               MONTHNAME(fp.payment_date) as month_name,
               COUNT(*) as transaction_count,
               SUM(fp.amount) as total_amount,
               fp.payment_method
        FROM fee_payments fp 
        JOIN students s ON fp.student_id = s.id 
        WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
        GROUP BY MONTH(fp.payment_date), MONTHNAME(fp.payment_date), fp.payment_method
        ORDER BY month, fp.payment_method
    ");
    $stmt->execute([$school_id, $year]);
    return $stmt->fetchAll();
}

function generateClassCollectionReport($pdo, $school_id, $year, $term, $class_id) {
    $query = "
        SELECT c.class_name, fs.term,
               (fs.amount * COUNT(DISTINCT s.id)) as total_fees, 
               COALESCE(SUM(fp.amount), 0) as total_collected,
               ((fs.amount * COUNT(DISTINCT s.id)) - COALESCE(SUM(fp.amount), 0)) as outstanding_balance,
               COUNT(DISTINCT s.id) as student_count
        FROM classes c
        JOIN fee_structure fs ON fs.class_id = c.id AND fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
        LEFT JOIN students s ON s.class_id = c.id AND s.school_id = ? AND s.status = 'active'
        LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
        WHERE c.school_id = ?
    ";
    $params = [$school_id, $year, $school_id, $school_id];
    
    if ($term) {
        $query .= " AND fs.term = ?";
        $params[] = $term;
    }
    
    if ($class_id) {
        $query .= " AND c.id = ?";
        $params[] = $class_id;
    }
    
    $query .= " GROUP BY c.id, c.class_name, fs.term ORDER BY c.class_name, fs.term";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generatePaymentMethodReport($pdo, $school_id, $year) {
    $stmt = $pdo->prepare("
        SELECT fp.payment_method,
               COUNT(*) as transaction_count,
               SUM(fp.amount) as total_amount,
               ROUND(SUM(fp.amount) * 100.0 / (SELECT SUM(fp2.amount) FROM fee_payments fp2 JOIN students s2 ON fp2.student_id = s2.id WHERE s2.school_id = ? AND fp2.year = ? AND fp2.status = 'completed' AND (fp2.fee_type = 'Tuition' OR fp2.fee_type IS NULL)), 2) as percentage
        FROM fee_payments fp 
        JOIN students s ON fp.student_id = s.id 
        WHERE s.school_id = ? AND fp.year = ? AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
        GROUP BY fp.payment_method
        ORDER BY total_amount DESC
    ");
    $stmt->execute([$school_id, $year, $school_id, $year]);
    return $stmt->fetchAll();
}

function generateTermSummaryReport($pdo, $school_id, $year, $term) {
    $query = "
        SELECT c.class_name, fs.term,
              (fs.amount * COUNT(DISTINCT s.id)) as total_fees, 
              COALESCE(SUM(fp.amount), 0) as total_collected,
              ((fs.amount * COUNT(DISTINCT s.id)) - COALESCE(SUM(fp.amount), 0)) as outstanding_balance
        FROM classes c
        JOIN fee_structure fs ON fs.class_id = c.id AND fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
        LEFT JOIN students s ON s.class_id = c.id AND s.school_id = ? AND s.status = 'active'
        LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
        WHERE c.school_id = ?
    ";
    $params = [$school_id, $year, $school_id, $school_id];
    
    if ($term) {
        $query .= " AND fs.term = ?";
        $params[] = $term;
    }
    
    $query .= " GROUP BY c.id, c.class_name, fs.term ORDER BY c.class_name, fs.term";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function generatePDFReport($school, $report_title, $report_data, $year, $term) {
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($school['school_name']);
    $pdf->SetTitle($report_title);
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);
    
    // Header
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect(15, 15, 180, 35, 'F');
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 8, $school['school_name'], 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(0, 5, $school['address'], 0, 1, 'C');
    $pdf->Cell(0, 5, 'Phone: ' . $school['phone'] . ' | Email: ' . $school['email'], 0, 1, 'C');
    
    $pdf->Ln(8);
    
    // Report Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, $report_title, 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Report Info
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Rect(15, 65, 180, 20, 'F');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Report Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 7, date('d M Y'), 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(30, 7, 'Year:', 0, 0, 'R');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(20, 7, $year, 0, 1, 'R');
    
    if ($term) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(50, 7, 'Term:', 0, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(130, 7, $term, 0, 1, 'L');
    }
    
    $pdf->Ln(10);
    
    // Report Data Table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'REPORT DATA', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Table Header
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    
    $headers = array_keys($report_data[0] ?? []);
    $col_width = 180 / count($headers);
    
    foreach ($headers as $header) {
        $pdf->Cell($col_width, 8, ucwords(str_replace('_', ' ', $header)), 1, 0, 'C', true);
    }
    $pdf->Ln();
    $pdf->SetTextColor(0, 0, 0);
    
    // Table Data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($report_data as $row) {
        foreach ($row as $value) {
            $pdf->Cell($col_width, 7, is_numeric($value) ? number_format($value, 2) : $value, 1, 0, 'C');
        }
        $pdf->Ln();
    }
    
    $pdf->Ln(15);
    
    // Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official financial report generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    
    // Save PDF
    $safe_title = preg_replace('/[^a-zA-Z0-9]/', '_', $report_title);
    $report_filename = $safe_title . '_' . $year . '.pdf';
    $report_path = __DIR__ . '/../report_files/' . $report_filename;
    
    if (!is_dir(__DIR__ . '/../report_files')) {
        mkdir(__DIR__ . '/../report_files', 0777, true);
    }
    
    $pdf->Output($report_path, 'F');
    
    return [
        'success' => true,
        'report_url' => '/finance-managers/report_files/' . $report_filename,
        'report_filename' => $report_filename
    ];
}

function generateExcelReport($report_title, $report_data, $year, $term) {
    // Simple CSV format for Excel compatibility
    return generateCSVReport($report_title, $report_data, $year, $term);
}

function generateCSVReport($report_title, $report_data, $year, $term) {
    $safe_title = preg_replace('/[^a-zA-Z0-9]/', '_', $report_title);
    $report_filename = $safe_title . '_' . $year . '.csv';
    $report_path = __DIR__ . '/../report_files/' . $report_filename;
    
    if (!is_dir(__DIR__ . '/../report_files')) {
        mkdir(__DIR__ . '/../report_files', 0777, true);
    }
    
    $file = fopen($report_path, 'w');
    
    // Write headers
    if (!empty($report_data)) {
        fputcsv($file, array_keys($report_data[0]));
        
        // Write data
        foreach ($report_data as $row) {
            fputcsv($file, $row);
        }
    }
    
    fclose($file);
    
    return [
        'success' => true,
        'report_url' => '/finance-managers/report_files/' . $report_filename,
        'report_filename' => $report_filename
    ];
}
