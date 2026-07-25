<?php
// M-Pesa Access Token Generation
// Uses centralized configuration from config/mpesa_config.php
require_once __DIR__ . '/../../config/mpesa_config.php';

$consumerKey = MPESA_CONSUMER_KEY;
$consumerSecret = MPESA_CONSUMER_SECRET;
$access_token_url = MPESA_AUTH_URL;

$headers = ['Content-Type:application/json; charset=utf8'];
$curl = curl_init($access_token_url);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curl, CURLOPT_HEADER, FALSE);
curl_setopt($curl, CURLOPT_USERPWD, $consumerKey . ':' . $consumerSecret);
curl_setopt($curl, CURLOPT_TIMEOUT, MPESA_TIMEOUT);
$result = curl_exec($curl);
$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$result = json_decode($result);
$access_token = $result->access_token ?? null;
curl_close($curl);