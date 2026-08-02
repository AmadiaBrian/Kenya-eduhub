<?php
// Parent Dashboard API
// Disable error reporting for API responses
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

// Check authentication
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

try {
    // Get parent details
    $stmt = $pdo->prepare("SELECT p.*, s.school_name FROM parents p JOIN schools s ON p.school_id = s.id WHERE p.id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    
    if (!$parent) {
        echo json_encode(['success' => false, 'error' => 'Parent not found']);
        exit;
    }
    
    // Get calendar status to find active term
    $current_year = date('Y');
    $terms = [];
    try {
        $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
        $stmt->execute([$school_id, $current_year]);
        $term_records = $stmt->fetchAll();
        foreach ($term_records as $term) {
            $terms[] = $term['term_name'];
        }
    } catch (PDOException $e) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }
    
    if (empty($terms)) {
        $terms = ['Term 1', 'Term 2', 'Term 3'];
    }
    
    $current_term = $terms[0] ?? 'Term 1';
    
    // Get children of this parent
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get statistics for this parent's children
    $stats = [
        'total_children' => count($children),
        'total_fees_due' => 0,
        'attendance_rate' => 0,
        'performance_records' => 0
    ];
    
    foreach ($children as $child) {
        $child_id = $child['id'];
        $class_id = $child['class_id'];
        
        // Calculate fee balance for all terms in the year (like fees API)
        $year_total_fees = 0;
        $year_total_paid = 0;
        
        if ($class_id) {
            // Get all fee structures for this class/year (only Tuition)
            $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? AND fee_type = 'Tuition'");
            $stmt->execute([$school_id, $class_id, $current_year]);
            $fee_result = $stmt->fetch();
            if ($fee_result) {
                $year_total_fees = $fee_result['total'] ?? 0;
            }
            
            // Get all completed payments for this child/year
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed'");
            $stmt->execute([$child_id, $current_year]);
            $payment_result = $stmt->fetch();
            if ($payment_result) {
                $year_total_paid = $payment_result['total_paid'] ?? 0;
            }
        }
        
        $balance = $year_total_fees - $year_total_paid;
        $stats['total_fees_due'] += max(0, $balance);
        
        // Get attendance records
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
        $stmt->execute([$child_id]);
        $attendance_total = $stmt->fetch()['total'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status = 'present'");
        $stmt->execute([$child_id]);
        $attendance_present = $stmt->fetch()['present'];
        
        if ($attendance_total > 0) {
            $stats['attendance_rate'] += ($attendance_present / $attendance_total) * 100;
        }
        
        // Get performance records
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance WHERE student_id = ? AND term = ? AND year = ?");
        $stmt->execute([$child_id, $current_term, $current_year]);
        $stats['performance_records'] += $stmt->fetch()['total'];
    }
    
    // Calculate average attendance rate
    if ($stats['total_children'] > 0) {
        $stats['attendance_rate'] = round($stats['attendance_rate'] / $stats['total_children'], 1);
    }
    
    // Get recent notifications/announcements
    $notifications = [];
    try {
        $stmt = $pdo->prepare("SELECT * FROM school_announcements WHERE school_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$school_id]);
        $notifications = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Table might not exist, ignore
    }
    
    echo json_encode([
        'success' => true,
        'parent' => [
            'id' => $parent['id'],
            'name' => $parent['first_name'] . ' ' . $parent['last_name'],
            'email' => $parent['email'],
            'phone' => $parent['phone'],
            'school_name' => $school_name
        ],
        'children' => array_map(function($child) {
            return [
                'id' => $child['id'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class' => $child['class_name'] ?? 'Not assigned',
                'stream' => $child['stream_name'] ?? 'Not assigned'
            ];
        }, $children),
        'stats' => $stats,
        'current_term' => $current_term,
        'terms' => $terms,
        'notifications' => array_map(function($notif) {
            return [
                'id' => $notif['id'],
                'title' => $notif['title'] ?? '',
                'message' => $notif['message'] ?? '',
                'created_at' => $notif['created_at'] ?? ''
            ];
        }, $notifications)
    ]);
    
} catch (PDOException $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
