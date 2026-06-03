<?php
/**
 * Remember Me functionality for Kenya EduHub
 * Handles cookie-based authentication for persistent login
 */

/**
 * Generate a secure remember me token
 */
function generateRememberToken($length = 64) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Set remember me cookie
 */
function setRememberCookie($userId, $pdo) {
    try {
        // Clean up old tokens for this user
        $cleanupStmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $cleanupStmt->execute([$userId]);
        
        // Generate new token
        $token = generateRememberToken(64);
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        // Store in database
        $stmt = $pdo->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $token, $expires]);
        
        // Set cookie
        setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to set remember cookie: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete remember me cookie
 */
function deleteRememberCookie($userId, $pdo) {
    try {
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        // Delete cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to delete remember cookie: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate remember me cookie and login user
 */
function validateRememberCookie($pdo) {
    try {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        
        // Find token in database
        $stmt = $pdo->prepare("
            SELECT rt.user_id, u.email, u.full_name, u.status, u.email_verified
            FROM remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token = ? AND rt.expires_at > NOW()
        ");
        $stmt->execute([$token]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // Invalid or expired token, delete it
            deleteRememberCookie(0, $pdo);
            return false;
        }
        
        // Check if user is active and verified
        if ($result['status'] !== 'active' || !$result['email_verified']) {
            return false;
        }
        
        // Set session
        $_SESSION['user_id'] = $result['user_id'];
        $_SESSION['user_email'] = $result['email'];
        $_SESSION['user_username'] = $result['full_name'];
        
        // Generate new token for security (token rotation)
        setRememberCookie($result['user_id'], $pdo);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to validate remember cookie: " . $e->getMessage());
        return false;
    }
}

/**
 * Clean up expired remember me tokens
 */
function cleanupExpiredTokens($pdo) {
    try {
        $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE expires_at < NOW()");
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        error_log("Failed to cleanup expired tokens: " . $e->getMessage());
        return false;
    }
}
?>
