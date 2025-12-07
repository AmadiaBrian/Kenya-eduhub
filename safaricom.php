<?php


$consumerKey = 'YOUR_APP_CONSUMER_KEY';
$consumerSecret = 'YOUR_APP_CONSUMER_SECRET';

// Encode credentials to base64
$credentials = base64_encode("$consumerKey:$consumerSecret");

$ch = curl_init('https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $credentials
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo 'cURL error: ' . curl_error($ch);
} else {
    $result = json_decode($response);
    print_r($result); // Or echo $result->access_token;
}

curl_close($ch);
?>
