<?php
// Parent Logout API
session_start();
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Destroy session
session_unset();
session_destroy();

// Redirect to login page
echo json_encode(['success' => true, 'redirect' => 'index.php']);
