<!DOCTYPE html>
<html>
<head>
    <title>Connection Error</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: var(--card-bg, #f8f9fa);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
            margin: 20px auto;
        }
        .error-icon {
            font-size: 64px;
            color: #FF6B35;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }
        .error-message {
            font-size: 16px;
            color: #5f6368;
            margin-bottom: 24px;
            text-align: center;
        }
        .retry-btn {
            background: #FF6B35;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .retry-btn:hover {
            background: #e55a2b;
        }
    </style>
</head>
<body>
    <div class='error-container'>
        <div class='error-icon'>⚠️</div>
        <h1 class='error-title'>Connection Error</h1>
        <p class='error-message'>We're unable to connect to our servers. Please check your internet connection and try again.</p>
        <button class='retry-btn' onclick='location.reload()'>Try Again</button>
    </div>
    <?php require_once __DIR__ . '/includes/copywrite.php'; ?>
</body>
</html>