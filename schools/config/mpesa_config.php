<?php
/**
 * M-Pesa Configuration File for Schools
 * 
 * IMPORTANT: Fill in your actual M-Pesa credentials here.
 * This file should be kept secure and not committed to public repositories.
 */

return [
    // M-Pesa API Credentials (from Safaricom Developer Portal)
    'consumer_key' => 'opWeqfbSeWQg0lxBf1OWiUuFGzvOAxWm1mJQG2G46kpPVOMJ', // Replace with your Consumer Key
    'consumer_secret' => '6hU1YYlvAX6I9K5z9e9AS3RVH5D993lM5PL78pjcBevO9KeTlIUwFUchwKDR25Yi', // Replace with your Consumer Secret
    
    // API Endpoints
    'access_token_url' => 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
    'b2c_url' => 'https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest',
    
    // B2C Configuration (Schools withdrawing money)
    'b2c' => [
        'shortcode' => '600997', // Replace with your B2C Shortcode
        'initiator_name' => 'testapi', // Replace with your Initiator Name (from Safaricom Developer Portal)
        'initiator_password' => 'Safaricom123!!', // Replace with your Initiator Password (from Safaricom Developer Portal)
        'command_id' => 'SalaryPayment', // SalaryPayment, BusinessPayment, PromotionPayment
    ],
    
    // Callback URLs (configure your public URL)
    'callback_base_url' => 'https://9c71-41-90-68-138.ngrok-free.app', // Replace with your public URL
    'b2c_timeout_url' => '/Kenyaeduhub/schools/payments/b2c/b2c_timeout.php',
    'b2c_result_url' => '/Kenyaeduhub/schools/payments/b2c/b2c_result.php',
    
    // Callback authentication (optional but recommended)
    'callback_api_key' => 'your_secure_api_key_here', // Replace with a secure random key
];
