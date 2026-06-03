<?php
// Helper functions for Kenya EduHub

// Security functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// File handling functions
function allowedFileTypes() {
    return [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif'
    ];
}

function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

function isAllowedFileType($filename) {
    $extension = getFileExtension($filename);
    $allowedTypes = allowedFileTypes();
    return isset($allowedTypes[$extension]);
}

function generateUniqueFilename($originalName) {
    $extension = getFileExtension($originalName);
    $basename = pathinfo($originalName, PATHINFO_FILENAME);
    $timestamp = time();
    $random = mt_rand(1000, 9999);
    return $basename . '_' . $timestamp . '_' . $random . '.' . $extension;
}

// User functions
function getUserById($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getUserByEmail($email) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateUserLastLogin($userId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    return $stmt->execute([$userId]);
}

// Resource functions
function getResourceById($resourceId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as uploader_name, u.email as uploader_email 
                           FROM resources r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.id = ?");
    $stmt->execute([$resourceId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function incrementDownloadCount($resourceId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE resources SET download_count = download_count + 1 WHERE id = ?");
    return $stmt->execute([$resourceId]);
}

function recordDownload($userId, $resourceId) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO downloads (user_id, resource_id, download_date) VALUES (?, ?, NOW())");
    return $stmt->execute([$userId, $resourceId]);
}

// Validation functions
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validateName($name) {
    return strlen(trim($name)) >= 2 && strlen(trim($name)) <= 100;
}

// Notification functions
function createNotification($userId, $message, $type = 'info') {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, ?, NOW())");
    return $stmt->execute([$userId, $message, $type]);
}

function getUnreadNotifications($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function markNotificationAsRead($notificationId) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
    return $stmt->execute([$notificationId]);
}

// Search functions
function searchResources($query, $limit = 20, $offset = 0) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT r.*, u.full_name as uploader_name 
                           FROM resources r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.status = 'approved' 
                           AND (r.title LIKE ? OR r.description LIKE ? OR r.subject LIKE ?)
                           ORDER BY r.created_at DESC 
                           LIMIT ? OFFSET ?");
    
    $searchTerm = "%{$query}%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Statistics functions
function getUserStats($userId) {
    global $pdo;
    $stats = [];
    
    // Resources uploaded
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM resources WHERE user_id = ? AND status = 'approved'");
    $stmt->execute([$userId]);
    $stats['resources_uploaded'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Downloads made
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM downloads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['downloads_made'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Resources downloaded (unique)
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT resource_id) as count FROM downloads WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['unique_downloads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    return $stats;
}

function getPlatformStats() {
    global $pdo;
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total resources
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM resources WHERE status = 'approved'");
    $stats['total_resources'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total downloads
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM downloads");
    $stats['total_downloads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Active users (last 30 days)
    $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) as count FROM downloads WHERE download_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['active_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    return $stats;
}


// Format file size
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Time ago function
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $ago = $now - $time;
    
    if ($ago < 1) {
        return 'just now';
    }
    
    $periods = [
        12 * 30 * 24 * 60 * 60  =>  'year',
        30 * 24 * 60 * 60       =>  'month',
        24 * 60 * 60            =>  'day',
        60 * 60                 =>  'hour',
        60                      =>  'minute',
        1                       =>  'second'
    ];
    
    foreach ($periods as $seconds => $name) {
        if ($ago >= $seconds) {
            $count = floor($ago / $seconds);
            return $count . ' ' . $name . ($count > 1 ? 's' : '') . ' ago';
        }
    }
    
    return 'just now';
}

// CSRF protection
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function isRateLimited($identifier, $limit = 5, $window = 300) {
    $cache_key = "rate_limit_" . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = ['count' => 0, 'start' => time()];
    }
    
    $data = $_SESSION[$cache_key];
    
    // Reset window if expired
    if (time() - $data['start'] > $window) {
        $_SESSION[$cache_key] = ['count' => 1, 'start' => time()];
        return false;
    }
    
    // Check limit
    if ($data['count'] >= $limit) {
        return true;
    }
    
    // Increment count
    $_SESSION[$cache_key]['count']++;
    return false;
}

// Remember me functions
function setRememberCookie($userId, $conn) {
    $token = generateToken();
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    
    try {
        // Check if remember_tokens table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
        
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Table exists, use it
            $stmt = $conn->prepare("INSERT INTO remember_tokens (user_id, token, expires_at) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $userId, $token, date('Y-m-d H:i:s', $expires));
            $stmt->execute();
        } else {
            // Table doesn't exist, use users table
            $stmt = $conn->prepare("UPDATE users SET remember_token = ?, remember_token_expires = ? WHERE id = ?");
            $stmt->bind_param("ssi", $token, date('Y-m-d H:i:s', $expires), $userId);
            $stmt->execute();
        }
        
        // Set cookie
        setcookie('remember_token', $token, $expires, '/', '', false, true);
        
    } catch (Exception $e) {
        error_log("Set remember cookie error: " . $e->getMessage());
    }
}

function deleteRememberCookie($userId, $conn) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        try {
            // Check if remember_tokens table exists
            $tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
            
            if ($tableCheck && $tableCheck->num_rows > 0) {
                // Table exists, use it
                $stmt = $conn->prepare("DELETE FROM remember_tokens WHERE token = ? AND user_id = ?");
                $stmt->bind_param("si", $token, $userId);
                $stmt->execute();
            } else {
                // Table doesn't exist, clear from users table
                $stmt = $conn->prepare("UPDATE users SET remember_token = '', remember_token_expires = ? WHERE id = ?");
                $stmt->bind_param("si", '', date('Y-m-d H:i:s', time() - 3600), $userId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Delete remember cookie error: " . $e->getMessage());
        }
        
        // Delete cookie
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

function validateRememberCookie($conn) {
    if (!isset($_COOKIE['remember_token'])) {
        return false;
    }
    
    $token = $_COOKIE['remember_token'];
    
    try {
        // Check if remember_tokens table exists
        $tableCheck = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
        
        if ($tableCheck && $tableCheck->num_rows > 0) {
            // Table exists, use it
            $stmt = $conn->prepare("SELECT rt.user_id, u.email, u.full_name 
                                   FROM remember_tokens rt 
                                   JOIN users u ON rt.user_id = u.id 
                                   WHERE rt.token = ? AND rt.expires_at > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Set session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                
                return true;
            }
        } else {
            // Table doesn't exist, check users table directly with token
            $stmt = $conn->prepare("SELECT id, email, full_name 
                                   FROM users 
                                   WHERE remember_token = ? AND remember_token_expires > NOW()");
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                
                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Remember cookie validation error: " . $e->getMessage());
        return false;
    }
    
    return false;
}
?>
