<?php
session_start();
require 'auth.php';
require 'chat_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: iindex.php');
    exit;
}

$current_user_id = $_SESSION['user_id'];
$users = getUsers();
$conversations = getUserConversations($current_user_id);

// Handle starting a new conversation
if (isset($_GET['start_chat']) && $_GET['start_chat'] != $current_user_id) {
    $other_user_id = $_GET['start_chat'];
    $conversation_id = getConversation($current_user_id, $other_user_id);
    header("Location: chat.php?conversation=$conversation_id");
    exit;
}

// Get current conversation
$current_conversation_id = isset($_GET['conversation']) ? $_GET['conversation'] : null;
$messages = [];
$other_user = null;

if ($current_conversation_id) {
    $messages = getMessages($current_conversation_id);
    
    foreach ($conversations as $conv) {
        if ($conv['id'] == $current_conversation_id) {
            $other_user = ['id' => $conv['other_user_id'], 'username' => $conv['other_username']];
            break;
        }
    }
}

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $current_conversation_id) {
    sendMessage($current_conversation_id, $current_user_id, $_POST['message']);
    header("Location: chat.php?conversation=$current_conversation_id");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat System</title>
    <link rel="stylesheet" href="chat_style.css"> <!-- External CSS -->
</head>
<body>

<div class="app-container">

    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
            <a href="user_page.php#resourceList" class="logout-btn">Logout</a>
        </div>

        <div class="section">
            <h3>Conversations</h3>
            <?php foreach ($conversations as $conv): ?>
                <div class="conversation <?php echo $conv['id'] == $current_conversation_id ? 'active' : ''; ?>"
                     onclick="location.href='?conversation=<?php echo $conv['id']; ?>'">
                    <?php echo $conv['other_username']; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section">
            <h3>Start New Chat</h3>
            <?php foreach ($users as $user): ?>
                <?php if ($user['id'] != $current_user_id): ?>
                    <div class="conversation new-chat" onclick="location.href='?start_chat=<?php echo $user['id']; ?>'">
                        <?php echo $user['username']; ?> (<?php echo $user['role']; ?>)
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="chat-area">
        <?php if ($current_conversation_id): ?>
            <div class="chat-header">
                <h2>Chat with <?php echo $other_user['username']; ?></h2>
            </div>

            <div class="messages" id="messages">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?php echo $msg['sender_id'] == $current_user_id ? 'sent' : 'received'; ?>">
                        <div class="message-content">
                            <?php echo htmlspecialchars($msg['message']); ?>
                        </div>
                        <small><?php echo $msg['username']; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <form class="message-input" method="post">
                <input type="text" name="message" placeholder="Type your message..." required>
                <button type="submit">Send</button>
            </form>

            <script>
                const messagesDiv = document.getElementById('messages');
                messagesDiv.scrollTop = messagesDiv.scrollHeight;

                setInterval(function() {
                    const lastMessageId = <?php echo empty($messages) ? 0 : end($messages)['id']; ?>;
                    fetch(`get_messages.php?conversation=<?php echo $current_conversation_id; ?>&last_id=${lastMessageId}`)
                        .then(response => response.json())
                        .then(messages => {
                            if (messages.length > 0) {
                                messages.forEach(msg => {
                                    const messageDiv = document.createElement('div');
                                    messageDiv.className = `message ${msg.sender_id == <?php echo $current_user_id; ?> ? 'sent' : 'received'}`;
                                    messageDiv.innerHTML = `<div class="message-content">${msg.message}</div><small>${msg.username}</small>`;
                                    messagesDiv.appendChild(messageDiv);
                                });
                                messagesDiv.scrollTop = messagesDiv.scrollHeight;
                            }
                        });
                }, 3000);
            </script>

        <?php else: ?>
            <div class="chat-header">
                <h2>Select a conversation or start a new chat</h2>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
