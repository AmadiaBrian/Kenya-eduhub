<?php
$host = "sql202.infinityfree.com";
$user = "if0_38917377";
$password = "vOBHdxuDt4nB";
$database = "if0_38917377_test";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("connection failed: ". $conn->connect_error);
}
   ?>