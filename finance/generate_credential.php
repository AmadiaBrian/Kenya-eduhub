<?php
/**
 * Generate M-Pesa B2C Security Credential
 * 
 * This script generates the security credential for B2C API calls.
 * It encrypts the initiator password using the Safaricom public certificate.
 */

// Sandbox initiator password
$initiatorPassword = 'TEST1234';

// Load the sandbox certificate
$certificatePath = __DIR__ . '/SandboxCertificate.cer';

// Download the certificate if not present
if (!file_exists($certificatePath)) {
    echo "Downloading SandboxCertificate.cer...\n";
    $certUrl = 'https://developer.safaricom.co.ke/certificates/SandboxCertificate.cer';
    $certContent = file_get_contents($certUrl);
    if ($certContent === false) {
        die("Failed to download certificate. Please download manually from: https://developer.safaricom.co.ke/certificates/SandboxCertificate.cer\n");
    }
    file_put_contents($certificatePath, $certContent);
    echo "Certificate downloaded successfully.\n";
}

// Load the certificate
$publicKey = file_get_contents($certificatePath);
if ($publicKey === false) {
    die("Failed to load certificate from: $certificatePath\n");
}

// Encrypt the password
$encrypted = '';
if (!openssl_public_encrypt($initiatorPassword, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
    die("Failed to encrypt password. Error: " . openssl_error_string() . "\n");
}

// Base64 encode the encrypted password
$securityCredential = base64_encode($encrypted);

// Output the result
echo "=== Security Credential Generated ===\n";
echo "Initiator Password: $initiatorPassword\n";
echo "Security Credential:\n";
echo $securityCredential . "\n";
echo "\n";
echo "Copy the Security Credential above and paste it into your config file:\n";
echo "finance-managers/config/mpesa_config.php\n";
echo "Replace the 'security_credential' value with this new credential.\n";
?>
