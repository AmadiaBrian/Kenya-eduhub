<?php
// Parent Fines API
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

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

try {
    // Get parent's phone number
    $parent_phone = '';
    $stmt = $pdo->prepare("SELECT phone FROM parents WHERE id = ?");
    $stmt->execute([$parent_id]);
    $parent = $stmt->fetch();
    if ($parent) {
        $parent_phone = $parent['phone'];
    }

    // Get parent's children
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();

    // Get fines for parent's children
    $fines = [];
    if (!empty($children)) {
        $child_ids = array_column($children, 'id');
        $placeholders = str_repeat('?,', count($child_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT lf.*, b.title, b.author,
                  CASE 
                      WHEN lf.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  END as user_name,
                  CASE 
                      WHEN lf.user_type = 'student' THEN s.admission_number
                  END as user_identifier,
                  s.id as student_id
                  FROM library_fines lf
                  JOIN books b ON lf.book_id = b.id
                  JOIN students s ON lf.user_id = s.id AND lf.user_type = 'student'
                  WHERE lf.user_id IN ($placeholders) AND lf.user_type = 'student'
                  ORDER BY lf.issue_date DESC");
        $stmt->execute($child_ids);
        $fines = $stmt->fetchAll();
    }

    // Calculate fine statistics
    $fine_stats = [
        'total_fines' => count($fines),
        'total_amount' => array_sum(array_column($fines, 'amount')),
        'total_paid' => array_sum(array_column($fines, 'amount_paid')),
        'unpaid_amount' => 0,
        'paid_count' => 0,
        'unpaid_count' => 0,
        'partial_count' => 0
    ];

    foreach ($fines as $fine) {
        $fine_stats['unpaid_amount'] += ($fine['amount'] - $fine['amount_paid']);
        if ($fine['status'] === 'paid') {
            $fine_stats['paid_count']++;
        } elseif ($fine['status'] === 'unpaid') {
            $fine_stats['unpaid_count']++;
        } elseif ($fine['status'] === 'partial') {
            $fine_stats['partial_count']++;
        }
    }

    // Get overdue books for parent's children
    $overdue_books = [];
    if (!empty($children)) {
        $child_ids = array_column($children, 'id');
        $placeholders = str_repeat('?,', count($child_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT bb.*, b.title, b.author, b.isbn,
                  CASE 
                      WHEN bb.borrower_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  END as student_name,
                  CASE 
                      WHEN bb.borrower_type = 'student' THEN s.admission_number
                  END as admission_number,
                  DATEDIFF(CURDATE(), bb.due_date) as days_overdue
                  FROM book_borrowings bb
                  JOIN books b ON bb.book_id = b.id
                  JOIN students s ON bb.borrower_id = s.id AND bb.borrower_type = 'student'
                  WHERE bb.borrower_id IN ($placeholders) AND bb.borrower_type = 'student'
                  AND bb.status = 'borrowed'
                  AND bb.due_date < CURDATE()
                  ORDER BY bb.due_date ASC");
        $stmt->execute($child_ids);
        $overdue_books = $stmt->fetchAll();
    }

    // Organize fines by child
    $fines_by_child = [];
    foreach ($children as $child) {
        $child_fines = array_filter($fines, function($fine) use ($child) {
            return $fine['student_id'] == $child['id'];
        });
        
        $child_overdue = array_filter($overdue_books, function($book) use ($child) {
            return $book['borrower_id'] == $child['id'];
        });
        
        $fines_by_child[$child['id']] = [
            'child' => [
                'id' => $child['id'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class' => $child['class_name'] ?? 'Not assigned',
                'stream' => $child['stream_name'] ?? 'Not assigned'
            ],
            'fines' => array_values($child_fines),
            'overdue_books' => array_values($child_overdue),
            'total_fines' => count($child_fines),
            'total_amount' => array_sum(array_column($child_fines, 'amount')),
            'total_paid' => array_sum(array_column($child_fines, 'amount_paid')),
            'unpaid_amount' => array_sum(array_column($child_fines, 'amount')) - array_sum(array_column($child_fines, 'amount_paid'))
        ];
    }

    echo json_encode([
        'success' => true,
        'parent' => [
            'id' => $parent_id,
            'name' => $_SESSION['parent_name'] ?? '',
            'email' => $_SESSION['parent_email'] ?? '',
            'phone' => $parent_phone
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
        'fines_by_child' => $fines_by_child,
        'fine_stats' => $fine_stats,
        'overdue_books' => $overdue_books
    ]);
    
} catch (PDOException $e) {
    error_log("Parent fines API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
