<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config.php';

// Get request parameters
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true) ?? $_REQUEST;

$userId = isset($data['user_id']) ? (int)$data['user_id'] : ($_SESSION['user_id'] ?? 0);
$email = isset($data['email']) ? trim($data['email']) : '';

try {
    // 1. Get user details
    $user = null;
    if ($userId > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } elseif (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id, name, email, phone, role, created_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    $currentUserId = (int)$user['id'];

    // 2. Calculate account age
    $accountAge = 'New Member';
    if (!empty($user['created_at'])) {
        $createdDate = new DateTime($user['created_at']);
        $now = new DateTime();
        $interval = $createdDate->diff($now);

        if ($interval->y > 0) {
            $accountAge = $interval->y . ' year' . ($interval->y > 1 ? 's' : '');
        } elseif ($interval->m > 0) {
            $accountAge = $interval->m . ' month' . ($interval->m > 1 ? 's' : '');
        } else {
            $accountAge = $interval->d . ' day' . ($interval->d > 1 ? 's' : '');
        }
    }

    // 3. Get user resources
    $stmt = $pdo->prepare("SELECT r.*, u.name as uploader_name FROM resources r LEFT JOIN users u ON r.user_id = u.id WHERE r.user_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$currentUserId]);
    $userResourcesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $userResources = [];
    $userTotalDownloads = 0;
    $userRecentUploads = 0;
    $oneWeekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));

    foreach ($userResourcesRaw as $res) {
        $downloads = (int)($res['downloads'] ?? $res['download_count'] ?? 0);
        $userTotalDownloads += $downloads;
        if (strtotime($res['created_at'] ?? '') >= strtotime($oneWeekAgo)) {
            $userRecentUploads++;
        }

        $filename = $res['filename'] ?? $res['file_path'] ?? '';
        $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'PDF');

        $userResources[] = [
            'id' => (int)$res['id'],
            'title' => $res['title'] ?? 'Untitled Resource',
            'subject' => $res['subject'] ?? 'General',
            'file_type' => $ext,
            'uploader_name' => $res['uploader_name'] ?? $user['name'],
            'description' => $res['description'] ?? 'No description available',
            'downloads' => $downloads,
            'created_at' => $res['created_at'] ?? ''
        ];
    }

    $userUploadCount = count($userResources);

    // 4. Activity level
    if ($userUploadCount >= 10) {
        $activityLevel = 'Active Contributor';
    } elseif ($userUploadCount >= 5) {
        $activityLevel = 'Regular User';
    } elseif ($userUploadCount >= 2) {
        $activityLevel = 'New Member';
    } else {
        $activityLevel = 'Getting Started';
    }

    // 5. Get all resources
    $stmt = $pdo->prepare("SELECT r.*, u.name as uploader_name FROM resources r LEFT JOIN users u ON r.user_id = u.id ORDER BY r.created_at DESC LIMIT 50");
    $stmt->execute();
    $allResourcesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $allResources = [];
    foreach ($allResourcesRaw as $res) {
        $downloads = (int)($res['downloads'] ?? $res['download_count'] ?? 0);
        $filename = $res['filename'] ?? $res['file_path'] ?? '';
        $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'PDF');

        $allResources[] = [
            'id' => (int)$res['id'],
            'title' => $res['title'] ?? 'Untitled Resource',
            'subject' => $res['subject'] ?? 'General',
            'file_type' => $ext,
            'uploader_name' => $res['uploader_name'] ?? 'Unknown User',
            'description' => $res['description'] ?? 'No description available',
            'downloads' => $downloads,
            'created_at' => $res['created_at'] ?? ''
        ];
    }

    // 6. Return response
    echo json_encode([
        'success' => true,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'] ?? 'user',
            'account_age' => $accountAge,
            'activity_level' => $activityLevel
        ],
        'stats' => [
            'my_resources_count' => $userUploadCount,
            'total_downloads' => $userTotalDownloads,
            'recent_uploads' => $userRecentUploads,
            'all_resources_count' => count($allResources)
        ],
        'my_resources' => $userResources,
        'all_resources' => $allResources
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading dashboard: ' . $e->getMessage()
    ]);
}
?>