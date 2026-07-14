<?php
// Parent Fees API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $child_id = $_GET['child_id'] ?? null;
        
        if (!$child_id) {
            echo json_encode(['success' => false, 'error' => 'Child ID is required']);
            exit;
        }
        
        // Verify this child belongs to the parent
        $stmt = $pdo->prepare("SELECT id, class_id FROM students WHERE id = ? AND parent_id = ?");
        $stmt->execute([$child_id, $parent_id]);
        $child = $stmt->fetch();
        
        if (!$child) {
            echo json_encode(['success' => false, 'error' => 'Unauthorized access to this child\'s data']);
            exit;
        }
        
        $class_id = $child['class_id'];
        $current_term = 'Term 1';
        $current_year = date('Y');
        
        // Get fee structure
        $fee_structure = null;
        if ($class_id) {
            $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ?");
            $stmt->execute([$school_id, $class_id, $current_term, $current_year]);
            $fee_structure = $stmt->fetch();
        }
        
        // Get payment history
        $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$child_id]);
        $payments = $stmt->fetchAll();
        
        // Calculate totals
        $total_fees = $fee_structure ? $fee_structure['amount'] : 0;
        $total_paid = 0;
        foreach ($payments as $payment) {
            $total_paid += $payment['amount'];
        }
        $balance = $total_fees - $total_paid;
        
        echo json_encode([
            'success' => true,
            'data' => [
                'fee_structure' => $fee_structure,
                'payments' => $payments,
                'total_fees' => $total_fees,
                'total_paid' => $total_paid,
                'balance' => $balance
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    error_log("Parent fees API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
