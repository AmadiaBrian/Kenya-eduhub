<?php
// Load PHPMailer at the top of the file
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';
require_once '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX request for school lookup (must be before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'lookup_school') {
    require_once '../config.php';
    header('Content-Type: application/json');
    $school_code = trim($_GET['school_code'] ?? '');
    
    if (empty($school_code)) {
        echo json_encode(['success' => false, 'error' => 'School code is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT s.id, s.school_code, s.school_name, s.email, s.phone,
                   COALESCE(sb.balance, 0.00) as current_balance
            FROM schools s
            LEFT JOIN school_balances sb ON s.id = sb.school_id
            WHERE s.school_code = ?
        ");
        $stmt->bind_param("s", $school_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if ($school) {
            echo json_encode(['success' => true, 'school' => $school]);
        } else {
            echo json_encode(['success' => false, 'error' => 'School not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request for verification code verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify_code') {
    require_once '../config.php';
    header('Content-Type: application/json');
    $school_id = intval($_GET['school_id'] ?? 0);
    $code = trim($_GET['code'] ?? '');
    
    if ($school_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid school ID']);
        exit();
    }
    
    if (empty($code)) {
        echo json_encode(['success' => false, 'error' => 'Verification code is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT verification_code, verification_expiry FROM schools WHERE id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if (!$school) {
            echo json_encode(['success' => false, 'error' => 'School not found']);
            exit();
        }
        
        if (empty($school['verification_code'])) {
            echo json_encode(['success' => false, 'error' => 'No verification code found. Please request a new one.']);
            exit();
        }
        
        // Check if code has expired
        if (strtotime($school['verification_expiry']) < time()) {
            echo json_encode(['success' => false, 'error' => 'Verification code has expired. Please request a new one.']);
            exit();
        }
        
        if ($code === $school['verification_code']) {
            // Clear the email verification code after successful use
            $stmt = $conn->prepare("UPDATE schools SET verification_code = NULL, verification_expiry = NULL WHERE id = ?");
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            
            // Generate SMS verification code (different from email code)
            $sms_verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $sms_expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Add SMS verification columns if they don't exist
            try {
                $conn->query("ALTER TABLE schools ADD COLUMN IF NOT EXISTS sms_verification_code VARCHAR(10) DEFAULT NULL AFTER verification_expiry");
                $conn->query("ALTER TABLE schools ADD COLUMN IF NOT EXISTS sms_verification_expiry DATETIME DEFAULT NULL AFTER sms_verification_code");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            // Save SMS verification code to database
            $stmt = $conn->prepare("UPDATE schools SET sms_verification_code = ?, sms_verification_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $sms_verification_code, $sms_expiry_time, $school_id);
            $stmt->execute();
            
            // Get school phone number
            $stmt = $conn->prepare("SELECT phone, school_name FROM schools WHERE id = ?");
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $school_data = $stmt->get_result()->fetch_assoc();
            
            // Get admin SMS settings from database
            $stmt = $conn->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
            $stmt->execute();
            $sms_result = $stmt->get_result();
            $admin_sms = $sms_result->fetch_assoc();
            
            if (!$admin_sms || empty($admin_sms['sms_enabled'])) {
                echo json_encode(['success' => false, 'error' => 'SMS settings not configured. Please contact administrator to set up SMS settings.']);
                exit();
            }
            
            // Load SMS helper classes and config
            require_once '../sms/sms_config.php';
            require_once '../sms/MobitechSMS.php';
            require_once '../sms/TextSMS.php';
            
            // Send SMS using the configured provider
            $sms_sent = false;
            $sms_error = '';
            
            if ($admin_sms['sms_provider'] === 'mobitech') {
                // Send via Mobitech using the existing class
                $mobitech_api_key = $admin_sms['mobitech_api_key'];
                $mobitech_sender_id = $admin_sms['mobitech_sender_id'];
                $phone = $school_data['phone'];
                $message = "Your Kenya EduHub SMS verification code is: $sms_verification_code. Valid for 15 minutes.";
                
                error_log("Sending SMS via Mobitech - Phone: $phone, Sender ID: $mobitech_sender_id");
                error_log("API Key length: " . strlen($mobitech_api_key));
                
                try {
                    $mobitech = new MobitechSMS($mobitech_api_key, $mobitech_sender_id);
                    $result = $mobitech->sendSMS($phone, $message);
                    
                    error_log("Mobitech SMS Result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $sms_sent = true;
                    } else {
                        $sms_error = $result['error'] ?? 'Unknown error';
                        error_log("Mobitech SMS Error: $sms_error");
                    }
                } catch (Exception $e) {
                    $sms_error = $e->getMessage();
                    error_log("Mobitech Exception: $sms_error");
                }
            } elseif ($admin_sms['sms_provider'] === 'textsms') {
                // Send via Text SMS using the existing class
                $textsms_api_key = $admin_sms['textsms_api_key'];
                $textsms_partner_id = $admin_sms['textsms_partner_id'];
                $textsms_sender_id = $admin_sms['textsms_sender_id'];
                $phone = $school_data['phone'];
                $message = "Your Kenya EduHub SMS verification code is: $sms_verification_code. Valid for 15 minutes.";
                
                error_log("Sending SMS via Text SMS - Phone: $phone, Sender ID: $textsms_sender_id");
                error_log("API Key length: " . strlen($textsms_api_key));
                
                try {
                    $textsms = new TextSMS($textsms_api_key, $textsms_partner_id, $textsms_sender_id);
                    $result = $textsms->sendSMS($phone, $message);
                    
                    error_log("Text SMS Result: " . print_r($result, true));
                    
                    if ($result['success']) {
                        $sms_sent = true;
                    } else {
                        $sms_error = $result['error'] ?? 'Unknown error';
                        error_log("Text SMS Error: $sms_error");
                    }
                } catch (Exception $e) {
                    $sms_error = $e->getMessage();
                    error_log("Text SMS Exception: $sms_error");
                }
            }
            
            if ($sms_sent) {
                echo json_encode(['success' => true, 'message' => 'Email verified. SMS verification code sent to phone']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to send SMS: ' . $sms_error]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid verification code']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request for SMS verification code verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify_sms_code') {
    require_once '../config.php';
    header('Content-Type: application/json');
    $school_id = intval($_GET['school_id'] ?? 0);
    $sms_code = trim($_GET['sms_code'] ?? '');
    
    if ($school_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid school ID']);
        exit();
    }
    
    if (empty($sms_code)) {
        echo json_encode(['success' => false, 'error' => 'SMS verification code is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT sms_verification_code, sms_verification_expiry FROM schools WHERE id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if (!$school) {
            echo json_encode(['success' => false, 'error' => 'School not found']);
            exit();
        }
        
        if (empty($school['sms_verification_code'])) {
            echo json_encode(['success' => false, 'error' => 'No SMS verification code found. Please complete email verification first.']);
            exit();
        }
        
        // Check if code has expired
        if (strtotime($school['sms_verification_expiry']) < time()) {
            echo json_encode(['success' => false, 'error' => 'SMS verification code has expired. Please start the verification process again.']);
            exit();
        }
        
        if ($sms_code === $school['sms_verification_code']) {
            // Clear the SMS verification code after successful use
            $stmt = $conn->prepare("UPDATE schools SET sms_verification_code = NULL, sms_verification_expiry = NULL WHERE id = ?");
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            
            echo json_encode(['success' => true, 'message' => 'SMS verification successful. Withdrawal can now proceed.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid SMS verification code']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Handle AJAX request for PIN verification
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify_pin') {
    require_once '../config.php';
    header('Content-Type: application/json');
    $school_id = intval($_GET['school_id'] ?? 0);
    $pin = trim($_GET['pin'] ?? '');
    
    if ($school_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid school ID']);
        exit();
    }
    
    if (empty($pin)) {
        echo json_encode(['success' => false, 'error' => 'PIN is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("SELECT withdrawal_pin FROM schools WHERE id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if (!$school) {
            echo json_encode(['success' => false, 'error' => 'School not found']);
            exit();
        }
        
        if (empty($school['withdrawal_pin'])) {
            echo json_encode(['success' => false, 'error' => 'No PIN set for this school']);
            exit();
        }
        
        if (password_verify($pin, $school['withdrawal_pin'])) {
            // Generate 6-digit verification code
            $verification_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiry_time = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            // Add verification_code column if it doesn't exist
            try {
                $conn->query("ALTER TABLE schools ADD COLUMN IF NOT EXISTS verification_code VARCHAR(10) DEFAULT NULL AFTER withdrawal_pin");
                $conn->query("ALTER TABLE schools ADD COLUMN IF NOT EXISTS verification_expiry DATETIME DEFAULT NULL AFTER verification_code");
            } catch (Exception $e) {
                // Column might already exist
            }
            
            // Save verification code to database
            $stmt = $conn->prepare("UPDATE schools SET verification_code = ?, verification_expiry = ? WHERE id = ?");
            $stmt->bind_param("ssi", $verification_code, $expiry_time, $school_id);
            $stmt->execute();
            
            // Get school email
            $stmt = $conn->prepare("SELECT email, school_name FROM schools WHERE id = ?");
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $school_data = $stmt->get_result()->fetch_assoc();
            
            // Get admin SMTP settings from database
            $stmt = $conn->prepare("SELECT * FROM admin_smtp_settings LIMIT 1");
            $stmt->execute();
            $smtp_result = $stmt->get_result();
            $admin_smtp = $smtp_result->fetch_assoc();
            
            // Debug: Log SMTP settings
            error_log("SMTP Settings Debug: " . print_r($admin_smtp, true));
            
            if (!$admin_smtp) {
                echo json_encode(['success' => false, 'error' => 'SMTP settings not configured. Please contact administrator to set up email settings.']);
                exit();
            }
            
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $admin_smtp['smtp_host'];
                $mail->Port = $admin_smtp['smtp_port'];
                $mail->Username = $admin_smtp['smtp_username'];
                $mail->Password = $admin_smtp['smtp_password'];
                $mail->SMTPSecure = $admin_smtp['encryption'] ?? 'tls';
                $mail->SMTPAuth = true; // Enable SMTP authentication
                
                // Debug: Log what we're using
                error_log("Using SMTP - Host: {$admin_smtp['smtp_host']}, Port: {$admin_smtp['smtp_port']}, User: {$admin_smtp['smtp_username']}");
                error_log("Password length: " . strlen($admin_smtp['smtp_password']));
                error_log("Password first 3 chars: " . substr($admin_smtp['smtp_password'], 0, 3));
                
                // Enable SMTP debug for detailed error information
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    error_log("SMTP Debug [$level]: $str");
                };
                
                // For Gmail, From address must match the authenticated username
                // Use SMTP username as From address to avoid authentication errors
                $mail->setFrom($admin_smtp['smtp_username'], 'Kenya EduHub');
                $mail->addAddress($school_data['email']);
                
                $mail->isHTML(true);
                $mail->Subject = 'Withdrawal Verification Code';
                
                $emailBody = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <div style='background: #FF6B35; color: white; padding: 20px; text-align: center;'>
                            <h2 style='margin: 0;'>Kenya EduHub</h2>
                            <p style='margin: 5px 0 0 0; opacity: 0.9;'>Withdrawal Verification</p>
                        </div>
                        <div style='padding: 20px; background: white;'>
                            <p>Dear <strong>{$school_data['school_name']}</strong>,</p>
                            <p>A withdrawal request has been initiated for your school account. Please use the following verification code to complete the process:</p>
                            <div style='background: #f8f9fa; padding: 20px; text-align: center; border: 2px solid #FF6B35; border-radius: 8px; margin: 20px 0;'>
                                <h1 style='margin: 0; font-size: 36px; color: #FF6B35; letter-spacing: 5px;'>$verification_code</h1>
                            </div>
                            <p><strong>This code will expire in 15 minutes.</strong></p>
                            <p>If you did not initiate this withdrawal, please contact the administrator immediately.</p>
                        </div>
                        <div style='background: #f1f3f4; padding: 15px; text-align: center; font-size: 12px; color: #666;'>
                            <p style='margin: 0;'>This email was sent from Kenya EduHub</p>
                            <p style='margin: 5px 0 0 0;'>&copy; " . date('Y') . " Kenya EduHub. All rights reserved.</p>
                        </div>
                    </div>
                ";
                
                $mail->Body = $emailBody;
                $mail->AltBody = "Your verification code is: $verification_code\n\nThis code will expire in 15 minutes.";
                
                // Send the email
                $mail->send();
                
                echo json_encode(['success' => true, 'message' => 'Verification code sent to email']);
            } catch (Exception $e) {
                // Return the actual PHPMailer error for debugging
                $error_msg = $e->getMessage();
                echo json_encode(['success' => false, 'error' => 'Failed to send email: ' . $error_msg]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid PIN']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Session is started by index.php router
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Output CSRF token to JavaScript for AJAX requests
$csrf_token = generateCSRFLite();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?route=login");
    exit();
}

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle form submissions
$errors = [];
$success = '';

// Handle AJAX request for school lookup
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'lookup_school') {
    header('Content-Type: application/json');
    $school_code = trim($_GET['school_code'] ?? '');
    
    if (empty($school_code)) {
        echo json_encode(['success' => false, 'error' => 'School code is required']);
        exit();
    }
    
    try {
        $stmt = $conn->prepare("
            SELECT s.id, s.school_code, s.school_name, s.email, s.phone,
                   COALESCE(sb.balance, 0.00) as current_balance
            FROM schools s
            LEFT JOIN school_balances sb ON s.id = sb.school_id
            WHERE s.school_code = ?
        ");
        $stmt->bind_param("s", $school_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if ($school) {
            echo json_encode(['success' => true, 'school' => $school]);
        } else {
            echo json_encode(['success' => false, 'error' => 'School not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_withdrawal'])) {
        // Validate CSRF token
        if (!validateCSRFLite($_POST['csrf_token'] ?? '')) {
            $errors[] = 'Invalid CSRF token. Please refresh the page and try again.';
        } else {
            // Validate form submission token to prevent double submission
            $form_token = $_POST['form_token'] ?? '';
            if (empty($form_token)) {
                $errors[] = 'Invalid form submission. Please refresh the page and try again.';
            } else {
                // Check if this form token has already been used
                if (isset($_SESSION['used_withdrawal_tokens']) && in_array($form_token, $_SESSION['used_withdrawal_tokens'])) {
                    $errors[] = 'This withdrawal has already been processed. Please refresh the page.';
                } else {
                    // Mark this token as used
                    if (!isset($_SESSION['used_withdrawal_tokens'])) {
                        $_SESSION['used_withdrawal_tokens'] = [];
                    }
                    $_SESSION['used_withdrawal_tokens'][] = $form_token;
                    
                    // Limit the size of used tokens array
                    if (count($_SESSION['used_withdrawal_tokens']) > 100) {
                        $_SESSION['used_withdrawal_tokens'] = array_slice($_SESSION['used_withdrawal_tokens'], -50);
                    }
                    
                    $school_code = trim($_POST['school_code'] ?? '');
                    $school_name = trim($_POST['school_name'] ?? '');
                    $school_email = trim($_POST['school_email'] ?? '');
                    $withdrawal_amount = floatval($_POST['withdrawal_amount'] ?? 0);
                    $notes = trim($_POST['notes'] ?? '');
        
                    if (empty($school_code)) {
                        $errors[] = 'School code is required';
                    }
                    if (empty($school_name)) {
                        $errors[] = 'School name is required';
                    }
                    if (empty($school_email)) {
                        $errors[] = 'School email is required';
                    }
                    if ($withdrawal_amount <= 0) {
                        $errors[] = 'Withdrawal amount must be greater than 0';
                    }
                    
                    if (empty($errors)) {
                        try {
                            // Verify school details and get current balance
                            $stmt = $conn->prepare("
                                SELECT s.id, s.school_code, s.school_name, s.email, s.phone,
                                       COALESCE(sb.balance, 0.00) as current_balance
                                FROM schools s
                                LEFT JOIN school_balances sb ON s.id = sb.school_id
                                WHERE s.school_code = ?
                            ");
                            $stmt->bind_param("s", $school_code);
                            $stmt->execute();
                            $school = $stmt->get_result()->fetch_assoc();
                            
                            if (!$school) {
                                $errors[] = 'School not found with this code';
                            } elseif ($school['school_name'] !== $school_name || $school['email'] !== $school_email) {
                                $errors[] = 'School information verification failed';
                            } elseif ($withdrawal_amount > $school['current_balance']) {
                                $errors[] = 'Insufficient balance. Current balance: KES ' . number_format($school['current_balance'], 2);
                            } else {
                                // Process withdrawal - deduct balance and record withdrawal
                                $conn->begin_transaction();
                                
                                // Generate reference number
                                $reference_number = 'WDR-' . date('YmdHis') . '-' . $school['id'];
                                
                                // Record withdrawal in school_withdrawals table
                                $stmt = $conn->prepare("
                                    INSERT INTO school_withdrawals 
                                    (school_id, amount, destination_type, destination_name, destination_account, notes, status, reference_number, result_desc, balance_deducted_at, success_at)
                                    VALUES (?, ?, 'cash', ?, ?, ?, 'completed', ?, 'Manual cash withdrawal processed by admin', NOW(), NOW())
                                ");
                                $stmt->bind_param("idssss", $school['id'], $withdrawal_amount, $school_name, $school['phone'], $notes, $reference_number);
                                $stmt->execute();
                                
                                // Deduct from school balance
                                $new_balance = $school['current_balance'] - $withdrawal_amount;
                                $stmt = $conn->prepare("UPDATE school_balances SET balance = ? WHERE school_id = ?");
                                $stmt->bind_param("di", $new_balance, $school['id']);
                                $stmt->execute();
                                
                                $conn->commit();
                                
                                // Send SMS notification with transaction details (like M-Pesa)
                                require_once '../sms/sms_config.php';
                                require_once '../sms/MobitechSMS.php';
                                require_once '../sms/TextSMS.php';
                                
                                try {
                                    // Get admin SMS settings
                                    $stmt = $conn->prepare("SELECT * FROM admin_sms_settings LIMIT 1");
                                    $stmt->execute();
                                    $admin_sms = $stmt->get_result()->fetch_assoc();
                                    
                                    if ($admin_sms && !empty($admin_sms['sms_enabled']) && !empty($school['phone'])) {
                                        // Format phone number
                                        $phone = $school['phone'];
                                        $transaction_time = date('d M Y H:i');
                                        
                                        // Create M-Pesa style SMS message
                                        $sms_message = $reference_number . " Confirmed. KES " . number_format($withdrawal_amount, 2) . " withdrawn from Kenya EduHub on " . date('d/m/y') . " at " . date('h:i A') . ". New balance is KES " . number_format($new_balance, 2) . ".";
                                        
                                        // Send SMS using configured provider
                                        if ($admin_sms['sms_provider'] === 'mobitech') {
                                            $mobitech = new MobitechSMS($admin_sms['mobitech_api_key'], $admin_sms['mobitech_sender_id']);
                                            $result = $mobitech->sendSMS($phone, $sms_message);
                                            error_log("Withdrawal SMS sent via Mobitech: " . print_r($result, true));
                                        } elseif ($admin_sms['sms_provider'] === 'textsms') {
                                            $textsms = new TextSMS($admin_sms['textsms_api_key'], $admin_sms['textsms_partner_id'], $admin_sms['textsms_sender_id']);
                                            $result = $textsms->sendSMS($phone, $sms_message);
                                            error_log("Withdrawal SMS sent via Text SMS: " . print_r($result, true));
                                        }
                                    }
                                } catch (Exception $sms_error) {
                                    error_log("Failed to send withdrawal SMS: " . $sms_error->getMessage());
                                    // Don't fail the withdrawal if SMS fails
                                }
                                
                                $success = 'Withdrawal processed successfully! Reference: ' . $reference_number . '. Amount: KES ' . number_format($withdrawal_amount, 2) . ' given to ' . $school_name;
                            }
                        } catch (Exception $e) {
                            $conn->rollback();
                            $errors[] = 'Failed to process withdrawal: ' . $e->getMessage();
                        }
                    }
                }
            }
        }
    }
}

// Get all schools with their balances
$schools = [];
try {
    $stmt = $conn->prepare("
        SELECT s.id, s.school_code, s.school_name, s.email, s.phone, 
               COALESCE(sb.balance, 0.00) as balance,
               sb.created_at as balance_created_at
        FROM schools s
        LEFT JOIN school_balances sb ON s.id = sb.school_id
        ORDER BY s.school_name ASC
    ");
    $stmt->execute();
    $schools = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $errors[] = 'Failed to load school data: ' . $e->getMessage();
}

// Calculate statistics
$total_balance = 0;
$schools_with_balance = 0;
foreach ($schools as $school) {
    $total_balance += floatval($school['balance']);
    if ($school['balance'] > 0) {
        $schools_with_balance++;
    }
}
$total_schools = count($schools);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>School Accounts - Kenya EduHub Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
        }
        
        .sidebar.collapsed {
            transform: translateX(-256px);
        }
        
        .sidebar-section {
            padding: 12px 0;
        }
        
        .sidebar-title {
            padding: 8px 24px;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-link:hover {
            background: #f1f3f4;
        }
        
        .nav-link.active {
            background: #e8f0fe;
            color: var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #FF6B35;
        }
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            color: #5f6368;
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: #f1f3f4;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 400;
            color: #202124;
        }
        
        .logo i {
            color: var(--primary-color);
        }
        
        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: transparent;
            border-bottom: 1px solid #e8eaed;
            padding: 20px 25px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .card-body {
            padding: 24px;
            background: transparent;
        }
        
        .table {
            margin-bottom: 0;
            background: white;
            border: 1px solid #000;
            border-radius: 0;
            overflow: hidden;
        }
        
        .table th {
            font-weight: 500;
            color: #000;
            border-bottom: 2px solid #000;
            border-right: 1px solid #000;
            background: #f0f0f0;
        }
        
        .table th:last-child {
            border-right: none;
        }
        
        .table td {
            vertical-align: middle;
            background: white;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }
        
        .table td:last-child {
            border-right: none;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .balance-positive {
            color: #1e8e3e;
            font-weight: 600;
        }
        
        .balance-negative {
            color: #c5221f;
            font-weight: 600;
        }
        
        .balance-zero {
            color: #5f6368;
            font-weight: 600;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }
        
        .alert {
            border: none;
            border-radius: 8px;
            padding: 12px 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #1e8e3e;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .modal-content {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .modal-header {
            background: transparent;
            border-bottom: 1px solid #e8eaed;
            padding: 24px 24px 16px 24px;
        }
        
        .modal-body {
            background: transparent;
            padding: 16px 24px 24px 24px;
        }
        
        .modal-footer {
            background: transparent;
            border-top: 1px solid #e8eaed;
            padding: 16px 24px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        
        .form-control {
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 8px 12px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26,115,232,0.1);
        }
        
        .form-label {
            font-weight: 500;
            color: #202124;
        }
        
        .stats-card {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 25px;
            box-shadow: none;
            text-align: center;
        }
        
        .stats-value {
            font-size: 32px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stats-label {
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content.expanded {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link" href="schools">
            <i class="fas fa-school"></i> All Schools
        </a>
        <a class="nav-link active" href="school-accounts">
            <i class="fas fa-wallet"></i> School Accounts
        </a>
        <a class="nav-link" href="users">
            <i class="fas fa-users"></i> All Users
        </a>
        <a class="nav-link" href="resources">
            <i class="fas fa-folder"></i> Resources
        </a>
        <a class="nav-link" href="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a class="nav-link" href="logs">
            <i class="fas fa-history"></i> Activity Logs
        </a>
        <a class="nav-link" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
    </aside>

    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <div class="container-fluid">
            <h1 class="page-title">School Accounts</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Card -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value">KES <?php echo number_format($total_balance, 2); ?></div>
                        <div class="stats-label">Total Balance</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo count($schools); ?></div>
                        <div class="stats-label">Total Schools</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-value"><?php echo $schools_with_balance; ?></div>
                        <div class="stats-label">Schools with Balance</div>
                    </div>
                </div>
            </div>
            
            <!-- Manual Withdrawal Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-money-bill-wave me-2"></i>Process Cash Withdrawal
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div id="formErrors" class="alert alert-danger" style="display: none;"></div>
                    
                    <form method="POST" id="withdrawalForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="form_token" value="<?php echo bin2hex(random_bytes(32)); ?>">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">School Code *</label>
                                <input type="text" name="school_code" class="form-control" id="schoolCode" required placeholder="Enter school code">
                                <small class="text-muted" id="schoolLookupStatus"></small>
                            </div>
                        </div>
                        
                        <div id="schoolDetails" style="display: none;">
                            <div class="alert alert-info">
                                <h5><i class="fas fa-school me-2"></i><span id="displaySchoolName"></span></h5>
                                <p class="mb-0 text-muted">School found. Please verify email to continue.</p>
                            </div>
                            
                            <input type="hidden" name="school_name" id="schoolName">
                            <input type="hidden" name="school_email" id="schoolEmail">
                            
                            <div class="mb-3">
                                <label class="form-label">Verify School Email *</label>
                                <input type="email" class="form-control" id="verifyEmail" placeholder="Enter school email for verification">
                                <small class="text-muted">Enter the school email to verify identity</small>
                            </div>
                            
                            <button type="button" class="btn btn-primary" id="verifyEmailBtn">
                                <i class="fas fa-check"></i> Verify Email
                            </button>
                        </div>
                        
                        <div id="schoolFullDetails" style="display: none;">
                            <div class="alert alert-success">
                                <h5><i class="fas fa-check-circle me-2"></i>Email Verified</h5>
                                <p class="mb-1"><strong>Email:</strong> <span id="displayEmail"></span></p>
                                <p class="mb-1"><strong>Phone:</strong> <span id="displayPhone"></span></p>
                                <p class="mb-0"><strong>Current Balance:</strong> <span id="displayBalance" style="font-weight: bold; color: #008000;"></span></p>
                            </div>
                        </div>
                        
                        <div id="pinVerification" style="display: none;">
                            <div class="alert alert-warning">
                                <i class="fas fa-lock me-2"></i> <strong>Security Verification Required</strong>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">School PIN *</label>
                                <input type="password" class="form-control" id="schoolPin" placeholder="Enter school PIN" maxlength="6" pattern="[0-9]*">
                                <small class="text-muted">Enter the school's withdrawal PIN</small>
                            </div>
                            
                            <button type="button" class="btn btn-primary" id="verifyPinBtn">
                                <i class="fas fa-check"></i> Verify PIN
                            </button>
                        </div>
                        
                        <div id="codeVerification" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-envelope me-2"></i> <strong>Verification Code Sent</strong>
                                <p class="mb-0">A 6-digit verification code has been sent to the school email. Please enter it below to continue.</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email Verification Code *</label>
                                <input type="text" class="form-control" id="verificationCode" placeholder="Enter 6-digit code" maxlength="6" pattern="[0-9]*">
                                <small class="text-muted">Enter the code sent to the school email (expires in 15 minutes)</small>
                            </div>
                            
                            <button type="button" class="btn btn-primary" id="verifyCodeBtn">
                                <i class="fas fa-check"></i> Verify Email Code
                            </button>
                            
                            <button type="button" class="btn btn-outline-primary" id="resendCodeBtn" onclick="resendVerificationCode()">
                                <i class="fas fa-redo"></i> Resend Code
                            </button>
                        </div>
                        
                        <div id="smsCodeVerification" style="display: none;">
                            <div class="alert alert-info">
                                <i class="fas fa-sms me-2"></i> <strong>SMS Verification Code Sent</strong>
                                <p class="mb-0">A different 6-digit verification code has been sent to the school phone. Please enter it below to continue.</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">SMS Verification Code *</label>
                                <input type="text" class="form-control" id="smsVerificationCode" placeholder="Enter 6-digit SMS code" maxlength="6" pattern="[0-9]*">
                                <small class="text-muted">Enter the code sent to the school phone (expires in 15 minutes)</small>
                            </div>
                            
                            <button type="button" class="btn btn-primary" id="verifySmsCodeBtn">
                                <i class="fas fa-check"></i> Verify SMS Code
                            </button>
                        </div>
                        
                        <div id="withdrawalAmountSection" style="display: none;">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Withdrawal Amount (KES) *</label>
                                    <input type="number" name="withdrawal_amount" class="form-control" id="withdrawalAmount" required step="0.01" min="0.01" placeholder="Enter amount">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea name="notes" class="form-control" rows="1" placeholder="Any additional notes..."></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" name="process_withdrawal" class="btn btn-primary">
                                <i class="fas fa-money-bill-wave"></i> Process Withdrawal & Give Cash
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- School Accounts Table -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-wallet me-2"></i>School Account Balances
                </div>
                <div class="card-body">
                    <?php if (empty($schools)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-school" style="font-size: 48px; color: #dadce0; margin-bottom: 16px;"></i>
                            <h5>No Schools Found</h5>
                            <p class="text-muted">No schools registered in the system yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>School Code</th>
                                        <th>School Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Balance (KES)</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schools as $school): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($school['school_code']); ?></td>
                                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                            <td><?php echo htmlspecialchars($school['email']); ?></td>
                                            <td><?php echo htmlspecialchars($school['phone']); ?></td>
                                            <td>
                                                <?php
                                                $balance = floatval($school['balance']);
                                                if ($balance > 0) {
                                                    echo '<span class="balance-positive">KES ' . number_format($balance, 2) . '</span>';
                                                } elseif ($balance < 0) {
                                                    echo '<span class="balance-negative">KES ' . number_format($balance, 2) . '</span>';
                                                } else {
                                                    echo '<span class="balance-zero">KES 0.00</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($school['balance_created_at']) {
                                                    echo date('M j, Y', strtotime($school['balance_created_at']));
                                                } else {
                                                    echo '<span class="text-muted">Never</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="text-muted">View Only</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            linksContainer.classList.toggle('collapsed');
            titleElement.classList.toggle('collapsed');
        }
        
        // School code lookup
        const schoolCodeInput = document.getElementById('schoolCode');
        const schoolLookupStatus = document.getElementById('schoolLookupStatus');
        const schoolDetails = document.getElementById('schoolDetails');
        const schoolFullDetails = document.getElementById('schoolFullDetails');
        const pinVerification = document.getElementById('pinVerification');
        const codeVerification = document.getElementById('codeVerification');
        const smsCodeVerification = document.getElementById('smsCodeVerification');
        const withdrawalAmountSection = document.getElementById('withdrawalAmountSection');
        
        let lookupTimeout;
        let currentSchool = null;
        
        schoolCodeInput.addEventListener('input', function() {
            clearTimeout(lookupTimeout);
            const code = this.value.trim();
            
            if (code.length < 3) {
                schoolLookupStatus.textContent = '';
                schoolDetails.style.display = 'none';
                schoolFullDetails.style.display = 'none';
                pinVerification.style.display = 'none';
                codeVerification.style.display = 'none';
                smsCodeVerification.style.display = 'none';
                withdrawalAmountSection.style.display = 'none';
                return;
            }
            
            schoolLookupStatus.textContent = 'Searching...';
            
            lookupTimeout = setTimeout(function() {
                fetch('school-accounts.php?action=lookup_school&school_code=' + encodeURIComponent(code))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            schoolLookupStatus.textContent = '';
                            schoolLookupStatus.style.color = '';
                            
                            currentSchool = data.school;
                            document.getElementById('displaySchoolName').textContent = data.school.school_name;
                            document.getElementById('displayEmail').textContent = data.school.email;
                            document.getElementById('displayPhone').textContent = data.school.phone;
                            document.getElementById('displayBalance').textContent = 'KES ' + parseFloat(data.school.current_balance).toFixed(2);
                            
                            document.getElementById('schoolName').value = data.school.school_name;
                            document.getElementById('schoolEmail').value = data.school.email;
                            
                            schoolDetails.style.display = 'block';
                            schoolFullDetails.style.display = 'none';
                            pinVerification.style.display = 'none';
                            codeVerification.style.display = 'none';
                            smsCodeVerification.style.display = 'none';
                            withdrawalAmountSection.style.display = 'none';
                        } else {
                            schoolLookupStatus.textContent = data.error || 'School not found';
                            schoolLookupStatus.style.color = '#d93025';
                            schoolDetails.style.display = 'none';
                            schoolFullDetails.style.display = 'none';
                            pinVerification.style.display = 'none';
                            codeVerification.style.display = 'none';
                            smsCodeVerification.style.display = 'none';
                            withdrawalAmountSection.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        schoolLookupStatus.textContent = 'Error looking up school';
                        schoolLookupStatus.style.color = '#d93025';
                        schoolDetails.style.display = 'none';
                        schoolFullDetails.style.display = 'none';
                        pinVerification.style.display = 'none';
                        codeVerification.style.display = 'none';
                        smsCodeVerification.style.display = 'none';
                        withdrawalAmountSection.style.display = 'none';
                    });
            }, 500);
        });
        
        // Add event listener for verify email button when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            const verifyBtn = document.getElementById('verifyEmailBtn');
            if (verifyBtn) {
                verifyBtn.addEventListener('click', verifyEmail);
            }
            
            const verifyPinBtn = document.getElementById('verifyPinBtn');
            if (verifyPinBtn) {
                verifyPinBtn.addEventListener('click', verifyPin);
            }
            
            const verifyCodeBtn = document.getElementById('verifyCodeBtn');
            if (verifyCodeBtn) {
                verifyCodeBtn.addEventListener('click', verifyCode);
            }
            
            const verifySmsCodeBtn = document.getElementById('verifySmsCodeBtn');
            if (verifySmsCodeBtn) {
                verifySmsCodeBtn.addEventListener('click', verifySmsCode);
            }
        });
        
        function verifyEmail() {
            console.log('verifyEmail called');
            
            if (!currentSchool) {
                console.error('currentSchool is null');
                showFormError('Please search for a school first');
                return;
            }
            
            const verifyEmailInput = document.getElementById('verifyEmail');
            const verifyEmailValue = verifyEmailInput.value.trim();
            
            console.log('Email entered:', verifyEmailValue);
            console.log('Current school email:', currentSchool.email);
            
            if (!verifyEmailValue) {
                showFormError('Please enter the school email');
                return;
            }
            
            if (verifyEmailValue !== currentSchool.email) {
                showFormError('Email does not match. Please check and try again.');
                return;
            }
            
            // Email verified, show full details and PIN verification
            schoolFullDetails.style.display = 'block';
            pinVerification.style.display = 'block';
            document.getElementById('verifyEmailBtn').style.display = 'none';
            verifyEmailInput.disabled = true;
            hideFormError();
        }
        
        function showFormError(message) {
            const errorDiv = document.getElementById('formErrors');
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i>' + message;
            errorDiv.style.display = 'block';
        }
        
        function hideFormError() {
            document.getElementById('formErrors').style.display = 'none';
        }
        
        function verifyPin() {
            const schoolPin = document.getElementById('schoolPin').value.trim();
            
            if (!schoolPin) {
                showFormError('Please enter the school PIN');
                return;
            }
            
            console.log('Verifying PIN for school:', currentSchool.id);
            
            // Verify PIN via AJAX
            fetch('school-accounts.php?action=verify_pin&school_id=' + currentSchool.id + '&pin=' + encodeURIComponent(schoolPin))
                .then(response => response.json())
                .then(data => {
                    console.log('PIN verification response:', data);
                    if (data.success) {
                        // PIN verified, show code verification section
                        codeVerification.style.display = 'block';
                        document.getElementById('verifyPinBtn').style.display = 'none';
                        document.getElementById('schoolPin').disabled = true;
                        hideFormError();
                    } else {
                        showFormError(data.error || 'Invalid PIN. Please try again.');
                    }
                })
                .catch(error => {
                    console.error('PIN verification error:', error);
                    showFormError('Error verifying PIN. Please try again.');
                });
        }
        
        function verifyCode() {
            const verificationCode = document.getElementById('verificationCode').value.trim();
            
            if (!verificationCode) {
                showFormError('Please enter the verification code');
                return;
            }
            
            // Verify code via AJAX
            fetch('school-accounts.php?action=verify_code&school_id=' + currentSchool.id + '&code=' + encodeURIComponent(verificationCode))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Email code verified, show SMS verification section
                        smsCodeVerification.style.display = 'block';
                        document.getElementById('verifyCodeBtn').style.display = 'none';
                        document.getElementById('resendCodeBtn').style.display = 'none';
                        document.getElementById('verificationCode').disabled = true;
                        hideFormError();
                    } else {
                        showFormError(data.error || 'Invalid verification code. Please try again.');
                    }
                })
                .catch(error => {
                    showFormError('Error verifying code. Please try again.');
                });
        }
        
        function verifySmsCode() {
            const smsVerificationCode = document.getElementById('smsVerificationCode').value.trim();
            
            if (!smsVerificationCode) {
                showFormError('Please enter the SMS verification code');
                return;
            }
            
            // Verify SMS code via AJAX
            fetch('school-accounts.php?action=verify_sms_code&school_id=' + currentSchool.id + '&sms_code=' + encodeURIComponent(smsVerificationCode))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // SMS code verified, show withdrawal amount section
                        withdrawalAmountSection.style.display = 'block';
                        document.getElementById('verifySmsCodeBtn').style.display = 'none';
                        document.getElementById('smsVerificationCode').disabled = true;
                        hideFormError();
                    } else {
                        showFormError(data.error || 'Invalid SMS verification code. Please try again.');
                    }
                })
                .catch(error => {
                    showFormError('Error verifying SMS code. Please try again.');
                });
        }
        
        function resendVerificationCode() {
            const schoolPin = document.getElementById('schoolPin').value.trim();
            
            if (!schoolPin) {
                showFormError('PIN verification required before resending code');
                return;
            }
            
            // Resend verification code by calling verify_pin again
            fetch('school-accounts.php?action=verify_pin&school_id=' + currentSchool.id + '&pin=' + encodeURIComponent(schoolPin))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showFormError('New verification code sent to email');
                    } else {
                        showFormError(data.error || 'Failed to resend code');
                    }
                })
                .catch(error => {
                    showFormError('Error resending code. Please try again.');
                });
        }
    </script>
</body>
</html>
