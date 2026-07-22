<?php
/**
 * B2C M-Pesa Payment Processor
 * Business to Customer - Send money from business account to customer
 */

header('Content-Type: application/json');

try {
    // Generate fresh access token for B2C
    $consumerKey = "H02tEBy7MOC6mNopfYI5Yf1dKJJyjQbA";
    $consumerSecret = "D9By1aKAGiURG4hT";
    $access_token_url = 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
    
    $curl = curl_init($access_token_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type:application/json; charset=utf8']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($curl, CURLOPT_HEADER, FALSE);
    curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
    $result = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    $result = json_decode($result);
    
    // Debug token generation
    if ($status !== 200) {
        throw new Exception("Token request failed with HTTP $status: " . json_encode($result));
    }
    
    if (!isset($result->access_token)) {
        throw new Exception('Failed to get access token: ' . json_encode($result));
    }
    
    $access_token = $result->access_token;
    
    // Log token for debugging (remove in production)
    error_log("B2C Access Token Generated: " . substr($access_token, 0, 20) . "...");
    
    // Get POST data
    $amount = intval(round(floatval($_POST['amount'] ?? 0)));
    $phone = $_POST['phone'] ?? '';
    $remarks = $_POST['remarks'] ?? 'B2C Payment from Teksolution';
    
    // Validate inputs
    if ($amount < 10) {
        throw new Exception('Minimum amount is Ksh 10');
    }
    
    if (empty($phone)) {
        throw new Exception('Phone number is required');
    }
    
    // Clean and format phone number for M-Pesa
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to M-Pesa format (254XXXXXXXXX)
    if (strlen($phone) === 9) {
        $phone = '254' . $phone;
    } elseif (strlen($phone) === 10 && $phone[0] === '0') {
        $phone = '254' . substr($phone, 1);
    } elseif (strlen($phone) === 12 && strpos($phone, '254') === 0) {
        // Already in 254XXXXXXXXX format
    } else {
        throw new Exception('Invalid phone number format. Use 07XXXXXXXX or 254XXXXXXXXX');
    }
    
    // B2C API Configuration
    date_default_timezone_set('Africa/Nairobi');
    $b2c_url = 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest';
    
    // B2C Credentials - YOU NEED TO GET THESE FROM SAFARICOM PORTAL
    $BusinessShortCode = '7373854'; // Your business shortcode
    $InitiatorName = 'YOUR_INITIATOR_NAME'; // Get from Safaricom portal
    $SecurityCredential = 'YOUR_SECURITY_CREDENTIAL'; // Get from Safaricom portal
    $CommandID = 'BusinessPayment'; // B2C transaction type
    $PartyA = $BusinessShortCode; // Business paying
    $PartyB = $phone; // Customer receiving
    $Remarks = $remarks;
    $QueueTimeOutURL = 'https://' . $_SERVER['HTTP_HOST'] . '/account/b2c/b2c_timeout.php';
    $ResultURL = 'https://' . $_SERVER['HTTP_HOST'] . '/account/b2c/b2c_result.php';
    $Occasion = 'Payment';
    
    // Generate timestamp and password
    $Timestamp = date('YmdHis');
    $Password = base64_encode($BusinessShortCode . $SecurityCredential . $Timestamp);
    
    // Prepare B2C request
    $b2c_headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $access_token
    ];
    
    $b2c_data = [
        'InitiatorName' => $InitiatorName,
        'SecurityCredential' => $SecurityCredential,
        'CommandID' => $CommandID,
        'Amount' => $amount,
        'PartyA' => $PartyA,
        'PartyB' => $PartyB,
        'Remarks' => $Remarks,
        'QueueTimeOutURL' => $QueueTimeOutURL,
        'ResultURL' => $ResultURL,
        'Occasion' => $Occasion
    ];
    
    // Initialize cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $b2c_url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $b2c_headers);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($b2c_data));
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    
    $curl_response = curl_exec($curl);
    $error = curl_error($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    $data = json_decode($curl_response, true);
    
    // Debug B2C API response
    error_log("B2C API Response - HTTP: $httpCode, Response: " . $curl_response);
    
    if ($httpCode !== 200) {
        $errorMsg = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'Failed to initiate payment';
        throw new Exception("B2C API Error ($httpCode): $errorMsg");
    }
    
    if (!isset($data['ResponseCode']) || $data['ResponseCode'] !== '0') {
        $errorMsg = $data['errorMessage'] ?? $data['ResponseDescription'] ?? 'B2C payment failed';
        throw new Exception($errorMsg);
    }
    
    // Store transaction in database
    require_once '../config/connection.php';
    
    $stmt = $db->prepare("INSERT INTO transactions (Amount, PhoneNumber, ResultCode, CheckoutRequestID, created_at) VALUES (?, ?, ?, ?, NOW())");
    $resultCode = 'pending';
    $checkoutId = $data['ConversationID'] ?? uniqid('B2C_');
    
    $stmt->bind_param("isss", $amount, $phone, $resultCode, $checkoutId);
    $stmt->execute();
    $transactionId = $db->insert_id;
    $stmt->close();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'B2C payment initiated successfully',
        'ConversationID' => $data['ConversationID'] ?? '',
        'OriginatorConversationID' => $data['OriginatorConversationID'] ?? '',
        'ResponseDescription' => $data['ResponseDescription'] ?? '',
        'transaction_id' => $transactionId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug_info' => [
            'amount' => $amount ?? 'not_set',
            'phone' => $phone ?? 'not_set',
            'remarks' => $remarks ?? 'not_set',
            'curl_error' => $error ?? 'none',
            'http_code' => $httpCode ?? 'none',
            'response' => $curl_response ?? 'none',
            'access_token_status' => $status ?? 'none',
            'access_token_length' => strlen($access_token ?? '') ?? 'none'
        ]
    ]);
}
?>
