<?php
// Test script to check what session variables are expected
session_start();

header('Content-Type: application/json');

echo json_encode([
    'expected_session_vars' => [
        'user_id' => 'Integer user ID',
        'name' => 'User full name',
        'email' => 'User email address',
        'role' => 'Should be "admin" for admin users'
    ],
    'current_session' => $_SESSION,
    'is_admin' => (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'),
    'has_user_id' => isset($_SESSION['user_id'])
]);
?>