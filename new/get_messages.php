<?php
session_start();
require 'auth.php';
require 'chat_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

if (!isset($_GET['conversation'])) {
    header('HTTP/1.1 400 Bad Request');
    exit;
}

$conversation_id = $_GET['conversation'];
$last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
$messages = getMessages($conversation_id, $last_message_id);

header('Content-Type: application/json');
echo json_encode($messages);
?>
