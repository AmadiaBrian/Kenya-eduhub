<?php
// M-Pesa Fine Payment API for Parents
error_reporting(0); // Disable error reporting to prevent JSON corruption
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../config/mpesa_config.php';

header('Content-Type: application/json');

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized - No parent session found']);
    exit;
}

$parent_id = $_SESSION['parent_id'];

// Use centralized M-Pesa configuration
$base_url = MPESA_CALLBACK_BASE_URL;

// Database connection is already handled by config.php ($pdo)

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get and validate input
    $fine_id = $_POST['fine_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $phone = $_POST['phone'] ?? null;
    
    if (!$fine_id || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Fine ID and amount are required']);
        exit;
    }
    
    if (!$phone) {
        echo json_encode(['success' => false, 'error' => 'Phone number is required']);
        exit;
    }
    
    // Verify this fine belongs to one of the parent's children
    $stmt = $pdo->prepare("SELECT lf.*, s.id as student_id 
                          FROM library_fines lf
                          JOIN students s ON lf.user_id = s.id AND lf.user_type = 'student'
                          JOIN student_parents sp ON s.id = sp.student_id
                          WHERE lf.id = ? AND sp.parent_id = ?");
    $stmt->execute([$fine_id, $parent_id]);
    $fine = $stmt->fetch();
    
    if (!$fine) {
        echo json_encode(['success' => false, 'error' => 'Fine not found or you do not have permission to pay this fine']);
        exit;
    }
    
    // Validate amount
    $amount = floatval($amount);
    $remaining_amount = $fine['amount'] - $fine['amount_paid'];
    
    if ($amount <= 0 || $amount > $remaining_amount) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount. Maximum payable: ' . $remaining_amount]);
        exit;
    }
    
    // Clean and format phone number for M-Pesa
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 9) {
        $phone = '254' . $phone;
    } elseif (strlen($phone) === 10 && $phone[0] === '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (strlen($phone) === 12 && strpos($phone, '254') === 0) {
        // Already in correct format
    } elseif (strlen($phone) === 13 && strpos($phone, '+254') === 0) {
        // Remove + and use 254
        $phone = '254' . substr($phone, 4);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid phone number format']);
        exit;
    }
    
    // Generate unique receipt number
    $receipt_number = 'FINE-' . strtoupper(uniqid()) . '-' . $fine_id;
    
    // Store original values for rollback
    $original_amount_paid = $fine['amount_paid'];
    $original_status = $fine['status'];
    
    // Set fine to pending status WITHOUT changing amount_paid yet
    $stmt = $pdo->prepare("UPDATE library_fines 
                          SET status = 'pending', payment_method = 'mpesa' 
                          WHERE id = ?");
    $stmt->execute([$fine_id]);
    
    // Log the payment initiation
    $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'fine_payment_initiated', ?, 'parent', ?)");
    $stmt->execute([$fine['book_id'], $fine['school_id'], $parent_id, "MPESA payment initiated: $amount for fine ID: $fine_id"]);
    
    // Include existing accessToken.php for M-Pesa access token
    include __DIR__ . '/accessToken.php';
    
    if (!$access_token) {
        // Revert fine status on token failure
        $stmt = $pdo->prepare("UPDATE library_fines SET amount_paid = ?, status = ?, payment_method = NULL WHERE id = ?");
        $stmt->execute([$original_amount_paid, $original_status, $fine_id]);
        echo json_encode(['success' => false, 'error' => 'Failed to get M-Pesa access token']);
        exit;
    }
    
    // M-Pesa STK Push
    date_default_timezone_set('Africa/Nairobi');
    $processrequestUrl = MPESA_STK_PUSH_URL;
    $passkey = MPESA_PASSKEY;
    $BusinessShortCode = MPESA_BUSINESS_SHORTCODE;
    $PartyA = $phone;
    $PartyB = $BusinessShortCode;
    $Timestamp = date('YmdHis');
    $Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);
    
    // Account reference with payment details
    $accountnumber = "FINE:$fine_id|BOOK:{$fine['book_id']}|AMOUNT:$amount|USER:{$fine['user_id']}";
    
    // Callback URL using configured base URL
    $callbackurl = $base_url . '/Kenyaeduhub/parents/api/mpesa_fine_callback.php';
    
    $stkpushheader = ['Content-Type:application/json', 'Authorization:Bearer ' . $access_token];
    
    $curl_post_data = [
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => intval(round($amount)),
        'PartyA' => $phone,
        'PartyB' => $PartyB,
        'PhoneNumber' => $phone,
        'CallBackURL' => $callbackurl,
        'AccountReference' => $accountnumber,
        'TransactionDesc' => 'Library Fine Payment'
    ];
    
    $data_string = json_encode($curl_post_data);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $processrequestUrl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkpushheader);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    $curl_response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Log the request and response for debugging
    error_log("M-Pesa STK Push Request: " . $data_string);
    error_log("M-Pesa STK Push Response: " . $curl_response);
    error_log("M-Pesa HTTP Code: " . $httpCode);
    error_log("M-Pesa Phone: " . $phone);
    
    if ($error) {
        // Revert fine status on curl error
        $stmt = $pdo->prepare("UPDATE library_fines SET amount_paid = ?, status = ?, payment_method = NULL WHERE id = ?");
        $stmt->execute([$original_amount_paid, $original_status, $fine_id]);
        echo json_encode(['success' => false, 'error' => 'M-Pesa connection error: ' . $error]);
        exit;
    }
    
    $data = json_decode($curl_response, true);
    
    if ($httpCode !== 200 || !isset($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
        // Revert fine status on API error
        $stmt = $pdo->prepare("UPDATE library_fines SET amount_paid = ?, status = ?, payment_method = NULL WHERE id = ?");
        $stmt->execute([$original_amount_paid, $original_status, $fine_id]);
        $errorMsg = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Failed to initiate payment';
        error_log("M-Pesa API Error: " . $errorMsg);
        echo json_encode(['success' => false, 'error' => $errorMsg, 'details' => $data]);
        exit;
    }
    
    // Update payment record with M-Pesa transaction IDs
    $CheckoutRequestID = $data['CheckoutRequestID'] ?? '';
    $MerchantRequestID = $data['MerchantRequestID'] ?? '';
    
    error_log("Storing CheckoutRequestID in fine record: $CheckoutRequestID for fine ID: $fine_id");
    
    $stmt = $pdo->prepare("UPDATE library_fines 
                          SET transaction_reference = ? 
                          WHERE id = ?");
    $stmt->execute([$CheckoutRequestID, $fine_id]);
    
    error_log("CheckoutRequestID stored successfully. Rows affected: " . $stmt->rowCount());
    
    // Verify the update
    $stmt = $pdo->prepare("SELECT transaction_reference FROM library_fines WHERE id = ?");
    $stmt->execute([$fine_id]);
    $updated_fine = $stmt->fetch();
    error_log("Fine transaction_reference after update: " . ($updated_fine['transaction_reference'] ?? 'NULL'));
    
    // Log the initiation
    error_log("M-Pesa fine payment initiated: Fine ID=$fine_id, Amount=$amount, CheckoutRequestID=$CheckoutRequestID");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment request sent successfully. Please check your phone to complete the payment.',
        'fine_id' => $fine_id,
        'receipt_number' => $receipt_number,
        'CheckoutRequestID' => $CheckoutRequestID,
        'MerchantRequestID' => $MerchantRequestID,
        'book_title' => $fine['title']
    ]);
    
} catch (PDOException $e) {
    error_log("M-Pesa fine payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("M-Pesa fine payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
