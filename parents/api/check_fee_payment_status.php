<?php
// Check Fee Payment Status via M-Pesa
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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $checkoutId = trim($_POST['checkoutRequestID'] ?? $_POST['CheckoutRequestID'] ?? '');
    
    if ($checkoutId === '') {
        throw new Exception('Missing CheckoutRequestID');
    }

    // Check transactions table in users_db database
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE CheckoutRequestID = ? LIMIT 1");
    $stmt->execute([$checkoutId]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        echo json_encode([
            'success' => true,
            'found' => false,
            'status' => 'not_found',
            'message' => 'Transaction not found yet. Please wait...'
        ]);
        exit;
    }

    // Determine status from transactions table
    $resultCode = $transaction['ResultCode'] ?? '';
    $mpesaReceipt = $transaction['MpesaReceiptNumber'] ?? '';
    $amount = $transaction['Amount'] ?? 0;

    // Map ResultCode to response format
    $responseStatus = 'pending';
    $errorMessage = '';

    if ($resultCode === '0' || $resultCode === 0) {
        $responseStatus = 'success';
    } elseif (!empty($resultCode)) {
        $responseStatus = 'failed';
        // Get specific error message based on ResultCode
        switch ($resultCode) {
            case '1032':
                $errorMessage = 'Transaction cancelled by user';
                break;
            case '1037':
                $errorMessage = 'Transaction timed out';
                break;
            case '1':
                $errorMessage = 'Insufficient funds';
                break;
            case '17':
                $errorMessage = 'Invalid phone number';
                break;
            default:
                $errorMessage = 'Payment failed';
                break;
        }
    }

    $response = [
        'success' => true,
        'found' => true,
        'status' => $responseStatus,
        'error_message' => $errorMessage,
        'mpesa_receipt' => $mpesaReceipt,
        'amount' => $amount,
        'result_code' => $resultCode
    ];

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Check fee payment status error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
