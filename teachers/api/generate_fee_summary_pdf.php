<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/fee_summary_error.log');

file_put_contents(__DIR__ . '/fee_summary_error.log', "Script started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/fee_summary_error.log', "Request method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/fee_summary_error.log', "Session school_id: " . (isset($_SESSION['school_id']) ? $_SESSION['school_id'] : 'not set') . "\n", FILE_APPEND);

session_start();

file_put_contents(__DIR__ . '/fee_summary_error.log', "After session_start\n", FILE_APPEND);

try {
    require_once '../../config.php';
    file_put_contents(__DIR__ . '/fee_summary_error.log', "Config loaded\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/fee_summary_error.log', "Config error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Config error: ' . $e->getMessage()]);
    exit;
}

try {
    require_once '../../PHPMailer/src/PHPMailer.php';
    require_once '../../PHPMailer/src/SMTP.php';
    require_once '../../PHPMailer/src/Exception.php';
    file_put_contents(__DIR__ . '/fee_summary_error.log', "PHPMailer loaded\n", FILE_APPEND);
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/fee_summary_error.log', "PHPMailer error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'PHPMailer error: ' . $e->getMessage()]);
    exit;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check authentication - accept from session or request body
$school_id = $_SESSION['school_id'] ?? null;
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

if (!$school_id) {
    file_put_contents(__DIR__ . '/fee_summary_error.log', "No school_id in session, checking request body\n", FILE_APPEND);
    // Try to get from request body
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    $school_id = $input['school_id'] ?? null;
}

if (!$school_id) {
    file_put_contents(__DIR__ . '/fee_summary_error.log', "Unauthorized - no school_id\n", FILE_APPEND);
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - no school_id found']);
    exit;
}

file_put_contents(__DIR__ . '/fee_summary_error.log', "Authentication passed with school_id: " . $school_id . "\n", FILE_APPEND);

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    $school_name = $input['school_name'] ?? 'Kenya EduHub';
    $date = $input['date'] ?? date('F j, Y');
    $academic_year = $input['academic_year'] ?? date('Y');
    $fee_type = $input['fee_type'] ?? 'All Fee Types';
    $students = $input['students'] ?? [];
    $summary = $input['summary'] ?? [];
    
    if (empty($students)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No student data provided']);
        exit;
    }
    
    try {
        // Get SMTP settings
        $stmt = $pdo->prepare("SELECT email, app_password, smtp_host, smtp_port, encryption FROM smtp_settings WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $smtpSettings = $stmt->fetch();
        
        if (!$smtpSettings) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'SMTP settings not configured']);
            exit;
        }
        
        // Group students by parent email
        $parentStudents = [];
        foreach ($students as $student) {
            // Get parent email using student_parents table
            $stmt = $pdo->prepare("SELECT p.email, p.first_name, p.last_name FROM parents p 
                                   JOIN student_parents sp ON p.id = sp.parent_id 
                                   JOIN students s ON s.id = sp.student_id 
                                   WHERE s.admission_number = ? AND s.school_id = ?");
            $stmt->execute([$student['admission'], $school_id]);
            $parent = $stmt->fetch();
            
            if ($parent && $parent['email']) {
                $email = $parent['email'];
                $parentName = $parent['first_name'] . ' ' . $parent['last_name'];
                if (!isset($parentStudents[$email])) {
                    $parentStudents[$email] = [
                        'name' => $parentName,
                        'students' => []
                    ];
                }
                $parentStudents[$email]['students'][] = $student;
            }
        }
        
        error_log("Found parent emails: " . count($parentStudents));
        
        if (empty($parentStudents)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No parent emails found for the selected students']);
            exit;
        }
        
        // Send individual emails to each parent
        $emailsSent = 0;
        $emailsFailed = 0;
        
        foreach ($parentStudents as $email => $parentData) {
            $parentName = $parentData['name'];
            $parentStudentsList = $parentData['students'];
            
            // Build fee table for this parent's children only
            $feeTable = "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            $feeTable .= "<tr style='background: #FF6B35; color: white;'>";
            $feeTable .= "<th>Admission</th><th>Name</th><th>Stream</th><th>Term</th><th>Fee Type</th><th>Fees</th><th>Paid</th><th>Balance</th><th>Status</th>";
            $feeTable .= "</tr>";
            
            $totalFeesSum = 0;
            $totalPaidSum = 0;
            $totalBalanceSum = 0;
            $paidCount = 0;
            $unpaidCount = 0;
            $partialCount = 0;
            
            $paymentHistoryTable = "";
            
            foreach ($parentStudentsList as $student) {
                // Get student ID for payment history
                $stmt = $pdo->prepare("SELECT id FROM students WHERE admission_number = ? AND school_id = ?");
                $stmt->execute([$student['admission'], $school_id]);
                $studentData = $stmt->fetch();
                $studentId = $studentData ? $studentData['id'] : null;
                
                // Get term from fee_type or use default
                $term = $academic_year; // Default to academic year if term not specified
                
                // Get payment history for this student (successful payments only)
                $paymentHistory = "";
                if ($studentId) {
                    $stmt = $pdo->prepare("SELECT payment_date, amount, payment_method, term, fee_type, receipt_number 
                                           FROM fee_payments 
                                           WHERE student_id = ? AND year = ? 
                                           ORDER BY payment_date DESC");
                    $stmt->execute([$studentId, $academic_year]);
                    $payments = $stmt->fetchAll();
                    
                    if (!empty($payments)) {
                        $paymentHistory = "<h4 style='color: #FF6B35; margin-top: 15px;'>Payment History for {$student['name']}</h4>";
                        $paymentHistory .= "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin-bottom: 15px;'>";
                        $paymentHistory .= "<tr style='background: #FF6B35; color: white;'>";
                        $paymentHistory .= "<th>Date</th><th>Receipt #</th><th>Term</th><th>Fee Type</th><th>Amount</th><th>Payment Method</th>";
                        $paymentHistory .= "</tr>";
                        
                        foreach ($payments as $payment) {
                            $paymentHistory .= "<tr>";
                            $paymentHistory .= "<td>{$payment['payment_date']}</td>";
                            $paymentHistory .= "<td>{$payment['receipt_number']}</td>";
                            $paymentHistory .= "<td>{$payment['term']}</td>";
                            $paymentHistory .= "<td>{$payment['fee_type']}</td>";
                            $paymentHistory .= "<td>KES " . number_format($payment['amount']) . "</td>";
                            $paymentHistory .= "<td>{$payment['payment_method']}</td>";
                            $paymentHistory .= "</tr>";
                        }
                        $paymentHistory .= "</table>";
                    }
                }
                
                $feeTable .= "<tr>";
                $feeTable .= "<td>{$student['admission']}</td>";
                $feeTable .= "<td>{$student['name']}</td>";
                $feeTable .= "<td>{$student['stream']}</td>";
                $feeTable .= "<td>$term</td>";
                $feeTable .= "<td>{$student['feeType']}</td>";
                $feeTable .= "<td>KES " . number_format((float)str_replace(',', '', $student['totalFees'])) . "</td>";
                $feeTable .= "<td>KES " . number_format((float)str_replace(',', '', $student['totalPaid'])) . "</td>";
                $feeTable .= "<td>KES " . number_format((float)str_replace(',', '', $student['balance'])) . "</td>";
                $feeTable .= "<td>" . strtoupper($student['status']) . "</td>";
                $feeTable .= "</tr>";
                
                $totalFeesSum += (float)str_replace(',', '', $student['totalFees']);
                $totalPaidSum += (float)str_replace(',', '', $student['totalPaid']);
                $totalBalanceSum += (float)str_replace(',', '', $student['balance']);
                
                if (stripos($student['status'], 'Paid') !== false && stripos($student['status'], 'Partial') === false) {
                    $paidCount++;
                } elseif (stripos($student['status'], 'Partial') !== false) {
                    $partialCount++;
                } else {
                    $unpaidCount++;
                }
                
                $paymentHistoryTable .= $paymentHistory;
            }
            $feeTable .= "</table>";
            
            // Send email to this parent
            $mail = new PHPMailer(true);
            
            try {
                $mail->isSMTP();
                $mail->Host = $smtpSettings['smtp_host'];
                $mail->SMTPAuth = true;
                $mail->Username = $smtpSettings['email'];
                $mail->Password = $smtpSettings['app_password'];
                $mail->SMTPSecure = $smtpSettings['encryption'] === 'tls' ? 'tls' : 'ssl';
                $mail->Port = $smtpSettings['smtp_port'];
                
                $mail->addAddress($email);
                $mail->setFrom($smtpSettings['email'], $school_name);
                $mail->isHTML(true);
                $mail->Subject = 'Fee Statement for Your Child - ' . $date;
                
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;'>
                        <div style='background: #FF6B35; color: white; padding: 20px; text-align: center;'>
                            <h2 style='margin: 0;'>$school_name</h2>
                            <p style='margin: 5px 0 0 0; opacity: 0.9;'>Kenya EduHub</p>
                        </div>
                        <div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #FF6B35;'>
                            <p style='margin: 0; font-size: 12px; color: #666;'>
                                <strong>To:</strong> $parentName<br>
                                <strong>From:</strong> $teacher_name<br>
                                <strong>Date:</strong> $date<br>
                                <strong>Academic Year:</strong> $academic_year
                            </p>
                        </div>
                        <div style='padding: 20px; background: white;'>
                            <p>Dear $parentName,</p>
                            <p>Please find below the fee statement for your child(ren) for the academic year <strong>$academic_year</strong>.</p>
                            
                            <h3 style='color: #FF6B35;'>STUDENT FEE DETAILS</h3>
                            $feeTable
                            
                            $paymentHistoryTable
                            
                            <h3 style='color: #FF6B35; margin-top: 20px;'>SUMMARY</h3>
                            <p>
                                <strong>Total Children:</strong> " . count($parentStudentsList) . "<br>
                                <strong>Fully Paid:</strong> $paidCount<br>
                                <strong>Partial Payment:</strong> $partialCount<br>
                                <strong>Not Paid:</strong> $unpaidCount<br>
                                <strong>Total Fees:</strong> KES " . number_format($totalFeesSum) . "<br>
                                <strong>Total Paid:</strong> KES " . number_format($totalPaidSum) . "<br>
                                <strong>Total Balance:</strong> KES " . number_format($totalBalanceSum) . "
                            </p>
                            
                            <p>For payment inquiries, please contact the school administration.</p>
                            <p>Thank you for your cooperation.</p>
                        </div>
                        <div style='background: #f1f3f4; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                            <p style='margin: 0;'>This email was sent from $school_name via Kenya EduHub</p>
                            <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " $school_name. All rights reserved.</p>
                        </div>
                    </div>
                ";
                
                $mail->Body = $emailBody;
                $mail->AltBody = "Dear $parentName,\n\nFee Statement for your child(ren) - $academic_year\n\nTotal Children: " . count($parentStudentsList) . "\nFully Paid: $paidCount\nPartial Payment: $partialCount\nNot Paid: $unpaidCount\nTotal Fees: KES " . number_format($totalFeesSum) . "\nTotal Paid: KES " . number_format($totalPaidSum) . "\nTotal Balance: KES " . number_format($totalBalanceSum) . "\n\nFor payment inquiries, please contact the school administration.\n\nBest regards,\n$teacher_name\n$school_name";
                
                $mail->send();
                $emailsSent++;
                error_log("Email sent successfully to: " . $email);
                
            } catch (Exception $e) {
                error_log("Email sending error for $email: " . $mail->ErrorInfo);
                $emailsFailed++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Fee statements sent to $emailsSent parents. $emailsFailed failed."]);
        
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to process request: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
