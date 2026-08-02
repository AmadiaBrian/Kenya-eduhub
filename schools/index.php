<?php
// Schools Main Router - Handles all school routes
session_start();
require_once __DIR__ . '/../config.php';

$route = $_GET['route'] ?? 'login';

// Whitelist of allowed routes
$allowed_routes = [
    'login',
    'logout',
    'dashboard',
    'account',
    'attendance',
    'calendar',
    'classes',
    'disciplinary-action-types',
    'disciplinary',
    'disciplinary_document',
    'disciplinary_view',
    'duty-assignments',
    'exam-types',
    'examination-heads',
    'examiners',
    'fees',
    'finance-managers',
    'grading',
    'invoices',
    'librarians',
    'parents',
    'performance',
    'profile',
    'results',
    'settings',
    'streams',
    'students',
    'subjects',
    'teachers',
    'timetable'
];

// Validate route
if (!in_array($route, $allowed_routes)) {
    header('HTTP/1.0 404 Not Found');
    require __DIR__ . '/404.php';
    exit;
}

// Handle login route separately (no auth required)
if ($route === 'login') {
    // If already logged in with valid school session, redirect to dashboard
    if (isset($_SESSION['school_id']) && isset($_SESSION['school_token'])) {
        try {
            $session_token = $_SESSION['school_token'];
            $stmt = $pdo->prepare("SELECT * FROM school_sessions WHERE session_token = ? AND expires_at > NOW()");
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
    <title>Kenya EduHub - Schools Portal</title>
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
            max-width: 900px;
            padding: 48px 40px;
            display: flex;
            gap: 60px;
            align-items: center;
        }
        
        .auth-info {
            flex: 1;
            padding-right: 40px;
        }
        
        .auth-info .logo-circle {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            flex-shrink: 0;
        }
        
        .auth-info .logo-circle span {
            font-weight: bold;
            font-size: 24px;
        }
        
        .auth-info .logo-text {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            margin-bottom: 24px;
        }
        
        .auth-info .logo-text .logo-circle {
            width: 50px;
            height: 50px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 2px;
            flex-shrink: 0;
            margin-bottom: 0;
        }
        
        .auth-info h1 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .auth-info h2 {
            font-size: 18px;
            font-weight: 400;
            color: #5f6368;
            margin-bottom: 24px;
        }
        
        .auth-info p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.5;
            margin-bottom: 16px;
        }
        
        .feature-list {
            list-style: none;
            margin-top: 24px;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            padding: 8px 0;
            font-size: 14px;
            color: #5f6368;
        }
        
        .feature-list li i {
            color: #FF6B35;
            margin-right: 12px;
            font-size: 16px;
        }
        
        .auth-form-container {
            flex: 1;
            max-width: 400px;
        }
        
        .auth-tabs {
            display: flex;
            gap: 16px;
            margin-bottom: 32px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .auth-tab {
            padding: 12px 16px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 14px;
            font-weight: 500;
            color: #5f6368;
            cursor: pointer;
            transition: color 0.2s, border-color 0.2s;
            font-family: inherit;
        }
        
        .auth-tab:hover {
            color: #202124;
        }
        
        .auth-tab.active {
            color: #FF6B35;
            border-bottom-color: #FF6B35;
        }
        
        .auth-form h2 {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .auth-form p {
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
            letter-spacing: 0.3px;
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
        
        .form-control::placeholder {
            color: #9aa0a6;
        }
        
        .form-control small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #5f6368;
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
        
        .btn-primary:active {
            background: #cc4a20;
            border-color: #cc4a20;
        }
        
        .forgot-link {
            display: inline-block;
            margin-top: 8px;
            font-size: 14px;
            color: #FF6B35;
            text-decoration: none;
        }
        
        .forgot-link:hover {
            text-decoration: underline;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        @media (max-width: 900px) {
            .auth-container {
                flex-direction: column;
                max-width: 450px;
                gap: 32px;
                padding: 32px 24px;
            }
            
            .auth-info {
                padding-right: 0;
                text-align: center;
            }
            
            .auth-info > div:first-child {
                justify-content: center;
            }
            
            .feature-list {
                text-align: left;
            }
            
            .auth-form-container {
                max-width: 100%;
            }
        }
        
        @media (max-width: 768px) {
            .auth-container {
                padding: 24px 20px;
                gap: 24px;
            }
            
            .auth-info h1 {
                font-size: 20px;
            }
            
            .auth-info h2 {
                font-size: 16px;
            }
            
            .feature-list li {
                font-size: 13px;
            }
        }
        
        @media (max-width: 480px) {
            .auth-container {
                padding: 20px 16px;
                gap: 20px;
            }
            
            .auth-info > div:first-child {
                font-size: 1.25rem;
                gap: 0.5rem;
            }
            
            .auth-info h1 {
                font-size: 18px;
            }
            
            .auth-info h2 {
                font-size: 14px;
            }
            
            .auth-info p {
                font-size: 13px;
            }
            
            .feature-list li {
                font-size: 12px;
                padding: 6px 0;
            }
            
            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn-primary {
                padding: 11px 20px;
                font-size: 13px;
            }
            
            .auth-tabs {
                gap: 12px;
            }
            
            .auth-tab {
                padding: 10px 12px;
                font-size: 13px;
            }
        }
            
            .auth-info > div:first-child > div:first-child {
                width: 40px;
                height: 40px;
            }
            
            .auth-info > div:first-child > div:first-child span {
                font-size: 20px;
            }
            
            .auth-info > div:first-child > div:first-child span span:first-child {
                font-size: 24px;
            }
            
            .auth-info h1 {
                font-size: 20px;
            }
            
            .auth-info p {
                font-size: 14px;
            }
            
            .auth-tabs {
                gap: 12px;
            }
            
            .auth-tab {
                padding: 10px 14px;
                font-size: 13px;
            }
            
            .auth-form h2 {
                font-size: 20px;
            }
            
            .auth-form p {
                font-size: 14px;
            }
            
            .form-control {
                padding: 12px 14px;
                font-size: 15px;
            }
            
            .btn-primary {
                padding: 11px 20px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 360px) {
            .auth-container {
                padding: 20px 16px;
                gap: 20px;
            }
            
            .auth-info > div:first-child {
                font-size: 1.1rem;
                gap: 0.4rem;
            }
            
            .auth-info > div:first-child > div:first-child {
                width: 36px;
                height: 36px;
            }
            
            .auth-info > div:first-child > div:first-child span {
                font-size: 18px;
            }
            
            .auth-info > div:first-child > div:first-child span span:first-child {
                font-size: 22px;
            }
            
            .auth-info h1 {
                font-size: 18px;
            }
            
            .auth-tabs {
                gap: 8px;
            }
            
            .auth-tab {
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .auth-form h2 {
                font-size: 18px;
            }
            
            .form-control {
                padding: 11px 12px;
                font-size: 14px;
            }
            
            .btn-primary {
                padding: 10px 16px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-info">
            <div style="display: flex; align-items: center; gap: 0.75rem; font-size: 1.5rem; font-weight: bold; color: white; margin-bottom: 24px;">
                <div style="width: 50px; height: 50px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 2px;">
                    <span style="font-weight: bold; font-size: 24px;">
                        <span style="color: #FF6B35; font-size: 28px;">K</span><span style="color: #008000; font-size: 24px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35;">Kenya</span> <span style="color: #008000;">EduHub</span>
            </div>
            <h1>Schools Portal</h1>
            <p>Manage your school efficiently with our comprehensive management system. Streamline student records, attendance, performance tracking, and parent communication.</p>
            
            <ul class="feature-list">
                <li><i class="fas fa-user-graduate"></i> Student Management</li>
                <li><i class="fas fa-chalkboard-teacher"></i> Class & Stream Management</li>
                <li><i class="fas fa-users"></i> Parent Portal</li>
                <li><i class="fas fa-chart-line"></i> Performance Tracking</li>
                <li><i class="fas fa-calendar-check"></i> Attendance System</li>
                <li><i class="fas fa-money-bill-wave"></i> Fee Management</li>
            </ul>
        </div>
        
        <div class="auth-form-container">
            <div class="auth-tabs">
                <button class="auth-tab active" data-tab="login">Login</button>
                <button class="auth-tab" data-tab="register">Register School</button>
            </div>
            
            <!-- Login Form -->
            <div class="tab-content active" id="login">
                <div class="auth-form">
                    <h2>Welcome Back</h2>
                    <p>Login to access your school dashboard</p>

                    <div id="loginError" class="error-message" style="display: none; background: #fee; color: #c33; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid #fcc;"></div>

                    <form id="loginForm">
                        <div class="form-group">
                            <label for="loginEmail">Email</label>
                            <input type="email" class="form-control" id="loginEmail" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="loginPassword">Password</label>
                            <input type="password" class="form-control" id="loginPassword" required autocomplete="current-password">
                        </div>
                        <a href="#" class="forgot-link">Forgot Password?</a>
                        <button type="submit" class="btn-primary">Next</button>
                    </form>
                </div>
            </div>
            
            <!-- Register Form -->
            <div class="tab-content" id="register">
                <div class="auth-form">
                    <h2>Register Your School</h2>
                    <p>Join Kenya EduHub and transform your school management</p>

                    <div id="registerMessage" class="message" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; border: 1px solid;"></div>

                    <form id="registerForm">
                        <div class="form-group">
                            <label for="schoolName">School Name</label>
                            <input type="text" class="form-control" id="schoolName" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolEmail">Email</label>
                            <input type="email" class="form-control" id="schoolEmail" required autocomplete="email">
                        </div>
                        <div class="form-group">
                            <label for="schoolPhone">Phone Number</label>
                            <input type="tel" class="form-control" id="schoolPhone" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolPassword">Password</label>
                            <input type="password" class="form-control" id="schoolPassword" required autocomplete="new-password">
                            <small>Minimum 8 characters</small>
                        </div>
                        <div class="form-group">
                            <label for="schoolCounty">County</label>
                            <input type="text" class="form-control" id="schoolCounty" required>
                        </div>
                        <div class="form-group">
                            <label for="schoolType">School Type</label>
                            <select class="form-control" id="schoolType" required>
                                <option value="">Select Type</option>
                                <option value="Primary">Primary</option>
                                <option value="Secondary">Secondary</option>
                                <option value="College">College</option>
                                <option value="University">University</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="admissionPrefix">Admission Prefix</label>
                            <input type="text" class="form-control" id="admissionPrefix" placeholder="e.g., TKNP/B">
                            <small>Optional: Prefix for student admission numbers</small>
                        </div>
                        <div class="form-group">
                            <label for="schoolAddress">Address</label>
                            <textarea class="form-control" id="schoolAddress" rows="2" required></textarea>
                        </div>
                        <button type="submit" class="btn-primary">Register School</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tab switching
        document.querySelectorAll('.auth-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                this.classList.add('active');
                document.getElementById(this.dataset.tab).classList.add('active');
            });
        });
        
        // Login Form Handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('loginEmail').value;
            const password = document.getElementById('loginPassword').value;
            const errorDiv = document.getElementById('loginError');

            // Hide error on new submission
            errorDiv.style.display = 'none';

            try {
                const response = await fetch('api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = 'dashboard';
                } else {
                    errorDiv.textContent = data.error || 'Login failed';
                    errorDiv.style.display = 'block';
                }
            } catch (error) {
                errorDiv.textContent = 'An error occurred. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
        
        // Register Form Handler
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const schoolData = {
                school_name: document.getElementById('schoolName').value,
                email: document.getElementById('schoolEmail').value,
                password: document.getElementById('schoolPassword').value,
                phone: document.getElementById('schoolPhone').value,
                county: document.getElementById('schoolCounty').value,
                school_type: document.getElementById('schoolType').value,
                admission_prefix: document.getElementById('admissionPrefix').value,
                address: document.getElementById('schoolAddress').value
            };

            const messageDiv = document.getElementById('registerMessage');

            // Hide message on new submission
            messageDiv.style.display = 'none';

            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(schoolData)
                });

                const data = await response.json();

                if (data.success) {
                    messageDiv.textContent = 'Registration successful! Your account is pending approval. You will be notified once approved.';
                    messageDiv.style.background = '#e8f5e9';
                    messageDiv.style.color = '#2e7d32';
                    messageDiv.style.borderColor = '#c8e6c9';
                    messageDiv.style.display = 'block';
                    document.getElementById('registerForm').reset();
                    setTimeout(() => {
                        document.querySelector('[data-tab="login"]').click();
                    }, 2000);
                } else {
                    messageDiv.textContent = data.error || 'Registration failed';
                    messageDiv.style.background = '#fee';
                    messageDiv.style.color = '#c33';
                    messageDiv.style.borderColor = '#fcc';
                    messageDiv.style.display = 'block';
                }
            } catch (error) {
                messageDiv.textContent = 'An error occurred. Please try again.';
                messageDiv.style.background = '#fee';
                messageDiv.style.color = '#c33';
                messageDiv.style.borderColor = '#fcc';
                messageDiv.style.display = 'block';
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
    if (isset($_SESSION['school_id'])) {
        try {
            $session_token = $_SESSION['school_session_token'] ?? '';
            if ($session_token) {
                $stmt = $pdo->prepare("DELETE FROM school_sessions WHERE session_token = ?");
                $stmt->execute([$session_token]);
            }
        } catch (PDOException $e) {
            error_log("Failed to delete school session: " . $e->getMessage());
        }
    }

    // Destroy session
    session_unset();
    session_destroy();

    header('Location: login');
    exit;
}

// Authentication check for all other routes
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: login');
    exit;
}

// Verify school session token is valid
try {
    $session_token = $_SESSION['school_token'];
    $stmt = $pdo->prepare("SELECT * FROM school_sessions WHERE session_token = ? AND expires_at > NOW()");
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
