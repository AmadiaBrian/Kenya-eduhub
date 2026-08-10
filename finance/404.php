<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>404 - Page Not Found</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .error-description {
            font-size: 16px;
            color: #9aa0a6;
            margin-bottom: 40px;
        }
        
        .btn-home {
            background: #FF6B35;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .btn-home:hover {
            background: #e55a2b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">404</div>
        <div class="error-message">Page Not Found</div>
        <div class="error-description">The page you are looking for does not exist or has been moved.</div>
        <a href="dashboard" class="btn-home">
            <i class="fas fa-home me-2"></i> Go to Dashboard
        </a>
    </div>
</body>
</html>
