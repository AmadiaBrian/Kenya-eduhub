<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config.php';
require_once '../includes/helpers.php';

// Include PHPMailer
require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

// Function to send email code
function sendResetCodeEmail($email, $code) {
    $smtp_settings = getSMTPSettings();
    if (!$smtp_settings) {
        error_log("SMTP settings not configured in database");
        return false;
    }

    $smtp_host = $smtp_settings['smtp_host'];
    $smtp_port = $smtp_settings['smtp_port'];
    $smtp_username = $smtp_settings['smtp_username'];
    $smtp_password = $smtp_settings['smtp_password'];
    $email_from = $smtp_settings['email_from'];
    $encryption = $smtp_settings['encryption'];

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = $encryption;
        $mail->Port = $smtp_port;

        $mail->setFrom($email_from, 'Kenya EduHub');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Code - Kenya EduHub';
        $mail->Body = "
            <p>You requested a password reset.</p>
            <p>Your verification code is: <strong>$code</strong></p>
            <p>Code expires in 10 minutes.</p>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

// Function to send SMS code
function sendResetSMSCode($phone, $code) {
    global $conn;

    error_log("=== SMS SENDING START ===");
    error_log("Phone: $phone, Code: $code");

    $sql = "SELECT sms_provider, mobitech_api_key, mobitech_sender_id, textsms_api_key, textsms_partner_id, textsms_sender_id, sms_enabled FROM admin_sms_settings LIMIT 1";
    $result = $conn->query($sql);

    if (!$result || $result->num_rows === 0) {
        error_log("ERROR: SMS settings not configured in admin_sms_settings table");
        return false;
    }

    $settings = $result->fetch_assoc();

    if (!($settings['sms_enabled'] ?? 0)) {
        error_log("ERROR: SMS is disabled in admin settings (sms_enabled = 0)");
        return false;
    }

    $message = "Your Kenya EduHub password reset code is: $code. Valid for 10 minutes.";

    $smsConfigPath = __DIR__ . '/../sms/sms_config.php';
    $mobitechPath = __DIR__ . '/../sms/MobitechSMS.php';
    $textsmsPath = __DIR__ . '/../sms/TextSMS.php';

    if (!file_exists($smsConfigPath) || !file_exists($mobitechPath) || !file_exists($textsmsPath)) {
        error_log("ERROR: SMS class files not found");
        return false;
    }

    require_once $smsConfigPath;
    require_once $mobitechPath;
    require_once $textsmsPath;

    try {
        if ($settings['sms_provider'] === 'mobitech') {
            $senderId = $settings['mobitech_sender_id'] ?? 'FULL_CIRCLE';
            $mobitech = new MobitechSMS($settings['mobitech_api_key'], $senderId);
            $res = $mobitech->sendSMS($phone, $message);
            if (!$res['success'] && !empty($settings['textsms_api_key'])) {
                $textsms = new TextSMS($settings['textsms_api_key'], $settings['textsms_partner_id'], $settings['textsms_sender_id']);
                $res = $textsms->sendSMS($phone, $message);
            }
            return $res['success'] ?? false;
        } else {
            $textsms = new TextSMS($settings['textsms_api_key'], $settings['textsms_partner_id'], $settings['textsms_sender_id']);
            $res = $textsms->sendSMS($phone, $message);
            return $res['success'] ?? false;
        }
    } catch (Exception $e) {
        error_log("ERROR: Exception during SMS sending: " . $e->getMessage());
        return false;
    }
}

// Get request data (from JSON body or POST form data)
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true) ?? $_POST;

$step = (int)($data['step'] ?? 1);
$method = trim($data['method'] ?? 'email');
$email = trim($data['email'] ?? $data['identifier'] ?? '');
$phone = trim($data['phone'] ?? $data['identifier'] ?? '');
$code = trim($data['code'] ?? '');
$password = $data['new_password'] ?? $data['password'] ?? '';

// Step 1: Send verification code
if ($step === 1) {
    if ($method === 'sms') {
        if (empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Phone number is required']);
            exit;
        }
        if (!preg_match('/^(07|01)[0-9]{8}$/', $phone) && !preg_match('/^\+254[71][0-9]{8}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Invalid phone format. Use Kenya format: 07XXXXXXXX or +2547XXXXXXXX']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, email FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Phone number not found in our system']);
            exit;
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE phone = ?");
        $update->bind_param("sss", $code, $expires, $phone);
        if ($update->execute()) {
            sendResetSMSCode($phone, $code); // attempt sending SMS
            $_SESSION['reset_phone'] = $phone;
            $_SESSION['reset_method'] = 'sms';
            $_SESSION['step'] = 2;
            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent to your phone.',
                'nextStep' => 2
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate reset code. Please try again.']);
        }
    } else {
        // Email method
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email address is required']);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Email not found in our system']);
            exit;
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $update = $conn->prepare("UPDATE users SET reset_code = ?, reset_expires_at = ? WHERE email = ?");
        $update->bind_param("sss", $code, $expires, $email);
        if ($update->execute()) {
            sendResetCodeEmail($email, $code); // attempt sending Email
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_method'] = 'email';
            $_SESSION['step'] = 2;
            echo json_encode([
                'success' => true,
                'message' => 'Verification code sent to your email.',
                'nextStep' => 2
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to generate reset code. Please try again.']);
        }
    }
    exit;
}

// Step 2: Verify code
if ($step === 2) {
    if (empty($code)) {
        echo json_encode(['success' => false, 'message' => 'Verification code is required']);
        exit;
    }

    if ($method === 'sms' && !empty($phone)) {
        $stmt = $conn->prepare("SELECT reset_expires_at FROM users WHERE phone = ? AND reset_code = ?");
        $stmt->bind_param("ss", $phone, $code);
    } elseif (!empty($email)) {
        $stmt = $conn->prepare("SELECT reset_expires_at FROM users WHERE email = ? AND reset_code = ?");
        $stmt->bind_param("ss", $email, $code);
    } else {
        $stmt = $conn->prepare("SELECT reset_expires_at FROM users WHERE reset_code = ?");
        $stmt->bind_param("s", $code);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        if (strtotime($row['reset_expires_at']) > time()) {
            $_SESSION['step'] = 3;
            echo json_encode([
                'success' => true,
                'message' => 'Code verified. Please enter your new password.',
                'nextStep' => 3
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Verification code has expired']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid verification code']);
    }
    exit;
}

// Step 3: Reset password
if ($step === 3) {
    if (empty($password)) {
        echo json_encode(['success' => false, 'message' => 'New password is required']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit;
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);

    if ($method === 'sms' && !empty($phone)) {
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE phone = ?");
        $stmt->bind_param("ss", $hashed, $phone);
    } elseif (!empty($email)) {
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hashed, $email);
    } else {
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_code = NULL, reset_expires_at = NULL WHERE reset_code = ?");
        $stmt->bind_param("ss", $hashed, $code);
    }

    if ($stmt->execute()) {
        session_unset();
        session_destroy();
        echo json_encode([
            'success' => true,
            'message' => 'Password reset successfully. You can now login.',
            'nextStep' => 3
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update password. Please try again.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request step']);
?>