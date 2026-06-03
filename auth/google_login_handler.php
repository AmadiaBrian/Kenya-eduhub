<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/helpers.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// DEBUG
error_log("Google login raw: " . $raw);

if (!is_array($data)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON received"
    ]);
    exit;
}

/* ✅ FIXED FIELD MAPPING */
$uid = $data['uid'] ?? null;
$email = $data['email'] ?? null;
$name = $data['displayName'] ?? null;
$photo = $data['photoURL'] ?? "";

/* ✅ VALIDATION */
if (empty($uid) || empty($email)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields",
        "debug" => $data
    ]);
    exit;
}

try {

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    /* =========================
       CHECK IF USER EXISTS
    ========================== */
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) throw new Exception($conn->error);

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    /* =========================
       USER EXISTS
    ========================== */
    if ($result->num_rows > 0) {

        $user = $result->fetch_assoc();

        // update google info always
        $update = $conn->prepare("
            UPDATE users 
            SET google_uid = ?, photo_url = ?, last_login = NOW() 
            WHERE id = ?
        ");

        $update->bind_param("ssi", $uid, $photo, $user['id']);
        $update->execute();

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $user['name'] ?? $name;
        $_SESSION['google_login'] = true;

        echo json_encode([
            "success" => true,
            "message" => "Login successful"
        ]);
        exit;
    }

    /* =========================
       NEW USER CREATE
    ========================== */

    $randomPass = password_hash(uniqid(), PASSWORD_DEFAULT);

    $insert = $conn->prepare("
        INSERT INTO users (name, email, password, google_uid, photo_url, created_at, last_login)
        VALUES (?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $insert->bind_param("sssss", $name, $email, $randomPass, $uid, $photo);

    if ($insert->execute()) {

        $newId = $conn->insert_id;

        $_SESSION['user_id'] = $newId;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_name'] = $name;
        $_SESSION['google_login'] = true;

        echo json_encode([
            "success" => true,
            "message" => "Account created"
        ]);
        exit;

    } else {
        throw new Exception("Insert failed: " . $insert->error);
    }

} catch (Exception $e) {

    error_log("Google login error: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => "Server error"
    ]);
}
?>