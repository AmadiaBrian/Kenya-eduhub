<?php
// Check Fine Payment Status API for Parents
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

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $checkoutRequestID = $_POST['checkoutRequestID'] ?? null;
    
    if (!$checkoutRequestID) {
        echo json_encode(['success' => false, 'error' => 'CheckoutRequestID is required']);
        exit;
    }
    
    // Check transactions table for payment status
    $stmt = $pdo->prepare("SELECT * FROM transactions WHERE CheckoutRequestID = ? ORDER BY ID DESC LIMIT 1");
    $stmt->execute([$checkoutRequestID]);
    $transaction = $stmt->fetch();

    if (!$transaction) {
        // No transaction record yet
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
            case '2001':
                $errorMessage = 'Wrong M-Pesa PIN entered';
                break;
            default:
                $errorMessage = $transaction['ResultDesc'] ?: 'Payment failed';
                break;
        }
    }

    echo json_encode([
        'success' => true,
        'found' => true,
        'status' => $responseStatus,
        'error_message' => $errorMessage,
        'mpesa_receipt' => $mpesaReceipt,
        'amount' => $amount,
        'result_code' => $resultCode
    ]);
    
} catch (PDOException $e) {
    error_log("Check fine payment status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
} catch (Exception $e) {
    error_log("Check fine payment status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
