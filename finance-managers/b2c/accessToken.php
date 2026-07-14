<?php
// Load M-Pesa configuration
$mpesa_config = require __DIR__ . '/../config/mpesa_config.php';

$consumerKey = $mpesa_config['consumer_key'];
$consumerSecret = $mpesa_config['consumer_secret'];
$access_token_url = $mpesa_config['access_token_url'];

$headers = ['Content-Type:application/json; charset=utf8'];
$curl = curl_init($access_token_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
$result = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$result = json_decode($result);
//ASSIGN ACCESS TOKEN TO A VARIABLE
$access_token = $result->access_token;
curl_close($curl);