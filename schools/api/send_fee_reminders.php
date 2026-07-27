<?php
// API endpoint to send fee reminders to parents
session_start();
require_once '../../config.php';
require_once '../../includes/helpers.php';
require_once '../../PHPMailer/src/PHPMailer.php';
require_once '../../PHPMailer/src/SMTP.php';
require_once '../../PHPMailer/src/Exception.php';
require_once '../../vendor/tecnickcom/tcpdf/tcpdf.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];

// Get school name
$stmt = $pdo->prepare("SELECT school_name FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();
$school_name = $school['school_name'] ?? 'School';

// Get SMTP settings
$stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE school_id = ?");
$stmt->execute([$school_id]);
$smtp_settings = $stmt->fetch();

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$send_to_all = $input['send_to_all'] ?? false;
$students = $input['students'] ?? [];
$term = $input['term'] ?? null;
$year = $input['year'] ?? null;

error_log("Send fee reminders: send_to_all=$send_to_all, students_count=" . count($students) . ", term=$term, year=$year");

// If students are sent with fee details, use them directly (from frontend selection)
if (!empty($students) && isset($students[0]['fee_type'])) {
    error_log("Using student data from frontend selection");
    // Students already have fee details from the selected rows
    // No need to recalculate
} elseif ($send_to_all) {
    // Get all students with outstanding balances using teachers' proven method
    try {
        $current_year = $year ?? date('Y');
        $filter_term = $term ?? null;
        
        // Get all active students
        $stmt = $pdo->prepare("
            SELECT s.id as student_id, s.admission_number as admission_number, 
                   CONCAT(s.first_name, ' ', s.last_name) as student_name,
                   c.class_name, c.id as class_id
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.school_id = ? AND s.status = 'active'
            ORDER BY s.admission_number
        ");
        $stmt->execute([$school_id]);
        $all_students = $stmt->fetchAll();
        
        $students = [];
        
        // For each student, get their fee structures and calculate balances
        foreach ($all_students as $student) {
            $student_id = $student['student_id'];
            $class_id = $student['class_id'];
            
            // Get fee structures for this student's class in selected year
            $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
            $stmt->execute([$school_id, $class_id, $current_year]);
            $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get payments for this student in selected year
            $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
            $stmt->execute([$student_id, $current_year]);
            $payments = $stmt->fetchAll();
            
            // Calculate balance for each fee structure
            foreach ($fee_structures as $fs) {
                $fs_term = $fs['term'];
                $fs_year = $fs['year'];
                $fs_fee_type = $fs['fee_type'];
                $fs_amount = $fs['amount'];
                
                // If term filter is set, only include matching terms
                if ($filter_term && $fs_term !== $filter_term) {
                    continue;
                }
                
                // Calculate paid amount for this specific fee structure
                $paid_amount = 0;
                foreach ($payments as $payment) {
                    if ($payment['term'] === $fs_term && 
                        $payment['year'] == $fs_year && 
                        ($payment['fee_type'] === $fs_fee_type || ($payment['fee_type'] === null && $fs_fee_type === 'Tuition'))) {
                        $paid_amount += $payment['amount'];
                    }
                }
                
                $balance = $fs_amount - $paid_amount;
                
                // Only include if there's a balance due
                if ($balance > 0) {
                    $students[] = [
                        'student_id' => $student_id,
                        'admission_number' => $student['admission_number'],
                        'student_name' => $student['student_name'],
                        'class_name' => $student['class_name'],
                        'term' => $fs_term,
                        'year' => $fs_year,
                        'fee_type' => $fs_fee_type,
                        'fee_amount' => $fs_amount,
                        'paid_amount' => $paid_amount,
                        'balance' => $balance
                    ];
                }
            }
        }
        
        error_log("Total students with balances: " . count($students));
        
        // Get payment history for each student by fee type
        foreach ($students as &$student) {
            $student_id = $student['student_id'];
            $term = $student['term'];
            $year = $student['year'];
            $fee_type = $student['fee_type'];
            
            $stmt = $pdo->prepare("
                SELECT payment_date, amount, payment_method, receipt_number, term, fee_type
                FROM fee_payments 
                WHERE student_id = ? AND status = 'completed'
                AND term = ? AND year = ? 
                AND (fee_type = ? OR (fee_type IS NULL AND ? = 'Tuition'))
                ORDER BY payment_date DESC
            ");
            $stmt->execute([$student_id, $term, $year, $fee_type, $fee_type]);
            $student['payments'] = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch all students with balances: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Failed to fetch students']);
        exit;
    }
}

if (empty($students)) {
    echo json_encode(['success' => false, 'error' => 'No students with outstanding balances found']);
    exit;
}

$sent_count = 0;
$failed_count = 0;

try {
    // Group students by parent to avoid duplicate emails
    $parents_map = [];
    
    // If students have fee details from frontend, use them directly
    if (!empty($students) && isset($students[0]['fee_type'])) {
        error_log("Using fee details from frontend selection");
        
        foreach ($students as $student) {
            $student_id = $student['student_id'];
            
            // Get student's parents
            $stmt = $pdo->prepare("
                SELECT p.id, p.first_name, p.last_name, p.email, p.phone 
                FROM parents p
                JOIN student_parents sp ON p.id = sp.parent_id
                WHERE sp.student_id = ? AND p.school_id = ?
            ");
            $stmt->execute([$student_id, $school_id]);
            $parents = $stmt->fetchAll();
            
            if (empty($parents)) {
                error_log("No parents found for student ID: $student_id");
                $failed_count++;
                continue;
            }
            
            foreach ($parents as $parent) {
                if (!empty($parent['email'])) {
                    $parent_key = $parent['email'];
                    if (!isset($parents_map[$parent_key])) {
                        $parents_map[$parent_key] = [
                            'parent' => $parent,
                            'students' => []
                        ];
                    }
                    
                    // Use the fee details from the selected row
                    $record_key = $student_id . '_' . $student['fee_type'] . '_' . $student['term'] . '_' . $student['year'];
                    if (!isset($parents_map[$parent_key]['students'][$record_key])) {
                        $parents_map[$parent_key]['students'][$record_key] = [
                            'student_id' => $student_id,
                            'admission_number' => $student['admission_number'],
                            'student_name' => $student['student_name'],
                            'fee_type' => $student['fee_type'],
                            'term' => $student['term'],
                            'year' => $student['year'],
                            'fee_amount' => $student['fee_amount'],
                            'paid_amount' => $student['paid_amount'],
                            'balance' => $student['balance']
                        ];
                    }
                }
            }
        }
    } else {
        // Original logic for send_to_all or when fee details not provided
        $student_totals = [];
        
        foreach ($students as $student) {
            $student_id = $student['student_id'];
            $term = $student['term'];
            $year = $student['year'];
            $fee_type = $student['fee_type'];
            
            $student_key = $student_id . '_' . $term . '_' . $year;
            
            if (!isset($student_totals[$student_key])) {
                $student_totals[$student_key] = [
                    'student_id' => $student_id,
                    'admission_number' => $student['admission_number'],
                    'student_name' => $student['student_name'],
                    'term' => $term,
                    'year' => $year,
                    'total_balance' => 0,
                    'fee_details' => []
                ];
            }
            
            $student_totals[$student_key]['total_balance'] += $student['balance'];
            $student_totals[$student_key]['fee_details'][] = [
                'fee_type' => $fee_type,
                'fee_amount' => $student['fee_amount'],
                'paid_amount' => $student['paid_amount'],
                'balance' => $student['balance']
            ];
        }
        
        foreach ($student_totals as $student_key => $student_data) {
            $student_id = $student_data['student_id'];
            
            $stmt = $pdo->prepare("
                SELECT p.id, p.first_name, p.last_name, p.email, p.phone 
                FROM parents p
                JOIN student_parents sp ON p.id = sp.parent_id
                WHERE sp.student_id = ? AND p.school_id = ?
            ");
            $stmt->execute([$student_id, $school_id]);
            $parents = $stmt->fetchAll();
            
            if (empty($parents)) {
                error_log("No parents found for student ID: $student_id");
                $failed_count++;
                continue;
            }
            
            foreach ($parents as $parent) {
                if (!empty($parent['email'])) {
                    $parent_key = $parent['email'];
                    if (!isset($parents_map[$parent_key])) {
                        $parents_map[$parent_key] = [
                            'parent' => $parent,
                            'students' => []
                        ];
                    }
                    
                    if (!isset($parents_map[$parent_key]['students'][$student_key])) {
                        $parents_map[$parent_key]['students'][$student_key] = [
                            'student_id' => $student_id,
                            'admission_number' => $student_data['admission_number'],
                            'student_name' => $student_data['student_name'],
                            'term' => $student_data['term'],
                            'year' => $student_data['year'],
                            'total_balance' => $student_data['total_balance'],
                            'fee_details' => $student_data['fee_details']
                        ];
                    }
                }
            }
        }
    }
    
    // Convert students array back to indexed
    foreach ($parents_map as $email => &$data) {
        $data['students'] = array_values($data['students']);
    }
    
    // Send one email per parent with all their children and individual fee statement
    foreach ($parents_map as $email => $data) {
        $parent = $data['parent'];
        $parent_students = $data['students'];
        
        // Get full student data for this parent's children
        $parent_student_ids = array_column($parent_students, 'admission_number');
        $parent_student_data = [];
        
        foreach ($parent_students as $student) {
            // Get student details from database
            $stmt = $pdo->prepare("
                SELECT s.id as student_id, s.admission_number, s.first_name, s.last_name, s.class_id,
                       c.class_name, sch.school_name, sch.address as school_address, 
                       sch.phone as school_phone, sch.email as school_email
                FROM students s
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN schools sch ON s.school_id = sch.id
                WHERE s.admission_number = ? AND s.school_id = ?
            ");
            $stmt->execute([$student['admission_number'], $school_id]);
            $student_data = $stmt->fetch();
            
            if ($student_data) {
                // Add fee details from the selected record
                $student_data['fee_type'] = $student['fee_type'] ?? null;
                $student_data['term'] = $student['term'] ?? null;
                $student_data['year'] = $student['year'] ?? null;
                $student_data['fee_amount'] = $student['fee_amount'] ?? null;
                $student_data['paid_amount'] = $student['paid_amount'] ?? null;
                $student_data['balance'] = $student['balance'] ?? null;
                $parent_student_data[] = $student_data;
            }
        }
        
        // Generate individual fee statement PDF for this parent's children
        $pdf_content = generateIndividualFeeStatement($parent_student_data, $school_name, $parent);
        $pdf_filename = "fee_statement_{$parent['last_name']}_{$parent['first_name']}_" . date('Y-m-d_H-i-s') . ".pdf";
        
        // Save PDF to temporary file for attachment
        $temp_pdf_path = sys_get_temp_dir() . '/' . $pdf_filename;
        file_put_contents($temp_pdf_path, $pdf_content);
        
        // Send email using PHPMailer with SMTP
        $mail = new PHPMailer(true);
        
        try {
            if ($smtp_settings) {
                // Use SMTP settings
                $mail->isSMTP();
                $mail->Host = $smtp_settings['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_settings['email'];
                $mail->Password = $smtp_settings['app_password'];
                $mail->SMTPSecure = $smtp_settings['encryption'];
                $mail->Port = $smtp_settings['smtp_port'];
            }
            
            $mail->setFrom($smtp_settings['email'] ?? 'noreply@kenyaeduhub.com', $school_name);
            $mail->addAddress($parent['email'], "{$parent['first_name']} {$parent['last_name']}");
            
            // Attach PDF from file
            $mail->addAttachment($temp_pdf_path, $pdf_filename);
            
            // Build student list for email body
            $student_list = '';
            $total_balance = 0;
            foreach ($parent_students as $student) {
                $student_list .= "<li><strong>{$student['student_name']}</strong> (Adm: {$student['admission_number']}) - Balance: KES " . number_format($student['balance']) . "</li>";
                $total_balance += $student['balance'];
            }
            
            $mail->Subject = 'Fee Statement for Your Child - ' . date('F j, Y');
            
            // Build fee table for email body (matching teachers/fees design)
            $feeTable = "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            $feeTable .= "<tr style='background: #FF6B35; color: white;'>";
            $feeTable .= "<th>Admission</th><th>Name</th><th>Term</th><th>Fee Type</th><th>Fees</th><th>Paid</th><th>Balance</th><th>Status</th>";
            $feeTable .= "</tr>";
            
            $totalFeesSum = 0;
            $totalPaidSum = 0;
            $totalBalanceSum = 0;
            $paidCount = 0;
            $unpaidCount = 0;
            $partialCount = 0;
            
            foreach ($parent_students as $student) {
                $feeTable .= "<tr>";
                $feeTable .= "<td>{$student['admission_number']}</td>";
                $feeTable .= "<td>{$student['student_name']}</td>";
                $feeTable .= "<td>{$student['term']}</td>";
                $feeTable .= "<td>{$student['fee_type']}</td>";
                $feeTable .= "<td>KES " . number_format($student['fee_amount']) . "</td>";
                $feeTable .= "<td>KES " . number_format($student['paid_amount']) . "</td>";
                $feeTable .= "<td>KES " . number_format($student['balance']) . "</td>";
                $feeTable .= "<td>" . ($student['balance'] <= 0 ? 'PAID' : 'BALANCE DUE') . "</td>";
                $feeTable .= "</tr>";
                
                $totalFeesSum += $student['fee_amount'];
                $totalPaidSum += $student['paid_amount'];
                $totalBalanceSum += $student['balance'];
                
                if ($student['balance'] <= 0) {
                    $paidCount++;
                } elseif ($student['paid_amount'] > 0) {
                    $partialCount++;
                } else {
                    $unpaidCount++;
                }
            }
            $feeTable .= "</table>";
            
            $currentDate = date('F j, Y');
            $currentYear = date('Y');
            $parentName = $parent['first_name'] . ' ' . $parent['last_name'];
            
            $mail->isHTML(true);
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>
                    <div style='background: #FF6B35; color: white; padding: 20px; text-align: center;'>
                        <h2 style='margin: 0;'>$school_name</h2>
                        <p style='margin: 5px 0 0 0; opacity: 0.9;'>Kenya EduHub</p>
                    </div>
                    <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #FF6B35;'>
                        <p style='margin: 0; font-size: 12px; color: #666;'>
                            <strong>To:</strong> $parentName<br>
                            <strong>Date:</strong> $currentDate<br>
                            <strong>Academic Year:</strong> $currentYear
                        </p>
                    </div>
                    <div style='padding: 20px; background: white;'>
                        <p>Dear $parentName,</p>
                        <p>Please find below the fee statement for your child(ren) for the academic year <strong>$currentYear</strong>.</p>
                        
                        <h3 style='color: #FF6B35;'>STUDENT FEE DETAILS</h3>
                        $feeTable
                        
                        <h3 style='color: #FF6B35; margin-top: 20px;'>SUMMARY</h3>
                        <p>
                            <strong>Total Children:</strong> " . count($parent_students) . "<br>
                            <strong>Fully Paid:</strong> $paidCount<br>
                            <strong>Partial Payment:</strong> $partialCount<br>
                            <strong>Not Paid:</strong> $unpaidCount<br>
                            <strong>Total Fees:</strong> KES " . number_format($totalFeesSum) . "<br>
                            <strong>Total Paid:</strong> KES " . number_format($totalPaidSum) . "<br>
                            <strong>Total Balance:</strong> KES " . number_format($totalBalanceSum) . "
                        </p>
                        
                        <p>Attached is the detailed fee statement for your reference.</p>
                        <p>For payment inquiries, please contact the school administration.</p>
                        <p>Thank you for your cooperation.</p>
                    </div>
                    <div style='background: #f1f3f4; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                        <p style='margin: 0;'>This email was sent from $school_name via Kenya EduHub</p>
                        <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " $school_name. All rights reserved.</p>
                    </div>
                </div>
            ";
            
            $mail->send();
            $sent_count++;
            error_log("Fee statement email sent to: {$parent['email']} for " . count($parent_students) . " student(s)");
            
            // Clean up temporary PDF file
            if (file_exists($temp_pdf_path)) {
                unlink($temp_pdf_path);
            }
        } catch (Exception $e) {
            $failed_count++;
            error_log("Failed to send fee statement email to: {$parent['email']} - Error: " . $mail->ErrorInfo);
            
            // Clean up temporary PDF file even on error
            if (file_exists($temp_pdf_path)) {
                unlink($temp_pdf_path);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'sent_count' => $sent_count,
        'failed_count' => $failed_count,
        'message' => "Sent $sent_count reminders, $failed_count failed"
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in send_fee_reminders: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("General error in send_fee_reminders: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

// Function to generate PDF report
function generateFeeReminderPDF($students, $school_name) {
    global $pdo, $school_id;
    
    // Get school information
    try {
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
    } catch (PDOException $e) {
        $school = [
            'school_name' => $school_name,
            'address' => '',
            'phone' => '',
            'email' => ''
        ];
    }
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($school['school_name']);
    $pdf->SetTitle('Fee Statement Report');
    
    // Set margins
    $pdf->SetMargins(12, 12, 12);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 20);
    
    // Add a page
    $pdf->AddPage();
    
    // Professional Header Box
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Rect(15, 15, 180, 35, 'F');
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Rect(15, 15, 180, 35, 'D');
    
    // School Header
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 8, $school['school_name'], 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 9);
    if (!empty($school['address'])) {
        $pdf->Cell(0, 5, $school['address'], 0, 1, 'C');
    }
    if (!empty($school['phone']) || !empty($school['email'])) {
        $contact_info = '';
        if (!empty($school['phone'])) {
            $contact_info .= 'Phone: ' . $school['phone'];
        }
        if (!empty($school['email'])) {
            if (!empty($contact_info)) {
                $contact_info .= ' | ';
            }
            $contact_info .= 'Email: ' . $school['email'];
        }
        $pdf->Cell(0, 5, $contact_info, 0, 1, 'C');
    }
    
    $pdf->Ln(8);
    
    // Statement Title with border
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetFillColor(255, 102, 0);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(0, 12, 'FEE PAYMENT REMINDER', 0, 1, 'C', true);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(5);
    
    // Statement Date
    $pdf->SetFillColor(248, 249, 250);
    $pdf->Rect(15, 65, 180, 15, 'F');
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->Rect(15, 65, 180, 15, 'D');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(50, 7, 'Statement Date:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(130, 7, date('d M Y'), 0, 1, 'L');
    
    $pdf->Ln(10);
    
    // Group students by student_id to show fee types in sections
    $grouped_students = [];
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        
        if (!isset($grouped_students[$student_id])) {
            $grouped_students[$student_id] = [
                'admission_number' => $student['admission_number'],
                'student_name' => $student['student_name'],
                'class_name' => $student['class_name'],
                'fee_types' => []
            ];
        }
        
        $grouped_students[$student_id]['fee_types'][] = $student;
    }
    
    // Display each student with their fee types in sections
    $row = 0;
    foreach ($grouped_students as $student_id => $student_data) {
        $adm_no = $student_data['admission_number'];
        $student_name = $student_data['student_name'];
        $class_name = $student_data['class_name'];
        
        // Student name header above table
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(255, 102, 0);
        $pdf->Cell(0, 8, "$student_name ($adm_no) - $class_name", 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(3);
        
        // Table header for this student
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(18, 8, 'Term', 1, 0, 'C', true);
        $pdf->Cell(18, 8, 'Year', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Fee Type', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Fee Amt', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Paid', 1, 0, 'C', true);
        $pdf->Cell(25, 8, 'Balance', 1, 0, 'C', true);
        $pdf->Cell(18, 8, 'Status', 1, 1, 'C', true);
        
        // Fee type rows for this student
        $pdf->SetFont('helvetica', '', 8);
        foreach ($student_data['fee_types'] as $fee_type_data) {
            $term = $fee_type_data['term'];
            $year = $fee_type_data['year'];
            $fee_type = $fee_type_data['fee_type'] ?? 'Tuition';
            $fee_amount = $fee_type_data['fee_amount'];
            $paid_amount = $fee_type_data['paid_amount'];
            $balance = $fee_type_data['balance'];
            $status = $balance <= 0 ? 'PAID' : 'BALANCE DUE';
            
            // Alternate row colors
            if ($row % 2 == 0) {
                $pdf->SetFillColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(248, 248, 248);
            }
            
            $pdf->Cell(18, 7, $term, 1, 0, 'C', true);
            $pdf->Cell(18, 7, $year, 1, 0, 'C', true);
            $pdf->Cell(30, 7, substr($fee_type, 0, 15), 1, 0, 'L', true);
            $pdf->Cell(25, 7, number_format($fee_amount), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($paid_amount), 1, 0, 'R', true);
            $pdf->Cell(25, 7, number_format($balance), 1, 0, 'R', true);
            
            // Status with color
            if ($balance <= 0) {
                $pdf->SetFillColor(0, 128, 0);
                $pdf->SetTextColor(255, 255, 255);
            } else {
                $pdf->SetFillColor(255, 102, 0);
                $pdf->SetTextColor(255, 255, 255);
            }
            $pdf->Cell(18, 7, $status, 1, 1, 'C', true);
            
            $pdf->SetTextColor(0, 0, 0);
            
            // Add payment history for this fee type
            if (!empty($fee_type_data['payments'])) {
                $pdf->Ln(2);
                $pdf->SetFillColor($row % 2 == 0 ? 255 : 248, $row % 2 == 0 ? 255 : 248, $row % 2 == 0 ? 255 : 248);
                $pdf->SetFont('helvetica', '', 7);
                $pdf->Cell(0, 5, '  Payment History (' . $fee_type . ' - ' . $term . '):', 0, 1, 'L', true);
                
                // Payment history table for this fee type
                $pdf->SetFillColor(245, 245, 245);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetFont('helvetica', 'B', 7);
                $pdf->Cell(25, 5, 'Date', 1, 0, 'C', true);
                $pdf->Cell(25, 5, 'Method', 1, 0, 'C', true);
                $pdf->Cell(30, 5, 'Receipt No', 1, 0, 'C', true);
                $pdf->Cell(25, 5, 'Amount', 1, 0, 'C', true);
                $pdf->Cell(25, 5, 'Term', 1, 0, 'C', true);
                $pdf->Cell(29, 5, 'Fee Type', 1, 1, 'C', true);
                
                $pdf->SetFont('helvetica', '', 7);
                foreach ($fee_type_data['payments'] as $payment) {
                    $payment_date = date('d M Y', strtotime($payment['payment_date']));
                    $amount = number_format($payment['amount']);
                    $method = strtoupper($payment['payment_method'] ?? 'N/A');
                    $receipt = $payment['receipt_number'] ?? 'N/A';
                    $payment_term = $payment['term'] ?? 'N/A';
                    $payment_fee_type = $payment['fee_type'] ?? 'Tuition';
                    
                    // Color code payment methods
                    if (strtoupper($method) === 'M-PESA') {
                        $pdf->SetFillColor(230, 255, 230);
                    } elseif (strtoupper($method) === 'CASH') {
                        $pdf->SetFillColor(255, 245, 230);
                    } elseif (strtoupper($method) === 'BANK TRANSFER') {
                        $pdf->SetFillColor(230, 240, 255);
                    } else {
                        $pdf->SetFillColor(255, 255, 255);
                    }
                    
                    $pdf->Cell(25, 5, $payment_date, 1, 0, 'C', true);
                    $pdf->Cell(25, 5, $method, 1, 0, 'C', true);
                    $pdf->Cell(30, 5, $receipt, 1, 0, 'C', true);
                    $pdf->Cell(25, 5, 'KES ' . $amount, 1, 0, 'R', true);
                    $pdf->Cell(25, 5, $payment_term, 1, 0, 'C', true);
                    $pdf->Cell(29, 5, substr($payment_fee_type, 0, 10), 1, 1, 'C', true);
                }
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Ln(3);
            }
            
            $row++;
        }
        
        // Add separator between students
        $pdf->Ln(5);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(5);
    }
    
    // Summary Section
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'SUMMARY', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    // Calculate totals from grouped students
    $total_students = count($grouped_students);
    $total_fees = 0;
    $total_paid = 0;
    $total_balance = 0;
    $paid_count = 0;
    $balance_due_count = 0;
    
    foreach ($grouped_students as $student_data) {
        $student_total_fees = 0;
        $student_total_paid = 0;
        $student_has_balance = false;
        
        foreach ($student_data['fee_types'] as $fee_type_data) {
            $student_total_fees += $fee_type_data['fee_amount'] ?? 0;
            $student_total_paid += $fee_type_data['paid_amount'] ?? 0;
            if ($fee_type_data['balance'] > 0) {
                $student_has_balance = true;
            }
        }
        
        $total_fees += $student_total_fees;
        $total_paid += $student_total_paid;
        $student_balance = $student_total_fees - $student_total_paid;
        $total_balance += $student_balance;
        
        if ($student_balance > 0) {
            $balance_due_count++;
        } else {
            $paid_count++;
        }
    }
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total Students:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, $total_students, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total Fees:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'KES ' . number_format($total_fees), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total Paid:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'KES ' . number_format($total_paid), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Total Balance Due:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, 'KES ' . number_format($total_balance), 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Fully Paid:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, $paid_count, 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(60, 7, 'Balance Due:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 7, $balance_due_count, 0, 1, 'L');
    
    // Footer
    $pdf->Ln(15);
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official fee statement generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    if (!empty($school['phone'])) {
        $pdf->Cell(0, 5, 'Contact: ' . $school['phone'], 0, 1, 'C');
    }
    
    return $pdf->Output('', 'S');
}

// Function to generate individual fee statement for a parent's children
function generateIndividualFeeStatement($students, $school_name, $parent) {
    global $pdo, $school_id;
    
    $current_year = date('Y');
    $terms = ['Term 1', 'Term 2', 'Term 3'];
    
    // Get school information
    try {
        $stmt = $pdo->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
    } catch (PDOException $e) {
        $school = [
            'school_name' => $school_name,
            'address' => '',
            'phone' => '',
            'email' => ''
        ];
    }
    
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Kenya EduHub');
    $pdf->SetAuthor($school['school_name']);
    $pdf->SetTitle('Fee Statement - Parent');
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks with larger bottom margin to prevent footer overflow
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Professional Header Box (EXACT teachers/fees design)
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
    
    // Statement Title with border (EXACT teachers/fees design)
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
    
    // Parent Information Section (EXACT teachers/fees design)
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'PARENT INFORMATION', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Parent Name:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $parent['first_name'] . ' ' . $parent['last_name'], 0, 1, 'L');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 7, 'Email:', 0, 0, 'L');
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(140, 7, $parent['email'], 0, 1, 'L');
    
    $pdf->Ln(8);
    
    // Generate fee statement for each student
    $grand_total_fees = 0;
    $grand_total_paid = 0;
    
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        $class_id = $student['class_id'];
        
        // If fee details are provided from selection, use them directly
        if (isset($student['fee_type']) && isset($student['term']) && isset($student['year'])) {
            $fee_type = $student['fee_type'];
            $term = $student['term'];
            $year = $student['year'];
            $fee_amount = $student['fee_amount'];
            $paid_amount = $student['paid_amount'];
            $balance = $student['balance'];
            
            $term_data = [
                $term => [
                    'fees' => $fee_amount,
                    'paid' => $paid_amount,
                    'balance' => $balance,
                    'fee_type' => $fee_type
                ]
            ];
            
            $total_fees = $fee_amount;
            $total_paid = $paid_amount;
            $grand_total_fees += $total_fees;
            $grand_total_paid += $total_paid;
            
            // Get payment history for this student (even when fee details provided)
            $stmt = $pdo->prepare("
                SELECT * FROM fee_payments 
                WHERE student_id = ? AND year = ? AND status = 'completed' 
                ORDER BY payment_date DESC
            ");
            $stmt->execute([$student_id, $current_year]);
            $payments = $stmt->fetchAll();
        } else {
            // Original logic for when fee details not provided
            // Get fee structure for this student
            $stmt = $pdo->prepare("
                SELECT * FROM fee_structure 
                WHERE school_id = ? AND class_id = ? AND year = ? 
                ORDER BY term, fee_type
            ");
            $stmt->execute([$school_id, $class_id, $current_year]);
            $fee_structures = $stmt->fetchAll();
            
            // Get payment history for this student
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
                
                foreach ($fee_structures as $fs) {
                    if ($fs['term'] === $term) {
                        $term_fees += $fs['amount'];
                    }
                }
                
                foreach ($payments as $payment) {
                    if ($payment['term'] === $term) {
                        $term_paid += $payment['amount'];
                    }
                }
                
                $term_balance = $term_fees - $term_paid;
                
                $term_data[$term] = [
                    'fees' => $term_fees,
                    'paid' => $term_paid,
                    'balance' => $term_balance
                ];
                
                $total_fees += $term_fees;
                $total_paid += $term_paid;
            }
            
            $grand_total_fees += $total_fees;
            $grand_total_paid += $total_paid;
        }
        
        // Student Information Section (EXACT teachers/fees design)
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
        $pdf->Cell(140, 7, $student['class_name'], 0, 1, 'L');
        
        $pdf->Ln(5);
        
        // Fee Summary Section
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetTextColor(255, 102, 0);
        $pdf->Cell(0, 8, 'FEE SUMMARY', 0, 1, 'L');
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(3);
        
        // Summary table header (EXACT teachers/fees design - no Fee Type column)
        $pdf->SetFillColor(255, 102, 0);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 8, 'Term', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Fees', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'Paid', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Balance', 1, 1, 'C', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Summary table data (EXACT teachers/fees design)
        $pdf->SetFont('helvetica', '', 9);
        foreach ($terms as $term) {
            $data = $term_data[$term] ?? ['fees' => 0, 'paid' => 0, 'balance' => 0];
            $pdf->Cell(50, 7, $term, 1, 0, 'C');
            $pdf->Cell(40, 7, 'KES ' . number_format($data['fees'], 2), 1, 0, 'R');
            $pdf->Cell(40, 7, 'KES ' . number_format($data['paid'], 2), 1, 0, 'R');
            $pdf->Cell(50, 7, 'KES ' . number_format($data['balance'], 2), 1, 1, 'R');
        }
        
        // Total row (EXACT teachers/fees design)
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(50, 8, 'TOTAL', 1, 0, 'C', true);
        $pdf->Cell(40, 8, 'KES ' . number_format($total_fees, 2), 1, 0, 'R', true);
        $pdf->Cell(40, 8, 'KES ' . number_format($total_paid, 2), 1, 0, 'R', true);
        $pdf->Cell(50, 8, 'KES ' . number_format($total_fees - $total_paid, 2), 1, 1, 'R', true);
        
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
            // Payment history table header (EXACT teachers/fees design)
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
        
        // Separator between students
        $pdf->Ln(10);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
        $pdf->Ln(10);
    }
    
    // Grand Total Section (EXACT teachers/fees design)
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'GRAND TOTAL (All Children)', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(3);
    
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(50, 10, 'TOTAL', 1, 0, 'C', true);
    $pdf->Cell(40, 10, 'KES ' . number_format($grand_total_fees, 2), 1, 0, 'R', true);
    $pdf->Cell(40, 10, 'KES ' . number_format($grand_total_paid, 2), 1, 0, 'R', true);
    $pdf->Cell(50, 10, 'KES ' . number_format($grand_total_fees - $grand_total_paid, 2), 1, 1, 'R', true);
    
    $pdf->Ln(10);
    
    // Add QR Code for verification
    // Include full names and mobile number
    $student_list = '';
    foreach ($students as $student) {
        $student_list .= $student['first_name'] . ' ' . $student['last_name'] . 
                        ' (' . $student['admission_number'] . '), ';
    }
    $student_list = rtrim($student_list, ', ');
    
    $verification_data = "FEE STATEMENT\n" .
        "School: " . $school['school_name'] . "\n" .
        "Parent: " . $parent['first_name'] . ' ' . $parent['last_name'] . "\n" .
        "Mobile: " . ($parent['phone'] ?? 'N/A') . "\n" .
        "Date: " . date('d M Y') . "\n" .
        "Year: " . $current_year . "\n" .
        "Students: " . $student_list . "\n" .
        "Total: KES " . number_format($grand_total_fees, 2) . "\n" .
        "Paid: KES " . number_format($grand_total_paid, 2) . "\n" .
        "Ref: " . substr(md5($parent['email'] . time()), 0, 6);
    
    // QR Code Section
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(255, 102, 0);
    $pdf->Cell(0, 8, 'VERIFICATION', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);
    
    // Save current position
    $qr_y = $pdf->GetY();
    
    // Add QR code using TCPDF's 2D barcode - centered on page
    $style = [
        'border' => 2,
        'vpadding' => 'auto',
        'hpadding' => 'auto',
        'fgcolor' => array(0,0,0),
        'bgcolor' => false,
        'module_width' => 5,
        'module_height' => 5
    ];
    
    // Center QR code on page with larger size for better readability with additional data
    $pdf->write2DBarcode($verification_data, 'QRCODE,M', 80, $qr_y, 50, 50, $style, 'N');
    
    // Add explanatory text below QR code
    $pdf->Ln(55);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(0, 5, 'Scan this QR code to view fee statement details.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Contains: School, Parent Name & Mobile, Students with Admission Numbers, and Financial Summary', 0, 1, 'C');
    
    $pdf->Ln(10);
    
    // Check if we need a new page for footer to prevent overflow
    if ($pdf->GetY() > 250) {
        $pdf->AddPage();
    }
    
    // Professional Footer
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(8);
    
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 5, 'This is an official fee statement generated by Kenya EduHub School Management System', 0, 1, 'C');
    $pdf->Cell(0, 5, 'Generated on: ' . date('d M Y H:i:s'), 0, 1, 'C');
    $pdf->Cell(0, 5, 'For inquiries, please contact the school administration', 0, 1, 'C');
    
    return $pdf->Output('', 'S');
}
