<?php
// Parent Children API
session_start();
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
        // Get all children of this parent using student_parents table
        $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                               FROM students s
                               JOIN student_parents sp ON s.id = sp.student_id
                               LEFT JOIN classes c ON s.class_id = c.id
                               LEFT JOIN streams st ON s.stream_id = st.id
                               WHERE sp.parent_id = ? AND s.status = 'active'
                               ORDER BY s.first_name, s.last_name");
        $stmt->execute([$parent_id]);
        $children = $stmt->fetchAll();
        
        // Calculate fee balance for each child
        foreach ($children as &$child) {
            $child_id = $child['id'];
            $class_id = $child['class_id'];
            
            $current_term = 'Term 1';
            $current_year = date('Y');
            
            // Get total fees
            $total_fees = 0;
            if ($class_id) {
                $stmt = $pdo->prepare("SELECT amount FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ?");
                $stmt->execute([$school_id, $class_id, $current_term, $current_year]);
                $fee_structure = $stmt->fetch();
                if ($fee_structure) {
                    $total_fees = $fee_structure['amount'];
                }
            }
            
            // Get total payments
            $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM fee_payments WHERE student_id = ?");
            $stmt->execute([$child_id]);
            $payments = $stmt->fetch();
            $total_paid = $payments['total_paid'];
            
            // Calculate balance
            $balance = $total_fees - $total_paid;
            $child['fee_balance'] = $balance;
            $child['total_fees'] = $total_fees;
            $child['total_paid'] = $total_paid;
            
            // Get attendance rate (last 30 days)
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
            $stmt->execute([$child_id]);
            $attendance_total = $stmt->fetch()['total'];
            
            $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE student_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND status = 'present'");
            $stmt->execute([$child_id]);
            $attendance_present = $stmt->fetch()['present'];
            
            $attendance_rate = $attendance_total > 0 ? round(($attendance_present / $attendance_total) * 100, 1) : 0;
            $child['attendance_rate'] = $attendance_rate;
            
            // Get performance records count
            $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM academic_performance WHERE student_id = ? AND term = ? AND year = ?");
            $stmt->execute([$child_id, $current_term, $current_year]);
            $child['performance_records'] = $stmt->fetch()['total'];
        }
        
        echo json_encode(['success' => true, 'data' => $children]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    }
} catch (PDOException $e) {
    error_log("Parent children API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
