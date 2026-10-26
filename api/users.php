<?php
header('Content-Type: application/json');

try {
    require_once '../config.php';
    require_once '../includes/security_lite.php';

    // Validate CSRF token for POST requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $csrf_token = $_POST['csrf_token'] ?? '';
        if (!validateCSRFLite($csrf_token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
            exit();
        }
    }

    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit();
    }

    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $sql = "SELECT id, name, email, role, is_verified FROM users ORDER BY id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'users' => $users
        ]);
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_role') {
            $user_id = $_POST['user_id'] ?? 0;
            $new_role = $_POST['role'] ?? 'user';
            
            if (!in_array($new_role, ['user', 'admin'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid role']);
                exit();
            }
            
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'User role updated successfully'
            ]);
        } elseif ($action === 'toggle_verification') {
            $user_id = $_POST['user_id'] ?? 0;
            
            $stmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }
            
            $new_status = $user['is_verified'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            echo json_encode([
                'success' => true,
                'message' => $new_status ? 'User verified successfully' : 'User unverified successfully'
            ]);
        } elseif ($action === 'delete_user') {
            $user_id = $_POST['user_id'] ?? 0;

            // Prevent deleting self
            if ($user_id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
                exit();
            }

            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'User not found']);
                exit();
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $result = $stmt->execute([$user_id]);

            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>