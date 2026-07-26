<?php
// SMS Configuration for Kenya EduHub
// Supports Mobitech and Text SMS API providers

// Mobitech SMS Configuration
define('MOBITECH_API_KEY', 'your_mobitech_api_key_here');
define('MOBITECH_API_URL', 'https://app.mobitechtechnologies.com/sms/sendsms');
define('MOBITECH_BULK_API_URL', 'https://app.mobitechtechnologies.com/sms/sendbulksms');
define('MOBITECH_BALANCE_URL', 'https://app.mobitechtechnologies.com/sms/units');
define('MOBITECH_SENDER_ID', 'KenyaEduHub');

// Text SMS Configuration
define('TEXTSMS_API_KEY', 'your_textsms_api_key_here');
define('TEXTSMS_API_URL', 'https://textsms.co.ke/api/sms/send');
define('TEXTSMS_SENDER_ID', 'KenyaEduHub');

// Default SMS Provider (options: 'mobitech', 'textsms')
define('DEFAULT_SMS_PROVIDER', 'mobitech');

// SMS Settings
define('SMS_ENABLED', true);
define('SMS_LOGGING', true);

// Message Templates
define('SMS_TEMPLATES', [
    'results_published' => 'Dear Parent, results for {term} {year} have been published. Login to your parent portal to view {child_name}\'s performance. - KenyaEduHub',
    'fee_reminder' => 'Dear Parent, this is a reminder that fee payment for {term} is due. Amount: KES {amount}. Please pay by {due_date}. - KenyaEduHub',
    'attendance_alert' => 'Dear Parent, {child_name} was absent on {date}. Please contact the school for more information. - KenyaEduHub',
    'assignment_due' => 'Dear Parent, {child_name} has an assignment due on {due_date}. Subject: {subject}. - KenyaEduHub',
    'general' => '{message} - KenyaEduHub'
]);
?>
