<?php
require 'config.php';

function getConversation($user1_id, $user2_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM conversations 
                          WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
    $conversation = $stmt->fetch();
    
    if (!$conversation) {
        $stmt = $pdo->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)");
        $stmt->execute([$user1_id, $user2_id]);
        return $pdo->lastInsertId();
    }
    
    return $conversation['id'];
}

function sendMessage($conversation_id, $sender_id, $message) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, sender_id, message) VALUES (?, ?, ?)");
    return $stmt->execute([$conversation_id, $sender_id, $message]);
}

function getMessages($conversation_id, $last_message_id = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT m.*, u.username FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE m.conversation_id = ? AND m.id > ? 
                          ORDER BY m.created_at ASC");
    $stmt->execute([$conversation_id, $last_message_id]);
    return $stmt->fetchAll();
}

function getUserConversations($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT c.id, 
                          CASE 
                              WHEN c.user1_id = ? THEN u2.id
                              ELSE u1.id
                          END as other_user_id,
                          CASE 
                              WHEN c.user1_id = ? THEN u2.username
                              ELSE u1.username
                          END as other_username
                          FROM conversations c
                          JOIN users u1 ON c.user1_id = u1.id
                          JOIN users u2 ON c.user2_id = u2.id
                          WHERE c.user1_id = ? OR c.user2_id = ?");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    return $stmt->fetchAll();
}
?>
