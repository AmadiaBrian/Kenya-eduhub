<?php
// API endpoint to send fee balances via SMS to parents
session_start();
require_once '../../config.php';
require_once '../../sms/sms_config.php';
require_once '../../sms/SMSHelper.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];

// Get school name and SMS settings
$stmt = $pdo->prepare("SELECT school_name, sms_enabled FROM schools WHERE id = ?");
$stmt->execute([$school_id]);
$school = $stmt->fetch();
$school_name = $school['school_name'] ?? 'School';
$sms_enabled = $school['sms_enabled'] ?? 0;

if (!$sms_enabled) {
    echo json_encode(['success' => false, 'error' => 'SMS is not enabled for this school']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
error_log("Raw input: " . file_get_contents('php://input'));

$students = $input['students'] ?? [];
$balance_type = $input['balance_type'] ?? 'term';
$year = $input['year'] ?? date('Y');
$term = $input['term'] ?? null;

error_log("Parsed data: balance_type=$balance_type, year=$year, term=$term");
error_log("Students array: " . print_r($students, true));

if (empty($students)) {
    echo json_encode(['success' => false, 'error' => 'No students selected']);
    exit;
}

try {
    $students_with_balances = [];
    $skipped_students = [];
    
    error_log("Processing " . count($students) . " students for SMS");
    
    // For each selected student, calculate their fee balance based on selected period
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        $admission_number = $student['admission_number'];
        
        error_log("Processing student ID: $student_id, Admission: $admission_number");
        error_log("Query params: student_id=$student_id, school_id=$school_id");
        
        // Get student details including class
        $stmt = $pdo->prepare("
            SELECT s.id as student_id, s.admission_number, 
                   CONCAT(s.first_name, ' ', s.last_name) as student_name,
                   c.class_name, c.id as class_id
            FROM students s
            LEFT JOIN classes c ON s.class_id = c.id
            WHERE s.id = ? AND s.school_id = ?
        ");
        $stmt->execute([$student_id, $school_id]);
        $student_data = $stmt->fetch();
        
        error_log("Query result: " . ($student_data ? "Found" : "Not found"));
        
        if (!$student_data) {
            error_log("Student not found in database: ID=$student_id, School=$school_id");
            // Try to find the student without school_id restriction for debugging
            $stmt2 = $pdo->prepare("SELECT id, admission_number, school_id FROM students WHERE id = ?");
            $stmt2->execute([$student_id]);
            $debug_student = $stmt2->fetch();
            error_log("Debug student lookup: " . print_r($debug_student, true));
            $skipped_students[] = "$admission_number (not found in database)";
            continue;
        }
        
        // Check if fee details are provided from frontend selection
        if (isset($student['fee_type']) && isset($student['fee_amount'])) {
            // Use the exact fee details from the selected row
            $fee_type = $student['fee_type'];
            $fee_amount = $student['fee_amount'];
            $paid_amount = $student['paid_amount'] ?? 0;
            $balance = $student['balance'];
            
            $fee_breakdown = [
                $fee_type => [
                    'fees' => $fee_amount,
                    'paid' => $paid_amount
                ]
            ];
            
            $total_fees = $fee_amount;
            $total_paid = $paid_amount;
            
            error_log("Using fee details from selection: fee_type=$fee_type, fees=$fee_amount, paid=$paid_amount, balance=$balance");
        } else {
            // Original logic: calculate from database
            $class_id = $student_data['class_id'];
            
            // Get fee structures for this student's class
            if ($balance_type === 'year') {
                // Full year - all terms
                $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
                $stmt->execute([$school_id, $class_id, $year]);
            } else {
                // Specific term
                $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? AND term = ? ORDER BY fee_type");
                $stmt->execute([$school_id, $class_id, $year, $term]);
            }
            $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($fee_structures)) {
                error_log("No fee structures found for class_id=$class_id, year=$year, term=$term");
                $skipped_students[] = "$admission_number (no fee structures for selected period)";
                continue;
            }
            
            // Get payments for this student in the selected period
            if ($balance_type === 'year') {
                $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
                $stmt->execute([$student_id, $year]);
            } else {
                $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND term = ? AND status = 'completed' ORDER BY payment_date DESC");
                $stmt->execute([$student_id, $year, $term]);
            }
            $payments = $stmt->fetchAll();
            
            // Calculate balance by fee type
            $fee_breakdown = [];
            $total_fees = 0;
            $total_paid = 0;
            
            // Initialize fee types with their amounts
            foreach ($fee_structures as $fs) {
                $fee_type = $fs['fee_type'] ?? 'Tuition';
                if (!isset($fee_breakdown[$fee_type])) {
                    $fee_breakdown[$fee_type] = ['fees' => 0, 'paid' => 0];
                }
                $fee_breakdown[$fee_type]['fees'] += $fs['amount'];
                $total_fees += $fs['amount'];
            }
            
            // Add payments to fee types
            foreach ($payments as $payment) {
                $payment_fee_type = $payment['fee_type'] ?? 'Tuition';
                if (!isset($fee_breakdown[$payment_fee_type])) {
                    $fee_breakdown[$payment_fee_type] = ['fees' => 0, 'paid' => 0];
                }
                $fee_breakdown[$payment_fee_type]['paid'] += $payment['amount'];
                $total_paid += $payment['amount'];
            }
            
            $balance = $total_fees - $total_paid;
            
            error_log("Student $admission_number: Fees=$total_fees, Paid=$total_paid, Balance=$balance");
            error_log("Fee breakdown: " . print_r($fee_breakdown, true));
        }
        
        // Include student regardless of balance (they may have paid partially)
        $students_with_balances[] = [
            'student_id' => $student_id,
            'admission_number' => $student_data['admission_number'],
            'student_name' => $student_data['student_name'],
            'class_name' => $student_data['class_name'],
            'total_fees' => $total_fees,
            'total_paid' => $total_paid,
            'balance' => $balance,
            'fee_breakdown' => $fee_breakdown,
            'period' => $balance_type === 'year' ? "Year $year" : "$term $year"
        ];
    }
    
    error_log("Valid students found: " . count($students_with_balances));
    error_log("Skipped students: " . implode(', ', $skipped_students));
    
    if (empty($students_with_balances)) {
        $error_msg = 'No valid students found. ';
        if (!empty($skipped_students)) {
            $error_msg .= 'Skipped: ' . implode(', ', $skipped_students);
        }
        echo json_encode(['success' => false, 'error' => $error_msg]);
        exit;
    }
    
    $sent_count = 0;
    $failed_count = 0;
    $error_details = [];
    
    // Send SMS to each student's parents
    foreach ($students_with_balances as $student) {
        try {
            error_log("Getting parents for student ID: {$student['student_id']}, Admission: {$student['admission_number']}");
            
            // Get student's parents
            $stmt = $pdo->prepare("
                SELECT p.id, p.first_name, p.last_name, p.phone 
                FROM parents p
                JOIN student_parents sp ON p.id = sp.parent_id
                WHERE sp.student_id = ? AND p.school_id = ?
            ");
            $stmt->execute([$student['student_id'], $school_id]);
            $parents = $stmt->fetchAll();
            
            error_log("Found " . count($parents) . " parents for student {$student['admission_number']}");
            
            if (empty($parents)) {
                $error_msg = "No parents found for student {$student['admission_number']}";
                error_log($error_msg);
                $error_details[] = $error_msg;
                $failed_count++;
                continue;
            }
            
            // Build SMS message with totals
            $sms_message = "$school_name - Fee Balance for {$student['student_name']} ({$student['admission_number']})\n";
            $sms_message .= "Period: {$student['period']}\n";
            $sms_message .= "Total Fees: KES " . number_format($student['total_fees']) . "\n";
            $sms_message .= "Amount Paid: KES " . number_format($student['total_paid']) . "\n";
            $sms_message .= "Outstanding Balance: KES " . number_format($student['balance']) . "\n";
            $sms_message .= "Please clear the balance to avoid penalties.";
            
            // Send to each parent
            foreach ($parents as $parent) {
                error_log("Parent: {$parent['first_name']} {$parent['last_name']}, Phone: {$parent['phone']}");
                
                if (!empty($parent['phone'])) {
                    // Call SMS API
                    error_log("Calling SMS API for parent {$parent['phone']}");
                    $sms_response = sendSMS($parent['phone'], $sms_message, $school_id, $parent['first_name'] . ' ' . $parent['last_name'], 'parent', 'fee_balance');
                    
                    error_log("SMS API response: " . print_r($sms_response, true));
                    
                    if ($sms_response['success']) {
                        $sent_count++;
                        error_log("SMS sent to parent {$parent['phone']} for student {$student['admission_number']}");
                    } else {
                        $error_msg = "Failed to send SMS to parent {$parent['phone']}: " . $sms_response['error'];
                        error_log($error_msg);
                        $error_details[] = $error_msg;
                        $failed_count++;
                    }
                } else {
                    $error_msg = "Parent {$parent['first_name']} {$parent['last_name']} has no phone number";
                    error_log($error_msg);
                    $error_details[] = $error_msg;
                    $failed_count++;
                }
            }
        } catch (PDOException $e) {
            error_log("Error processing student {$student['student_id']}: " . $e->getMessage());
            $failed_count++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'sent_count' => $sent_count,
        'failed_count' => $failed_count,
        'message' => "Sent $sent_count SMS, $failed_count failed",
        'error_details' => $error_details
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in send_fee_balance_sms: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("General error in send_fee_balance_sms: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}

// Function to send SMS
function sendSMS($phone, $message, $school_id, $recipient_name, $recipient_type, $message_type) {
    global $pdo;
    
    try {
        // Check if SMS is enabled
        if (!SMS_ENABLED) {
            return ['success' => false, 'error' => 'SMS service is disabled'];
        }
        
        // Initialize SMS Helper with school's selected provider
        $smsHelper = new SMSHelper($pdo, $school_id);
        
        // Build options
        $options = [
            'recipient_name' => $recipient_name,
            'recipient_type' => $recipient_type,
            'message_type' => $message_type
        ];
        
        // Send SMS
        $result = $smsHelper->sendSMS($phone, $message, $school_id, $options);
        
        return $result;
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
