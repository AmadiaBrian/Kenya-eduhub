<?php
/**
 * M-Pesa Configuration File
 * Centralized M-Pesa settings for the Kenya EduHub system
 * 
 * This file contains all M-Pesa configuration for:
 * - Parents (STK Push payments)
 * - Finance Managers (B2C withdrawals)
 * - Schools (B2C withdrawals)
 */

// Environment: 'sandbox' for testing, 'production' for live
define('MPESA_ENVIRONMENT', 'sandbox');

// Base URL for M-Pesa callbacks
// For local testing, use a tunneling service like ngrok
// For production, use your actual domain with HTTPS
define('MPESA_CALLBACK_BASE_URL', 'https://30d0-2c0f-fe38-21a8-1be2-b1aa-3f3d-6971-164f.ngrok-free.app');

// M-Pesa API Credentials
// Sandbox credentials (for testing)
if (MPESA_ENVIRONMENT === 'sandbox') {
    // STK Push (Parents, Schools)
    define('MPESA_CONSUMER_KEY', 'opWeqfbSeWQg0lxBf1OWiUuFGzvOAxWm1mJQG2G46kpPVOMJ');
    define('MPESA_CONSUMER_SECRET', '6hU1YYlvAX6I9K5z9e9AS3RVH5D993lM5PL78pjcBevO9KeTlIUwFUchwKDR25Yi');
    define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
    define('MPESA_BUSINESS_SHORTCODE', '174379');
    define('MPESA_STK_PUSH_URL', 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    define('MPESA_AUTH_URL', 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    
    // B2C (Finance Managers, Schools)
    define('MPESA_B2C_URL', 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest');
    define('MPESA_B2C_SHORTCODE', '600997');
    define('MPESA_B2C_INITIATOR_NAME', 'testapi');
    define('MPESA_B2C_INITIATOR_PASSWORD', 'Safaricom123!!');
    define('MPESA_B2C_COMMAND_ID', 'SalaryPayment');
} else {
    // Production credentials (for live payments)
    // STK Push (Parents, Schools)
    define('MPESA_CONSUMER_KEY', 'YOUR_PRODUCTION_CONSUMER_KEY');
    define('MPESA_CONSUMER_SECRET', 'YOUR_PRODUCTION_CONSUMER_SECRET');
    define('MPESA_PASSKEY', 'YOUR_PRODUCTION_PASSKEY');
    define('MPESA_BUSINESS_SHORTCODE', 'YOUR_PRODUCTION_SHORTCODE');
    define('MPESA_STK_PUSH_URL', 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    define('MPESA_AUTH_URL', 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    
    // B2C (Finance Managers, Schools)
    define('MPESA_B2C_URL', 'https://api.safaricom.co.ke/mpesa/b2c/v1/paymentrequest');
    define('MPESA_B2C_SHORTCODE', 'YOUR_PRODUCTION_B2C_SHORTCODE');
    define('MPESA_B2C_INITIATOR_NAME', 'YOUR_PRODUCTION_INITIATOR_NAME');
    define('MPESA_B2C_INITIATOR_PASSWORD', 'YOUR_PRODUCTION_INITIATOR_PASSWORD');
    define('MPESA_B2C_COMMAND_ID', 'SalaryPayment');
}

// Transaction Type
define('MPESA_TRANSACTION_TYPE', 'CustomerPayBillOnline');

// Timeout settings
define('MPESA_TIMEOUT', 30); // seconds

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
