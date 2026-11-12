<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';
require_once '../includes/helpers.php';

// Parse request body
$inputJSON = file_get_contents('php://input');
$json = json_decode($inputJSON, true);

$user_id = (int)($json['user_id'] ?? $_POST['user_id'] ?? $_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        "success" => false,
        "message" => "You must be logged in to manage settings"
    ]);
    exit();
}

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    echo json_encode([
        "success" => false,
        "message" => "User account not found"
    ]);
    exit();
}

// Create user_settings table if it doesn't exist
try {
    $conn->query("CREATE TABLE IF NOT EXISTS user_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        email_uploads TINYINT(1) DEFAULT 1,
        email_comments TINYINT(1) DEFAULT 1,
        email_updates TINYINT(1) DEFAULT 0,
        show_profile TINYINT(1) DEFAULT 1,
        show_email TINYINT(1) DEFAULT 0,
        theme VARCHAR(20) DEFAULT 'light',
        language VARCHAR(10) DEFAULT 'en',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user (user_id)
    )");
} catch (Exception $e) {
    error_log("Failed to verify user_settings table: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $json['action'] ?? $_POST['action'] ?? 'update_settings';

    if ($action === 'delete_account') {
        $deletion_reason = trim($json['deletion_reason'] ?? $_POST['deletion_reason'] ?? 'User requested deletion from mobile app');
        $confirm_email = trim($json['confirm_email'] ?? $_POST['confirm_email'] ?? '');

        if (strtolower($confirm_email) !== strtolower($user['email'])) {
            echo json_encode([
                "success" => false,
                "message" => "Email confirmation does not match. Please enter your correct email address."
            ]);
            exit();
        }

        $conn->begin_transaction();
        try {
            // Delete user settings and resources
            $d1 = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
            $d1->bind_param("i", $user_id);
            $d1->execute();

            $d2 = $conn->prepare("DELETE FROM resources WHERE user_id = ?");
            $d2->bind_param("i", $user_id);
            $d2->execute();

            $d3 = $conn->prepare("DELETE FROM users WHERE id = ?");
            $d3->bind_param("i", $user_id);
            $d3->execute();

            $conn->commit();

            echo json_encode([
                "success" => true,
                "message" => "Account deleted successfully."
            ]);
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                "success" => false,
                "message" => "Failed to delete account: " . $e->getMessage()
            ]);
            exit();
        }
    } else {
        // Update user settings
        $email_uploads = isset($json['email_uploads']) ? ($json['email_uploads'] ? 1 : 0) : 1;
        $email_comments = isset($json['email_comments']) ? ($json['email_comments'] ? 1 : 0) : 1;
        $email_updates = isset($json['email_updates']) ? ($json['email_updates'] ? 1 : 0) : 0;
        $show_profile = isset($json['show_profile']) ? ($json['show_profile'] ? 1 : 0) : 1;
        $show_email = isset($json['show_email']) ? ($json['show_email'] ? 1 : 0) : 0;
        $theme = trim($json['theme'] ?? 'light');
        $language = trim($json['language'] ?? 'en');

        $check_stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();

        if ($check_stmt->get_result()->num_rows > 0) {
            $update_stmt = $conn->prepare("UPDATE user_settings SET
                email_uploads = ?,
                email_comments = ?,
                email_updates = ?,
                show_profile = ?,
                show_email = ?,
                theme = ?,
                language = ?
                WHERE user_id = ?");
            $update_stmt->bind_param("iiiisssi",
                $email_uploads,
                $email_comments,
                $email_updates,
                $show_profile,
                $show_email,
                $theme,
                $language,
                $user_id
            );
            $update_stmt->execute();
        } else {
            $insert_stmt = $conn->prepare("INSERT INTO user_settings
                (user_id, email_uploads, email_comments, email_updates, show_profile, show_email, theme, language)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("iiiiisss",
                $user_id,
                $email_uploads,
                $email_comments,
                $email_updates,
                $show_profile,
                $show_email,
                $theme,
                $language
            );
            $insert_stmt->execute();
        }

        echo json_encode([
            "success" => true,
            "message" => "Settings saved successfully!",
            "settings" => [
                "email_uploads" => (bool)$email_uploads,
                "email_comments" => (bool)$email_comments,
                "email_updates" => (bool)$email_updates,
                "show_profile" => (bool)$show_profile,
                "show_email" => (bool)$show_email,
                "theme" => $theme,
                "language" => $language
            ]
        ]);
    }
} else {
    // GET user settings
    $get_stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
    $get_stmt->bind_param("i", $user_id);
    $get_stmt->execute();
    $settings = $get_stmt->get_result()->fetch_assoc();

    echo json_encode([
        "success" => true,
        "settings" => [
            "email_uploads" => (bool)($settings['email_uploads'] ?? 1),
            "email_comments" => (bool)($settings['email_comments'] ?? 1),
            "email_updates" => (bool)($settings['email_updates'] ?? 0),
            "show_profile" => (bool)($settings['show_profile'] ?? 1),
            "show_email" => (bool)($settings['show_email'] ?? 0),
            "theme" => $settings['theme'] ?? 'light',
            "language" => $settings['language'] ?? 'en'
        ]
    ]);
}
?>