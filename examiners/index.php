<?php
// Examiners Main Router - Handles all examiner routes
session_start();
require_once __DIR__ . '/../config.php';

$route = $_GET['route'] ?? 'login';

// Whitelist of allowed routes
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'exam-types',
    'subjects',
    'performance',
    'grading',
    'results',
    'students',
    'attendance',
    'classes',
    'calendar',
    'timetable',
    'streams'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}

// Handle login route separately (no auth required)
if ($route === 'login') {
    // If already logged in with valid examiner session, redirect to dashboard
    if (isset($_SESSION['examiner_id']) && isset($_SESSION['examiner_token'])) {
        try {
            $session_token = $_SESSION['examiner_token'];
            $stmt = $pdo->prepare("SELECT * FROM examiner_sessions WHERE session_token = ? AND expires_at > NOW()");
            $stmt->execute([$session_token]);
            $session = $stmt->fetch();
            
            if ($session) {
                header('Location: dashboard');
                exit;
            }
        } catch (PDOException $e) {
            // Invalid session, continue to login page
        }
    }
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Examiners Portal - Kenya EduHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }
        
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: 40px;
        }
        
        .logo-circle {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .logo-circle span {
            font-weight: bold;
            font-size: 24px;
        }
        
        .logo-text {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 24px;
        }
        
        h1 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        p {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 13px 15px;
            font-size: 16px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-control:focus {
            border-color: #FF6B35;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .btn-primary {
            width: 100%;
            padding: 12px 24px;
            background: #FF6B35;
            color: white;
            border: 2px solid #FF6B35;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            margin-top: 8px;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
            border-color: #e55a2b;
            color: white;
        }
        
        .error-message {
            display: none;
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcc;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1a73e8;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-text">
            <div class="logo-circle">
                <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
            </div>
            <span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span>
        </div>
        
        <h1>Examiners Portal</h1>
        <p>Login to access your examiner dashboard</p>

        <div id="loginError" class="error-message"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="loginEmail">Email</label>
                <input type="email" class="form-control" id="loginEmail" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" class="form-control" id="loginPassword" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary">Login</button>
        </form>
        
        <a href="../schools/index.php?route=login" class="back-link">Back to Schools Login</a>
    </div>
    
    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const errorDiv = document.getElementById('loginError');

            console.log('Login attempt started');
            console.log('Email:', email);
            console.log('Password length:', password.length);

            errorDiv.style.display = 'none';

            try {
                console.log('Sending request to api/login.php');
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                if (data.success) {
                    console.log('Login successful, redirecting to dashboard');
                    window.location.href = 'dashboard';
                } else {
                    console.log('Login failed:', data.error);
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                console.error('Fetch error:', error);
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
    </script>
</body>
</html>
<?php
    exit;
}

// Handle logout route
if ($route === 'logout') {
    // Delete session from database
    if (isset($_SESSION['examiner_id'])) {
        try {
            $session_token = $_SESSION['examiner_session_token'] ?? '';
            if ($session_token) {
                $stmt = $pdo->prepare("DELETE FROM examiner_sessions WHERE session_token = ?");
                $stmt->execute([$session_token]);
            }
        } catch (PDOException $e) {
            error_log("Failed to delete examiner session: " . $e->getMessage());
        }
    }

    // Destroy session
    session_unset();
    session_destroy();

    header('Location: login');
    exit;
}

// Authentication check for all other routes
if (!isset($_SESSION['examiner_id']) || !isset($_SESSION['examiner_token'])) {
    header('Location: login');
    exit;
}

// Verify examiner session token is valid
try {
    $session_token = $_SESSION['examiner_token'];
    $stmt = $pdo->prepare("SELECT * FROM examiner_sessions WHERE session_token = ? AND expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();
    
    if (!$session) {
        // Invalid or expired session
        session_unset();
        session_destroy();
        header('Location: login');
        exit;
    }
} catch (PDOException $e) {
    error_log("Session verification failed: " . $e->getMessage());
    header('Location: login');
    exit;
}

// Route to the appropriate page
$page_file = __DIR__ . "/{$route}.php";

if (file_exists($page_file)) {
    require $page_file;
} else {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}
?>
