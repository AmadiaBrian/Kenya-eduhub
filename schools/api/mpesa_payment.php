<?php
// M-Pesa Fee Payment API for Parents
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

// Configure your actual callback URL here
$base_url = 'https://mothers-chest-counter-unfortunately.trycloudflare.com'; // Update this to your actual domain

// Database connection is already handled by config.php ($pdo)

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get and validate input
    $student_id = $_POST['student_id'] ?? null;
    $amount = $_POST['amount'] ?? null;
    // For sandbox testing, use registered test number if provided, otherwise use parent's phone
    $phone = $_POST['phone'] ?? $_SESSION['parent_phone'] ?? null; // Allow override with POST parameter
    $term = $_POST['term'] ?? 'Term 1';
    $year = $_POST['year'] ?? date('Y');
    $fee_type = $_POST['fee_type'] ?? 'Tuition';
    
    if (!$student_id || !$amount) {
        echo json_encode(['success' => false, 'error' => 'Student ID and amount are required']);
        exit;
    }
    
    if (!$phone) {
        echo json_encode(['success' => false, 'error' => 'Parent phone number not found. Please contact support.']);
        exit;
    }
    
    // Verify this child belongs to the parent (using schools database)
    $stmt = $pdo->prepare("SELECT s.id, s.first_name, s.last_name, c.class_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           WHERE s.id = ? AND sp.parent_id = ? AND s.status = 'active'");
    $stmt->execute([$student_id, $parent_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized access to this child']);
        exit;
    }
    
    // Validate amount
    $amount = floatval($amount);
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid amount']);
        exit;
    }
    
    // Clean and format phone number for M-Pesa
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Log original phone for debugging
    error_log("Original phone from session: " . $_SESSION['parent_phone']);
    error_log("Cleaned phone: " . $phone);
    
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
        error_log("Invalid phone format detected: " . $phone . " (length: " . strlen($phone) . ")");
        echo json_encode(['success' => false, 'error' => 'Invalid phone number format. Database phone: ' . $_SESSION['parent_phone'] . ', Cleaned: ' . $phone]);
        exit;
    }
    
    error_log("Final formatted phone for M-Pesa: " . $phone);
    
    // Generate unique receipt number
    $receipt_number = 'FEE-' . strtoupper(uniqid()) . '-' . $student_id;
    
    // Create pending payment record in schools database
    $stmt = $pdo->prepare("INSERT INTO fee_payments 
                          (student_id, amount, payment_date, payment_method, transaction_id, term, year, fee_type, receipt_number, created_at) 
                          VALUES (?, ?, CURDATE(), 'M-Pesa', NULL, ?, ?, ?, ?, NOW())");
    $stmt->execute([$student_id, $amount, $term, $year, $fee_type, $receipt_number]);
    $payment_id = $pdo->lastInsertId();
    
    // Include existing accessToken.php for M-Pesa access token
    include __DIR__ . '/accessToken.php';
    
    if (!$access_token) {
        // Delete pending payment on token failure
        $stmt = $pdo->prepare("DELETE FROM fee_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        echo json_encode(['success' => false, 'error' => 'Failed to get M-Pesa access token']);
        exit;
    }
    
    // M-Pesa STK Push (SANDBOX - using working Python credentials)
    date_default_timezone_set('Africa/Nairobi');
    $processrequestUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919"; // From working Python implementation
    $BusinessShortCode = '174379';
    $PartyA = $phone; // Use the actual phone number (like Python)
    $PartyB = $BusinessShortCode; // PartyB should be business shortcode for CustomerPayBillOnline
    $Timestamp = date('YmdHis');
    $Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);
    
    // Account reference with payment details
    $accountnumber = "FEE:$payment_id|STUDENT:$student_id|AMOUNT:$amount|TERM:$term|YEAR:$year|TYPE:$fee_type";
    
    // Callback URL using configured base URL
    $callbackurl = $base_url . '/Kenyaeduhub/schools/api/mpesa_callback.php';
    
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
        'TransactionDesc' => 'School Fee Payment'
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
        // Delete pending payment on curl error
        $stmt = $pdo->prepare("DELETE FROM fee_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        echo json_encode(['success' => false, 'error' => 'M-Pesa connection error: ' . $error]);
        exit;
    }
    
    $data = json_decode($curl_response, true);
    
    if ($httpCode !== 200 || !isset($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
        // Delete pending payment on API error
        $stmt = $pdo->prepare("DELETE FROM fee_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $errorMsg = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Failed to initiate payment';
        error_log("M-Pesa API Error: " . $errorMsg);
        error_log("Full Response: " . print_r($data, true));
        echo json_encode(['success' => false, 'error' => $errorMsg, 'details' => $data]);
        exit;
    }
    
    // Update payment record with M-Pesa transaction IDs
    $CheckoutRequestID = $data['CheckoutRequestID'] ?? '';
    $MerchantRequestID = $data['MerchantRequestID'] ?? '';
    
    $stmt = $pdo->prepare("UPDATE fee_payments 
                          SET transaction_id = ? 
                          WHERE id = ?");
    $stmt->execute([$CheckoutRequestID, $payment_id]);
    
    // Log the initiation
    error_log("M-Pesa payment initiated: Payment ID=$payment_id, Student=$student_id, Amount=$amount, CheckoutRequestID=$CheckoutRequestID");
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment request sent successfully. Please check your phone to complete the payment.',
        'payment_id' => $payment_id,
        'receipt_number' => $receipt_number,
        'CheckoutRequestID' => $CheckoutRequestID,
        'MerchantRequestID' => $MerchantRequestID,
        'student_name' => $student['first_name'] . ' ' . $student['last_name']
    ]);
    
} catch (PDOException $e) {
    error_log("M-Pesa payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
} catch (Exception $e) {
    error_log("M-Pesa payment API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
