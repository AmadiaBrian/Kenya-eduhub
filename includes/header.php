<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $user_email = $user_avatar = '';

if ($is_logged_in) {
    $user_name = htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'User');
    $user_email = htmlspecialchars($_SESSION['email'] ?? '');
    $user_avatar = strtoupper(substr($user_name, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kenya EduHub - Educational Resource Platform</title>
    <meta name="description" content="Kenya's leading educational platform for sharing and accessing quality learning resources">
    <meta name="keywords" content="Kenya education platform, learning resources, educational materials, students, teachers">
    <meta name="author" content="Kenya EduHub">
    <meta name="robots" content="index, follow">
    
    <!-- External Stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        /* Professional Header */
        .professional-header {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 16px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 24px;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #333;
            font-weight: 700;
            font-size: 24px;
            transition: color 0.3s ease;
        }

        .header-logo:hover {
            color: #667eea;
        }

        .header-logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            color: white;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-nav {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .header-link {
            color: #333;
            text-decoration: none;
            font-weight: 500;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-link:hover {
            color: #667eea;
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .header-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(45deg, #FFD700, #FFA500);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: white;
            backdrop-filter: blur(3px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .header-user-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .header-user-email {
            font-size: 12px;
            color: #666;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .header-btn {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #333;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #667eea;
            transform: translateY(-2px);
        }

        .header-btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }

        .header-btn-primary:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        /* Mobile Menu */
        .mobile-menu {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 2px 0 20px rgba(0, 0, 0, 0.2);
            z-index: 999;
            transition: transform 0.3s ease;
            padding: 80px 0 20px 0;
            border-right: 1px solid rgba(0, 0, 0, 0.1);
        }

        .mobile-menu.active {
            transform: translateX(0);
        }

        .mobile-menu-link {
            display: block;
            padding: 16px 24px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .mobile-menu-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding-left: 32px;
        }

        .mobile-menu-link.logout {
            color: #dc3545;
            border-top: 1px solid rgba(220, 53, 69, 0.2);
            margin-top: 10px;
        }

        .mobile-menu-link.logout:hover {
            background: rgba(220, 53, 69, 0.1);
        }

        /* Mobile Menu Overlay */
        .mobile-menu-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
        }

        .mobile-menu-overlay.active {
            display: block;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .header-container {
                padding: 0 16px;
                gap: 16px;
            }

            .header-nav {
                gap: 16px;
            }

            .header-link {
                padding: 6px 12px;
                font-size: 14px;
            }

            .header-user-info {
                display: none;
            }

            .header-actions {
                gap: 8px;
            }

            .header-logo {
                font-size: 20px;
            }

            .header-logo-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            /* Mobile Menu Toggle */
            .mobile-menu-toggle {
                display: none;
                position: fixed;
                top: 16px;
                left: 16px;
                z-index: 1001;
                background: transparent;
                border: none;
                padding: 8px;
                cursor: pointer;
                width: 32px;
                height: 24px;
                flex-direction: column;
                justify-content: space-between;
            }

            .mobile-menu-toggle span {
                display: block;
                width: 100%;
                height: 2px;
                background: #333;
                border-radius: 1px;
                transition: all 0.3s ease;
            }

            .mobile-menu-toggle:hover span:nth-child(1) {
                transform: translateY(-1px);
            }

            .mobile-menu-toggle:hover span:nth-child(3) {
                transform: translateY(1px);
            }

            .mobile-menu-toggle {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .header-container {
                padding: 0 12px;
            }

            .header-logo {
                font-size: 18px;
            }

            .header-logo-icon {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .header-link {
                padding: 4px 8px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <header class="professional-header">
        <div class="header-container">
            <a href="../index.php" class="header-logo">
                <div class="header-logo-icon">KE</div>
                <span>Kenya EduHub</span>
            </a>
            
            <nav class="header-nav">
                <?php if ($is_logged_in): ?>
                    <a href="../dashboard/index.php" class="header-link">Dashboard</a>
                    <a href="../resources.php" class="header-link">Resources</a>
                    <a href="#" class="header-link">My Courses</a>
                    <a href="#" class="header-link">Community</a>
                <?php else: ?>
                    <a href="../index.php" class="header-link">Home</a>
                    <a href="../auth/login.php" class="header-link">Login</a>
                    <a href="../auth/register.php" class="header-link">Register</a>
                <?php endif; ?>
            </nav>

            <?php if ($is_logged_in): ?>
                <div class="header-user">
                    <div class="header-avatar"><?php echo $user_avatar; ?></div>
                    <div class="header-user-info">
                        <div class="header-user-name"><?php echo $user_name; ?></div>
                        <div class="header-user-email"><?php echo $user_avatar; ?>@eduhub.ke</div>
                    </div>
                </div>

                <div class="header-actions">
                    <a href="../dashboard/index.php" class="header-btn">Dashboard</a>
                    <a href="../auth/logout.php" class="header-btn">Logout</a>
                </div>
            <?php else: ?>
                <div class="header-actions">
                    <a href="../auth/login.php" class="header-btn header-btn-primary">Get Started</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Mobile Navigation Menu -->
        <div class="mobile-menu" id="mobileMenu">
            <?php if ($is_logged_in): ?>
                <a href="../dashboard/index.php" class="mobile-menu-link">Dashboard</a>
                <a href="../resources.php" class="mobile-menu-link">Resources</a>
                <a href="#" class="mobile-menu-link">My Courses</a>
                <a href="#" class="mobile-menu-link">Community</a>
                <a href="../auth/logout.php" class="mobile-menu-link logout">Logout</a>
            <?php else: ?>
                <a href="../index.php" class="mobile-menu-link">Home</a>
                <a href="../auth/login.php" class="mobile-menu-link">Login</a>
                <a href="../auth/register.php" class="mobile-menu-link">Register</a>
            <?php endif; ?>
        </div>
    </header>

    <script>
        function toggleMobileMenu() {
            const mobileMenu = document.getElementById('mobileMenu');
            const overlay = document.createElement('div');
            overlay.className = 'mobile-menu-overlay';
            
            if (mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                // Remove overlay if exists
                const existingOverlay = document.querySelector('.mobile-menu-overlay');
                if (existingOverlay) {
                    existingOverlay.remove();
                }
            } else {
                mobileMenu.classList.add('active');
                document.body.appendChild(overlay);
                
                // Close menu when clicking overlay
                overlay.addEventListener('click', function() {
                    mobileMenu.classList.remove('active');
                    overlay.remove();
                });
            }
        }
    </script>
</body>
</html>
