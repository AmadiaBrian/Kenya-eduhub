<?php
/**
 * Finance manager withdrawal processor.
 * Phone withdrawals are sent through sandbox M-Pesa B2C. Other withdrawal
 * methods are saved as pending requests for manual/future processor handling.
 */

session_start();
require_once __DIR__ . '/../../config.php';

// Load M-Pesa configuration
$mpesa_config = require __DIR__ . '/../config/mpesa_config.php';
$base_url = $mpesa_config['callback_base_url'];

function normalizeMpesaPhone(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone);

    // Handle 9-digit format (7XXXXXXXX)
    if (strlen($phone) === 9 && preg_match('/^[17]/', $phone)) {
        return '254' . $phone;
    }

    // Handle 10-digit format (07XXXXXXXX or 01XXXXXXXX)
    if (strlen($phone) === 10 && $phone[0] === '0' && preg_match('/^0[17]/', $phone)) {
        return '254' . substr($phone, 1);
    }

    // Handle 12-digit format (2547XXXXXXXX or 2541XXXXXXXX)
    if (strlen($phone) === 12 && strpos($phone, '254') === 0 && preg_match('/^254[17]/', $phone)) {
        return $phone;
    }

    // Handle 13-digit format (+2547XXXXXXXX or +2541XXXXXXXX)
    if (strlen($phone) === 13 && strpos($phone, '254') === 0 && preg_match('/^254[17]/', $phone)) {
        return $phone;
    }

    // Handle any format that starts with 7 or 1 and is 9-13 digits
    if (preg_match('/^[17]/', $phone) && strlen($phone) >= 9 && strlen($phone) <= 13) {
        // Extract the last 9 digits and prepend 254
        $last9 = substr($phone, -9);
        if (preg_match('/^[17]/', $last9)) {
            return '254' . $last9;
        }
    }

    throw new InvalidArgumentException('Invalid phone number format. Use 07XXXXXXXX or 254XXXXXXXXX.');
}

function getPendingWithdrawalTotal(PDO $pdo, int $schoolId): float
{
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM school_withdrawals WHERE school_id = ? AND status = 'pending'");
    $stmt->execute([$schoolId]);
    return (float) $stmt->fetchColumn();
}

function createWithdrawal(PDO $pdo, array $withdrawal): int
{
    $stmt = $pdo->prepare("INSERT INTO school_withdrawals (school_id, finance_manager_id, amount, destination_type, destination_name, destination_account, destination_extra, notes, status, reference_number, result_desc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?)");
    $stmt->execute([
        $withdrawal['school_id'],
        $withdrawal['finance_manager_id'],
        $withdrawal['amount'],
        $withdrawal['destination_type'],
        $withdrawal['destination_name'],
        $withdrawal['destination_account'],
        $withdrawal['destination_extra'],
        $withdrawal['notes'],
        $withdrawal['reference_number'],
        $withdrawal['result_desc']
    ]);

    return (int) $pdo->lastInsertId();
}

function generateSecurityCredential(string $initiatorPassword, string $mpesaApiUrl): string
{
    // Determine if we're using sandbox or production
    $isSandbox = strpos($mpesaApiUrl, 'sandbox') !== false;
    
    // Select the appropriate certificate
    if ($isSandbox) {
        $certificateUrl = 'https://developer.safaricom.co.ke/certificates/SandboxCertificate.cer';
        $certificatePath = __DIR__ . '/SandboxCertificate.cer';
    } else {
        $certificateUrl = 'https://developer.safaricom.co.ke/certificates/ProductionCertificate.cer';
        $certificatePath = __DIR__ . '/ProductionCertificate.cer';
    }
    
    // Download certificate if not present
    if (!file_exists($certificatePath)) {
        $certContent = @file_get_contents($certificateUrl);
        if ($certContent === false) {
            throw new RuntimeException("Failed to download certificate from $certificateUrl");
        }
        file_put_contents($certificatePath, $certContent);
    }
    
    // Load the certificate
    $publicKey = file_get_contents($certificatePath);
    if ($publicKey === false) {
        throw new RuntimeException("Failed to load certificate from $certificatePath");
    }
    
    // Encrypt the password
    $encrypted = '';
    if (!openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
        throw new RuntimeException("Failed to encrypt initiator password: " . openssl_error_string());
    }
    
    // Base64 encode the encrypted password
    return base64_encode($encrypted);
}

function getPublicBaseUrl(): string
{
    global $base_url;

    return rtrim($base_url, '/');
}

function respondOrRedirect(array $payload, string $redirectUrl = '../account.php'): void
{
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $isJson = stripos($accept, 'application/json') !== false || strtolower($requestedWith) === 'xmlhttprequest';

    if ($isJson) {
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    $key = !empty($payload['success']) ? 'success' : 'error';
    $message = $payload['message'] ?? (!empty($payload['success']) ? 'Withdrawal request sent.' : 'Withdrawal request failed.');
    header('Location: ' . $redirectUrl . '?' . http_build_query([$key => $message]));
    exit;
}

if (!isset($_SESSION['finance_manager_id'], $_SESSION['school_id'])) {
    respondOrRedirect(['success' => false, 'message' => 'Unauthorized. Please log in again.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondOrRedirect(['success' => false, 'message' => 'Invalid request method.']);
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    respondOrRedirect(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
}

$withdrawalId = null;
$referenceNumber = null;

try {
    $financeManagerId = (int) $_SESSION['finance_manager_id'];
    $schoolId = (int) $_SESSION['school_id'];
    
    // Get school phone number and name for phone withdrawals
    $schoolPhone = '';
    $schoolName = '';
    try {
        $stmt = $pdo->prepare("SELECT phone, school_name FROM schools WHERE id = ?");
        $stmt->execute([$schoolId]);
        $schoolData = $stmt->fetch();
        if ($schoolData) {
            $schoolPhone = $schoolData['phone'] ?? '';
            $schoolName = $schoolData['school_name'] ?? '';
        }
    } catch (PDOException $e) {
        error_log("Failed to fetch school data: " . $e->getMessage());
    }
    
    // Rate limiting: Max 5 withdrawal requests per 5 minutes per finance manager
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM school_withdrawals 
                          WHERE finance_manager_id = ? 
                          AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $stmt->execute([$financeManagerId]);
    $recentRequests = (int) $stmt->fetchColumn();
    
    if ($recentRequests >= 5) {
        respondOrRedirect(['success' => false, 'message' => 'Too many withdrawal requests. Please wait a few minutes before trying again.']);
    }
    
    $amount = (int) round((float) ($_POST['amount'] ?? 0));
    $destinationType = strtolower(trim($_POST['destination_type'] ?? 'phone'));
    $recipientName = trim($_POST['destination_name'] ?? '');
    $destinationAccount = trim($_POST['destination_account'] ?? '');
    $extra = trim($_POST['destination_extra'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $allowedDestinationTypes = ['phone', 'till', 'paybill', 'bank', 'other'];

    if (!in_array($destinationType, $allowedDestinationTypes, true)) {
        throw new InvalidArgumentException('Select a valid withdrawal method.');
    }

    if ($amount < 1) {
        throw new InvalidArgumentException('Enter a valid withdrawal amount.');
    }

    if ($destinationAccount === '') {
        throw new InvalidArgumentException('Enter the destination number, account, or details.');
    }

    if ($destinationType === 'bank' && $recipientName === '') {
        throw new InvalidArgumentException('Enter the bank name.');
    }

    $phone = null;
    if ($destinationType === 'phone') {
        if ($amount < 10) {
            throw new InvalidArgumentException('Minimum B2C phone withdrawal amount is KES 10.');
        }

        // Use school phone number if available, otherwise use provided account
        if ($schoolPhone) {
            try {
                $phone = normalizeMpesaPhone($schoolPhone);
                error_log("Using school phone: $schoolPhone -> Normalized: $phone");
            } catch (InvalidArgumentException $e) {
                // If school phone is invalid, use provided account
                error_log("School phone invalid: " . $e->getMessage() . ". Using provided account.");
                $phone = normalizeMpesaPhone($destinationAccount);
                error_log("Using provided account: $destinationAccount -> Normalized: $phone");
            }
        } else {
            $phone = normalizeMpesaPhone($destinationAccount);
            error_log("Using provided account: $destinationAccount -> Normalized: $phone");
        }
        
        // For sandbox testing, override with valid test number if needed
        // M-Pesa sandbox only accepts registered test numbers
        $sandboxTestNumbers = ['254708374149', '254724540000', '254700000000'];
        if (strpos($mpesa_config['b2c_url'], 'sandbox') !== false) {
            // In sandbox, use a valid test number instead of the actual phone
            $phone = '254708374149'; // Valid M-Pesa sandbox test number
            error_log("Sandbox mode: Using test number $phone instead of actual phone");
        }
        
        // Validate the final phone number
        if (empty($phone) || strlen($phone) !== 12 || strpos($phone, '254') !== 0) {
            error_log("Invalid phone after normalization: $phone");
            throw new InvalidArgumentException('Invalid phone number format. Please ensure the school phone number is in the correct format (254XXXXXXXXX).');
        }
        
        $destinationAccount = $phone;
        error_log("Final phone for B2C: $destinationAccount");
        
        // Use school name as recipient name for phone withdrawals
        if ($schoolName && empty($recipientName)) {
            $recipientName = $schoolName;
        }
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT balance FROM school_balances WHERE school_id = ? FOR UPDATE");
    $stmt->execute([$schoolId]);
    $schoolBalance = $stmt->fetchColumn();

    if ($schoolBalance === false) {
        throw new RuntimeException('School balance account was not found.');
    }

    $pendingTotal = getPendingWithdrawalTotal($pdo, $schoolId);
    $availableToWithdraw = (float) $schoolBalance - $pendingTotal;

    error_log("Balance check - School ID: $schoolId, Balance: $schoolBalance, Pending: $pendingTotal, Available: $availableToWithdraw, Requested: $amount");

    if ($amount > $availableToWithdraw) {
        throw new RuntimeException("Insufficient balance. Available: KES $availableToWithdraw, Requested: KES $amount (after pending withdrawals: KES $pendingTotal)");
    }

    $referenceNumber = 'WDR-' . date('YmdHis') . '-' . $schoolId . '-' . random_int(100, 999);
    $remarks = $notes !== '' ? $notes : 'School withdrawal ' . $referenceNumber;

    $initialResultDesc = $destinationType === 'phone'
        ? 'B2C request created. Waiting for Safaricom response.'
        : ucfirst($destinationType) . ' withdrawal request created. Awaiting manual processing.';

    $withdrawalId = createWithdrawal($pdo, [
        'school_id' => $schoolId,
        'finance_manager_id' => $financeManagerId,
        'amount' => $amount,
        'destination_type' => $destinationType,
        'destination_name' => $recipientName !== '' ? $recipientName : null,
        'destination_account' => $destinationAccount,
        'destination_extra' => $extra !== '' ? $extra : null,
        'notes' => $notes !== '' ? $notes : null,
        'reference_number' => $referenceNumber,
        'result_desc' => $initialResultDesc
    ]);

    $pdo->commit();

    if ($destinationType !== 'phone') {
        respondOrRedirect([
            'success' => true,
            'message' => ucfirst($destinationType) . ' withdrawal request saved as pending. Balance will update after it is processed successfully. Reference: ' . $referenceNumber,
            'reference_number' => $referenceNumber,
            'withdrawal_id' => $withdrawalId
        ]);
    }

    date_default_timezone_set('Africa/Nairobi');
    $businessShortCode = $mpesa_config['b2c']['shortcode'];
    $initiatorName = $mpesa_config['b2c']['initiator_name'];
    $initiatorPassword = $mpesa_config['b2c']['initiator_password'];
    $commandId = $mpesa_config['b2c']['command_id'];
    
    // Generate security credential dynamically
    $securityCredential = generateSecurityCredential($initiatorPassword, $mpesa_config['b2c_url']);

    error_log("=== B2C Configuration ===");
    error_log("Business Shortcode (PartyA): $businessShortCode");
    error_log("Initiator Name: $initiatorName");
    error_log("Security Credential (first 20 chars): " . substr($securityCredential, 0, 20) . "...");
    error_log("Command ID: $commandId");
    error_log("Phone (PartyB): $phone");

    require __DIR__ . '/accessToken.php';

    if (empty($access_token)) {
        $message = 'Failed to get M-Pesa access token.';
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_desc = ? WHERE id = ?");
        $stmt->execute([$message, $withdrawalId]);
        throw new RuntimeException($message);
    }
    
    error_log("Access Token obtained (first 20 chars): " . substr($access_token, 0, 20) . "...");

    $baseUrl = getPublicBaseUrl();
    $queueTimeoutUrl = $baseUrl . $mpesa_config['b2c_timeout_url'];
    $resultUrl = $baseUrl . $mpesa_config['b2c_result_url'];

    $b2cData = [
        'InitiatorName' => $initiatorName,
        'SecurityCredential' => $securityCredential,
        'CommandID' => $commandId,
        'Amount' => $amount,
        'PartyA' => $businessShortCode,
        'PartyB' => $phone,
        'Remarks' => $remarks,
        'QueueTimeOutURL' => $queueTimeoutUrl,
        'ResultURL' => $resultUrl,
        'Occasion' => $referenceNumber
    ];

    $dataString = json_encode($b2cData);
    error_log("B2C Request Data: " . $dataString);
    
    $curl = curl_init('https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest');
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $dataString);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    $b2cResponse = curl_exec($curl);
    $b2cError = curl_error($curl);
    $b2cStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    error_log('Finance B2C request: ' . $dataString);
    error_log("Finance B2C response HTTP $b2cStatus: " . $b2cResponse);
    error_log("Finance B2C phone: " . $phone);

    if ($b2cError) {
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_desc = ? WHERE id = ?");
        $stmt->execute(['M-Pesa B2C connection error: ' . $b2cError, $withdrawalId]);
        throw new RuntimeException('M-Pesa B2C connection error: ' . $b2cError);
    }

    $responseData = json_decode($b2cResponse, true);
    if ($b2cStatus !== 200 || ($responseData['ResponseCode'] ?? '') !== '0') {
        $message = $responseData['errorMessage'] ?? $responseData['ResponseDescription'] ?? 'Failed to initiate B2C withdrawal.';
        
        // Provide specific guidance for customer type errors
        if (strpos($message, 'customer type') !== false || strpos($message, 'Customer') !== false) {
            $message = 'The recipient phone number is not supported by the current payment type. Please try a different withdrawal method or contact support.';
        }
        
        // Provide specific guidance for ReceiverParty errors
        if (strpos($message, 'ReceiverParty') !== false || strpos($message, 'receiver') !== false) {
            $message = 'The recipient phone number is invalid. Please ensure the school phone number is correct and in M-Pesa format (254XXXXXXXXX). Contact support if the issue persists.';
        }
        
        // Provide specific guidance for Initiator errors
        if (strpos($message, 'Initiator') !== false || strpos($message, 'initiator') !== false) {
            $message = 'The M-Pesa initiator credentials are invalid. Please check the Initiator Name and Security Credential in the configuration file (finance-managers/config/mpesa_config.php). These must be obtained from the Safaricom Developer Portal.';
        }
        
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_code = ?, result_desc = ?, callback_payload = ? WHERE id = ?");
        $stmt->execute([
            $responseData['ResponseCode'] ?? null,
            $message,
            $b2cResponse,
            $withdrawalId
        ]);
        throw new RuntimeException($message);
    }

    // Update the pending withdrawal with Safaricom transaction identifiers.
    $stmt = $pdo->prepare("UPDATE school_withdrawals SET originator_conversation_id = ?, conversation_id = ?, result_desc = ? WHERE id = ?");
    $stmt->execute([
        $responseData['OriginatorConversationID'] ?? null,
        $responseData['ConversationID'] ?? null,
        $responseData['ResponseDescription'] ?? 'B2C request accepted. Waiting for callback.',
        $withdrawalId
    ]);

    respondOrRedirect([
        'success' => true,
        'message' => 'B2C withdrawal request sent successfully. Balance will update after successful callback. Reference: ' . $referenceNumber,
        'reference_number' => $referenceNumber,
        'withdrawal_id' => $withdrawalId,
        'conversation_id' => $responseData['ConversationID'] ?? null
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($withdrawalId) {
        $stmt = $pdo->prepare("UPDATE school_withdrawals SET status = 'failed', result_desc = ? WHERE id = ?");
        $stmt->execute([$e->getMessage(), $withdrawalId]);
    }

    error_log('Finance B2C withdrawal failed: ' . $e->getMessage());
    respondOrRedirect(['success' => false, 'message' => $e->getMessage()]);
}
