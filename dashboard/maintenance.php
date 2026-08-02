<?php
// Maintenance Page
require_once '../config.php';
require_once '../includes/helpers.php';

// Check if actually in maintenance mode
if (!isMaintenanceMode($conn)) {
    header('Location: dashboard');
    exit;
}

// Allow admin users to bypass maintenance mode
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && $user['role'] === 'admin') {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Kenya EduHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Google+Material+Icons">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #202124;
        }
        
        .maintenance-container {
            padding: 48px 40px;
            max-width: 480px;
            text-align: center;
        }
        
        .maintenance-icon {
            font-size: 64px;
            color: #5f6368;
            margin-bottom: 24px;
        }
        
        .maintenance-title {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .maintenance-message {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
            line-height: 1.5;
        }
        
        .maintenance-info {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: left;
        }
        
        .maintenance-info h4 {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .maintenance-info ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .maintenance-info li {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }
        
        .maintenance-info li:before {
            content: 'check_circle';
            font-family: 'Google Material Icons';
            position: absolute;
            left: 0;
            color: #1a73e8;
            font-size: 18px;
        }
        
        .maintenance-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 32px;
        }
        
        .maintenance-logo-circle {
            width: 40px;
            height: 40px;
            background: #FFD700;
            border: 2px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 20px;
        }
        
        .maintenance-logo-text {
            font-size: 20px;
            font-weight: 500;
        }
        
        .maintenance-logo-text span:first-child {
            color: #FF6B35;
        }
        
        .maintenance-logo-text span:last-child {
            color: #008000;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
        }
        
        .btn-primary {
            background: #1a73e8;
            color: white;
        }
        
        .btn-primary:hover {
            background: #1557b0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
        }
        
        .btn-secondary {
            background: #f1f3f4;
            color: #202124;
        }
        
        .btn-secondary:hover {
            background: #e8eaed;
        }
        
        @media (max-width: 600px) {
            .maintenance-container {
                padding: 32px 24px;
                margin: 16px;
            }
            
            .maintenance-title {
                font-size: 20px;
            }
            
            .maintenance-message {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <div class="maintenance-logo">
            <div class="maintenance-logo-circle">
                <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
            </div>
            <div class="maintenance-logo-text">
                <span>Kenya</span> <span>EduHub</span>
            </div>
        </div>
        
        <div class="maintenance-icon">
            <span class="material-icons">build</span>
        </div>
        
        <h1 class="maintenance-title">Under Maintenance</h1>
        
        <p class="maintenance-message">
            We're currently performing scheduled maintenance to improve your experience. 
            We'll be back shortly!
        </p>
        
        <div class="maintenance-info">
            <h4>What to expect:</h4>
            <ul>
                <li>Enhanced performance and speed</li>
                <li>New features and improvements</li>
                <li>Security updates</li>
                <li>Bug fixes and optimizations</li>
            </ul>
        </div>
        
        <p style="color: #5f6368; font-size: 14px; margin-bottom: 24px;">
            We apologize for any inconvenience. Thank you for your patience!
        </p>
    </div>
</body>
</html>