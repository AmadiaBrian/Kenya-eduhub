<?php
require 'cconfig.php';

function registerUser($username, $email, $password, $role = 'user') {
    global $pdo;

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        // Email already registered
        return false;
    }

    // Continue registration
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $email, $hashedPassword, $role]);
}

function loginUser($email, $password) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        return true;
    }
    return false;
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getUsers() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, username, role FROM users");
    return $stmt->fetchAll();
}
?>
