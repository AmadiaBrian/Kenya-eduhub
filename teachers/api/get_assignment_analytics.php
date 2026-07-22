<?php
session_start();
require_once '../../config.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    error_log("Analytics: Unauthorized - no teacher_id in session");
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$assignment_id = $_GET['assignment_id'] ?? null;

if (!$assignment_id) {
    error_log("Analytics: No assignment_id provided");
    echo json_encode(['success' => false, 'error' => 'Assignment ID required']);
    exit;
}

try {
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'] ?? null;
    
    error_log("Analytics: teacher_id=$teacher_id, school_id=$school_id, assignment_id=$assignment_id");
    
    // Verify teacher owns this assignment - check by teacher_id only if school_id is null
    if ($school_id) {
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ? AND school_id = ?");
        $stmt->execute([$assignment_id, $teacher_id, $school_id]);
    } else {
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assignment_id, $teacher_id]);
    }
    
    if (!$stmt->fetch()) {
        error_log("Analytics: Assignment not found or no permission");
        echo json_encode(['success' => false, 'error' => 'Assignment not found or you do not have permission']);
        exit;
    }
    
    // Check if assignment_downloads table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'assignment_downloads'");
    if ($stmt->rowCount() == 0) {
        // Table doesn't exist, return empty analytics
        echo json_encode([
            'success' => true,
            'total_downloads' => 0,
            'unique_downloaders' => 0,
            'last_download' => null,
            'downloads' => []
        ]);
        exit;
    }
    
    // Get download statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM assignment_downloads WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    $total_downloads = $stmt->fetch()['total'];
    
    // Get unique downloaders
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) as unique_count FROM assignment_downloads WHERE assignment_id = ?");
    $stmt->execute([$assignment_id]);
    $unique_downloaders = $stmt->fetch()['unique_count'];
    
    // Get last download
    $stmt = $pdo->prepare("SELECT download_date FROM assignment_downloads WHERE assignment_id = ? ORDER BY download_date DESC LIMIT 1");
    $stmt->execute([$assignment_id]);
    $last_download = $stmt->fetch();
    $last_download_date = $last_download ? date('M d, H:i', strtotime($last_download['download_date'])) : null;
    
    // Get download history
    $stmt = $pdo->prepare("SELECT ad.*, 
                          CASE ad.user_type
                              WHEN 'teacher' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM teachers WHERE id = ad.user_id)
                              WHEN 'parent' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM parents WHERE id = ad.user_id)
                              WHEN 'student' THEN (SELECT CONCAT(first_name, ' ', last_name) FROM students WHERE id = ad.user_id)
                              ELSE ad.user_name
                          END as full_name
                          FROM assignment_downloads ad
                          WHERE ad.assignment_id = ?
                          ORDER BY ad.download_date DESC
                          LIMIT 50");
    $stmt->execute([$assignment_id]);
    $downloads = $stmt->fetchAll();
    
    $downloads_formatted = [];
    foreach ($downloads as $download) {
        $downloads_formatted[] = [
            'full_name' => $download['full_name'] ?: 'Unknown User',
            'user_type' => ucfirst($download['user_type']),
            'download_date' => date('M d, H:i', strtotime($download['download_date']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'total_downloads' => $total_downloads,
        'unique_downloaders' => $unique_downloaders,
        'last_download' => $last_download_date,
        'downloads' => $downloads_formatted
    ]);
    
} catch (PDOException $e) {
    error_log("Failed to get assignment analytics: " . $e->getMessage());
    error_log("PDO Error code: " . $e->getCode());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}

