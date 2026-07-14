<?php
//YOU MPESA API KEYS
$consumerKey = "opWeqfbSeWQg0lxBf1OWiUuFGzvOAxWm1mJQG2G46kpPVOMJ"; //Fill with your app Consumer Key
$consumerSecret = "6hU1YYlvAX6I9K5z9e9AS3RVH5D993lM5PL78pjcBevO9KeTlIUwFUchwKDR25Yi"; //Fill with your app Consumer Secret
//ACCESS TOKEN URL (SANDBOX)
$access_token_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
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