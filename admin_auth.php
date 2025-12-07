<?php
session_start();
require_once 'config.php';

if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    // Check if user exists and is an admin
    $stmt = $conn->prepare("SELECT id, name, email, password, is_verified FROM users WHERE email = ? AND role = 'admin'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if email is verified
            if (!$user['is_verified']) {
                $_SESSION['pending_user_email'] = $email;
                header("Location: verify_code.php");
                exit();
            }

            // Set session variables
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['name'];
            $_SESSION['admin_email'] = $user['email'];
            
            // Debug: Log session data
            error_log('Login successful. Session data: ' . print_r($_SESSION, true));
            
            // Redirect to admin dashboard
            header("Location: admin_page.php");
            exit();
        }
    }
    
    // If we get here, login failed
    $_SESSION['login_error'] = 'Invalid email or password';
    header("Location: admin_login.php");
    exit();
}

// If not a POST request, redirect to login
header("Location: admin_login.php");
exit();
?>
