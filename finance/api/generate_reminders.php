<?php
// Reminder Generation API
session_start();
require_once __DIR__ . '/../../config.php';

require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['finance_manager_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$finance_manager_id = $_SESSION['finance_manager_id'];
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Function to get SMTP settings for school
function getSMTPSettings($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE school_id = ?");
    $stmt->execute([$school_id]);
    return $stmt->fetch();
}

// Function to generate fee statement PDF for a specific term
function generateTermFeeStatement($pdo, $student_id, $school_id, $term, $year) {
    require_once __DIR__ . '/../../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Get student details
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name, sch.school_name, sch.address as school_address, sch.phone as school_phone, sch.email as school_email
                          FROM students s
                          LEFT JOIN classes c ON s.class_id = c.id
                          LEFT JOIN streams st ON s.stream_id = st.id
                          JOIN schools sch ON s.school_id = sch.id
                          WHERE s.id = ? AND s.school_id = ?");
    $stmt->execute([$student_id, $school_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        return ['success' => false, 'error' => 'Student not found'];
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
    
    // Get fee structure for the specific term
    $stmt = $pdo->prepare("
        SELECT * FROM fee_structure 
        WHERE school_id = ? AND class_id = ? AND year = ? AND term = ?
        ORDER BY fee_type
    ");
    $stmt->execute([$school_id, $student['class_id'], $year, $term]);
    $fee_structures = $stmt->fetchAll();
    
    // Get payments for the specific term
    $stmt = $pdo->prepare("
        SELECT * FROM fee_payments 
        WHERE student_id = ? AND year = ? AND term = ? AND status = 'completed' 
        ORDER BY payment_date DESC
    ");
    $stmt->execute([$student_id, $year, $term]);
    $payments = $stmt->fetchAll();
    
    // Calculate totals
    $total_fees = 0;
    $total_paid = 0;
    
    foreach ($fee_structures as $fs) {
        $total_fees += $fs['amount'];
    }
    
    foreach ($payments as $payment) {
        $total_paid += $payment['amount'];
    }
    
    $total_balance = $total_fees - $total_paid;
    
    // Create PDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($student['school_name']);
    $pdf->SetTitle('Fee Statement - ' . $student['admission_number'] . ' - ' . $term);
    
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
    $pdf->Cell(0, 12, 'FEE STATEMENT - ' . $term, 0, 1, 'C', true);
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
    $pdf->Cell(20, 7, $year, 0, 1, 'R');
    
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
    $pdf->Cell(0, 8, 'FEE SUMMARY - ' . $term, 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Summary table header
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'Fee Type', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Amount', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Paid', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Balance', 1, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    // Fee structure data
    $pdf->SetFont('helvetica', '', 9);
    foreach ($fee_structures as $fs) {
        $fee_paid = 0;
        foreach ($payments as $payment) {
            if (($payment['fee_type'] ?? 'Tuition') === $fs['fee_type']) {
                $fee_paid += $payment['amount'];
            }
        }
        $fee_balance = $fs['amount'] - $fee_paid;
        
        $pdf->Cell(60, 7, $fs['fee_type'] ?? 'Tuition', 1, 0, 'C');
        $pdf->Cell(40, 7, 'KES ' . number_format($fs['amount'], 2), 1, 0, 'R');
        $pdf->Cell(40, 7, 'KES ' . number_format($fee_paid, 2), 1, 0, 'R');
        $pdf->Cell(40, 7, 'KES ' . number_format($fee_balance, 2), 1, 1, 'R');
    }
    
    // Total row
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(60, 8, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_fees, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_paid, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 8, 'KES ' . number_format($total_balance, 2), 1, 1, 'R', true);
    
    $pdf->Ln(8);
    
    // Payment History Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'PAYMENT HISTORY - ' . $term, 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    if (empty($payments)) {
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 7, 'No payments recorded for ' . $term . '.', 0, 1, 'C');
    } else {
        // Payment history table header
        $pdf->SetFillColor(255, 102, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(30, 8, 'Date', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Fee Type', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Amount', 1, 0, 'C', true);
        $pdf->Cell(45, 8, 'Method', 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Payment history data
        $pdf->SetFont('helvetica', '', 8);
        foreach ($payments as $payment) {
            $pdf->Cell(30, 7, date('d M Y', strtotime($payment['payment_date'])), 1, 0, 'C');
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
    
    // Save PDF to temp file
    $safe_admission = preg_replace('/[^a-zA-Z0-9]/', '_', $student['admission_number']);
    $safe_term = preg_replace('/[^a-zA-Z0-9]/', '_', $term);
    $statement_filename = 'fee_statement_' . $safe_admission . '_' . $safe_term . '_' . $year . '.pdf';
    $statement_path = __DIR__ . '/../receipts/' . $statement_filename;
    
    // Create receipts directory if not exists
    if (!is_dir(__DIR__ . '/../receipts')) {
        mkdir(__DIR__ . '/../receipts', 0777, true);
    }
    
    $pdf->Output($statement_path, 'F');
    
    return ['success' => true, 'path' => $statement_path, 'filename' => $statement_filename];
}

// Function to send reminder email with PDF attachment
function sendReminderEmail($pdo, $school_id, $school_name, $student, $outstanding, $term, $year, $custom_message = '') {
    $smtp_settings = getSMTPSettings($pdo, $school_id);
    
    if (!$smtp_settings) {
        return ['success' => false, 'error' => 'SMTP settings not configured'];
    }
    
    // Use parent email if available, otherwise use student email
    $recipient_email = !empty($student['parent_email']) ? $student['parent_email'] : $student['email'];
    $recipient_name = !empty($student['parent_email']) ? 
        ($student['parent_first_name'] . ' ' . $student['parent_last_name']) : 
        ($student['first_name'] . ' ' . $student['last_name']);
    
    if (empty($recipient_email)) {
        return ['success' => false, 'error' => 'No email address available'];
    }
    
    // Generate PDF statement
    $pdf_result = generateTermFeeStatement($pdo, $student['id'], $school_id, $term, $year);
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_settings['email'];
        $mail->Password = $smtp_settings['app_password'];
        $mail->SMTPSecure = $smtp_settings['encryption'];
        $mail->Port = $smtp_settings['smtp_port'];
        
        // Recipients
        $mail->setFrom($smtp_settings['email'], $school_name);
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Attach PDF if generated successfully
        if ($pdf_result['success']) {
            $mail->addAttachment($pdf_result['path'], $pdf_result['filename']);
        }
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Reminder - ' . $school_name . ' - ' . $term;
        
        $message = $custom_message ?: "Dear " . $recipient_name . ",<br><br>
        This is a friendly reminder that your child " . $student['first_name'] . " " . $student['last_name'] . " has an outstanding balance of KES " . number_format($outstanding, 2) . " for " . $term . " (" . $year . ").<br><br>
        Please find attached the detailed fee statement for " . $term . ".<br><br>
        Please make the payment at your earliest convenience to avoid any late fees.<br><br>
        If you have already made this payment, please disregard this notice.<br><br>
        Thank you,<br>" . $school_name;
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        
        // Clean up PDF file
        if ($pdf_result['success'] && file_exists($pdf_result['path'])) {
            unlink($pdf_result['path']);
        }
        
        return ['success' => true];
    } catch (Exception $e) {
        // Clean up PDF file on error
        if ($pdf_result['success'] && file_exists($pdf_result['path'])) {
            unlink($pdf_result['path']);
        }
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

try {
    $student_ids_json = $_POST['student_ids'] ?? '[]';
    $student_ids = json_decode($student_ids_json, true);
    $reminder_type = $_POST['reminder_type'] ?? 'email';
    $message = $_POST['message'] ?? '';
    $filter_year = $_POST['year'] ?? date('Y');
    
    if (empty($student_ids)) {
        echo json_encode(['success' => false, 'error' => 'No students selected']);
        exit;
    }
    
    // Disable output buffering for streaming
    ini_set('output_buffering', 'off');
    ini_set('zlib.output_compression', false);
    while (@ob_end_flush());
    
    $pdo->beginTransaction();
    
    $sent_count = 0;
    $failed_count = 0;
    $total = count($student_ids);
    $processed = 0;
    $results = [];
    
    foreach ($student_ids as $student_id) {
        // Get student details with parent information
        $stmt = $pdo->prepare("SELECT s.*, p.email as parent_email, p.first_name as parent_first_name, p.last_name as parent_last_name
                              FROM students s
                              LEFT JOIN student_parents sp ON sp.student_id = s.id AND sp.is_primary = 1
                              LEFT JOIN parents p ON sp.parent_id = p.id
                              WHERE s.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if ($student) {
            // Get outstanding balance
            $stmt = $pdo->prepare("
                SELECT (fs.amount - COALESCE(SUM(fp.amount), 0)) as outstanding_balance, fs.term, fs.year
                FROM fee_structure fs
                LEFT JOIN fee_payments fp ON fp.student_id = ? AND fp.year = fs.year AND fp.term = fs.term AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
                WHERE fs.class_id = ? AND fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
                GROUP BY fs.term, fs.year, fs.amount
            ");
            $stmt->execute([$student_id, $student['class_id'], $school_id, $filter_year]);
            $outstanding = $stmt->fetch();
            
            if ($outstanding && $outstanding['outstanding_balance'] > 0) {
                $status = 'pending';
                
                // Send email if reminder type is email
                if ($reminder_type === 'email') {
                    $email_result = sendReminderEmail($pdo, $school_id, $school_name, $student, $outstanding['outstanding_balance'], $outstanding['term'], $filter_year, $message);
                    if ($email_result['success']) {
                        $status = 'sent';
                        $sent_count++;
                    } else {
                        $status = 'failed';
                        $failed_count++;
                        error_log("Failed to send email for student " . $student_id . ": " . $email_result['error']);
                    }
                } else {
                    $status = 'sent'; // For letter/manual, mark as sent
                    $sent_count++;
                }
                
                // Record reminder
                $stmt = $pdo->prepare("
                    INSERT INTO reminder_history (student_id, school_id, year, term, outstanding_amount, reminder_type, message, status, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([
                    $student_id, 
                    $school_id, 
                    $filter_year, 
                    $outstanding['term'], 
                    $outstanding['outstanding_balance'], 
                    $reminder_type, 
                    $message,
                    $status
                ]);
                
                $results[] = [
                    'student_id' => $student_id,
                    'student_name' => $student['first_name'] . ' ' . $student['last_name'],
                    'status' => $status,
                    'outstanding' => $outstanding['outstanding_balance']
                ];
            }
        }
        
        $processed++;
        
        // Send progress update
        $progress = round(($processed / $total) * 100);
        echo json_encode(['progress' => $progress, 'processed' => $processed, 'total' => $total, 'current_student' => $student['first_name'] . ' ' . $student['last_name'] ?? 'Unknown']) . "\n";
        flush();
    }
    
    $pdo->commit();
    
    // Send final completion message
    echo json_encode([
        'success' => true, 
        'sent' => $sent_count, 
        'failed' => $failed_count,
        'total' => $total,
        'results' => $results,
        'complete' => true
    ]) . "\n";
    flush();
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Failed to generate reminders: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to generate reminders. Please try again.']);
}
