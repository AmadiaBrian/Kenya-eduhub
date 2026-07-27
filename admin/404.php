<?php
// 404 Error Page for Admin Panel
// Session is started by index.php router
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - Kenya EduHub Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }
        
        .error-container {
            text-align: center;
            padding: 40px;
        }
        
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: #FF6B35;
            margin-bottom: 20px;
        }
        
        .error-message {
            font-size: 24px;
            color: #5f6368;
            margin-bottom: 30px;
        }
        
        .btn-home {
            background: #FF6B35;
            color: white;
            border: 2px solid #FF6B35;
            border-radius: 25px;
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-home:hover {
            background: #FFD700;
            border-color: #FFD700;
            color: #202124;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message">Page Not Found</div>
        <p style="color: #5f6368; margin-bottom: 30px;">The page you're looking for doesn't exist or has been moved.</p>
        <a href="dashboard" class="btn-home">
            <i class="fas fa-home"></i> Go to Dashboard
        </a>
    </div>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
