<?php
/**
 * M-Pesa Configuration File
 * Centralized M-Pesa settings for the Kenya EduHub system
 * 
 * This file now reads M-Pesa credentials from the database for better security
 * Credentials are stored in admin_mpesa_settings table and loaded dynamically
 * 
 * This file contains all M-Pesa configuration for:
 * - Parents (STK Push payments)
 * - Finance Managers (B2C withdrawals)
 * - Schools (B2C withdrawals)
 */

require_once __DIR__ . '/../config.php';

// Function to get M-Pesa settings from database
function getMpesaSettings() {
    global $conn;
    
    try {
        // Get settings from database
        $stmt = $conn->prepare("SELECT * FROM admin_mpesa_settings LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            // Insert default sandbox settings if table is empty
            $insertStmt = $conn->prepare("INSERT INTO admin_mpesa_settings 
                (environment, callback_base_url, consumer_key, consumer_secret, passkey, 
                 business_shortcode, stk_push_url, auth_url, b2c_url, b2c_shortcode, 
                 b2c_initiator_name, b2c_initiator_password, b2c_command_id, transaction_type, timeout) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $insertStmt->bind_param("ssssssssssssssi", 
                'sandbox',
                'https://2f34-154-159-237-4.ngrok-free.app',
                'opWeqfbSeWQg0lxBf1OWiUuFGzvOAxWm1mJQG2G46kpPVOMJ',
                '6hU1YYlvAX6I9K5z9e9AS3RVH5D993lM5PL78pjcBevO9KeTlIUwFUchwKDR25Yi',
                'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919',
                '174379',
                'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
                'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
                '600997',
                'testapi',
                'Safaricom123!!',
                'SalaryPayment',
                'CustomerPayBillOnline',
                30
            );
            $insertStmt->execute();
            
            // Return the inserted settings
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        }
    } catch (Exception $e) {
        error_log("Failed to get M-Pesa settings from database: " . $e->getMessage());
        return null;
    }
}

// Get settings from database
$mpesa_settings = getMpesaSettings();

// If database read fails, throw error (no hardcoded fallback)
if (!$mpesa_settings) {
    error_log("FATAL: Failed to load M-Pesa settings from database. Please configure M-Pesa settings in admin panel.");
    die("M-Pesa settings not configured. Please contact administrator.");
}

// Use database values
define('MPESA_ENVIRONMENT', $mpesa_settings['environment']);
define('MPESA_CALLBACK_BASE_URL', $mpesa_settings['callback_base_url']);
define('MPESA_CONSUMER_KEY', $mpesa_settings['consumer_key']);
define('MPESA_CONSUMER_SECRET', $mpesa_settings['consumer_secret']);
define('MPESA_PASSKEY', $mpesa_settings['passkey']);
define('MPESA_BUSINESS_SHORTCODE', $mpesa_settings['business_shortcode']);
define('MPESA_STK_PUSH_URL', $mpesa_settings['stk_push_url']);
define('MPESA_AUTH_URL', $mpesa_settings['auth_url']);
define('MPESA_B2C_URL', $mpesa_settings['b2c_url']);
define('MPESA_B2C_SHORTCODE', $mpesa_settings['b2c_shortcode']);
define('MPESA_B2C_INITIATOR_NAME', $mpesa_settings['b2c_initiator_name']);
define('MPESA_B2C_INITIATOR_PASSWORD', $mpesa_settings['b2c_initiator_password']);
define('MPESA_B2C_COMMAND_ID', $mpesa_settings['b2c_command_id']);
define('MPESA_TRANSACTION_TYPE', $mpesa_settings['transaction_type']);
define('MPESA_TIMEOUT', $mpesa_settings['timeout']);

// Callback URL paths (relative to base URL)
// These paths work on both localhost (with ngrok) and live hosting

// Parents STK Push callback
define('MPESA_PARENTS_CALLBACK_PATH', '/kenyaeduhub/parents/api/mpesa_callback.php');

// Librarians fine payment callback
define('MPESA_LIBRARIANS_CALLBACK_PATH', '/kenyaeduhub/librarians/api/mpesa_fine_callback.php');

// Schools B2C callbacks
define('MPESA_SCHOOLS_B2C_TIMEOUT_PATH', '/kenyaeduhub/schools/payments/b2c/b2c_timeout.php');
define('MPESA_SCHOOLS_B2C_RESULT_PATH', '/kenyaeduhub/schools/payments/b2c/b2c_result.php');

// Finance Managers B2C callbacks
define('MPESA_FINANCE_B2C_TIMEOUT_PATH', '/kenyaeduhub/finance-managers/b2c/b2c_timeout.php');
define('MPESA_FINANCE_B2C_RESULT_PATH', '/kenyaeduhub/finance-managers/b2c/b2c_result.php');
?>
