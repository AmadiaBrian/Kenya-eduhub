<?php
// Parent Fees API
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

// Load calendar helpers
require_once __DIR__ . '/../../includes/calendar_helpers.php';

// Get calendar status to find active term
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);
$active_term = $calendar_status['current_term']['term_name'] ?? null;

// Get terms from database for current year
$terms = [];
try {
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $term_records = $stmt->fetchAll();
    foreach ($term_records as $term) {
        $terms[] = $term['term_name'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

// Use active term if available, otherwise use first term
$current_term = $active_term ?? ($terms[0] ?? 'Term 1');

try {
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
    
    // Get fee data for all children
    $fee_data = [];
    foreach ($children as $child) {
        $child_id = $child['id'];
        $class_id = $child['class_id'];
        
        // Get fee structure for all terms in current year (all fee types)
        $fee_structures = [];
        if ($class_id) {
            $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND year = ? ORDER BY term, fee_type");
            $stmt->execute([$school_id, $class_id, $current_year]);
            $fee_structures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get payment history for current year (only completed payments)
        $stmt = $pdo->prepare("SELECT * FROM fee_payments WHERE student_id = ? AND year = ? AND status = 'completed' ORDER BY payment_date DESC");
        $stmt->execute([$child_id, $current_year]);
        $payments = $stmt->fetchAll();
        
        // Calculate term-wise balances (only Tuition fees)
        $term_balances = [];
        $year_total_fees = 0;
        $year_total_paid = 0;
        
        foreach ($terms as $term) {
            $term_fee = 0;
            $term_paid = 0;
            
            // Get fee structure for this term (only Tuition)
            foreach ($fee_structures as $fs) {
                if ($fs['term'] === $term && $fs['fee_type'] === 'Tuition') {
                    $term_fee = $fs['amount'];
                    break;
                }
            }
            
            // Get payments for this term (only Tuition)
            foreach ($payments as $payment) {
                if ($payment['term'] === $term && ($payment['fee_type'] === 'Tuition' || $payment['fee_type'] === null)) {
                    $term_paid += $payment['amount'];
                }
            }
            
            $term_balance = $term_fee - $term_paid;
            
            $term_balances[$term] = [
                'fees' => $term_fee,
                'paid' => $term_paid,
                'balance' => $term_balance
            ];
            
            $year_total_fees += $term_fee;
            $year_total_paid += $term_paid;
        }
        
        $year_balance = $year_total_fees - $year_total_paid;
        
        // Calculate fee structure payment status for all fee types
        $fee_structure_status = [];
        foreach ($fee_structures as $fs) {
            $fs_id = $fs['id'];
            $fs_term = $fs['term'];
            $fs_fee_type = $fs['fee_type'];
            $fs_amount = $fs['amount'];
            
            // Get payments for this specific fee structure
            $paid_amount = 0;
            foreach ($payments as $payment) {
                if ($payment['term'] === $fs_term && ($payment['fee_type'] === $fs_fee_type || ($payment['fee_type'] === null && $fs_fee_type === 'Tuition'))) {
                    $paid_amount += $payment['amount'];
                }
            }
            
            $fee_structure_status[] = [
                'id' => $fs_id,
                'fee_type' => $fs_fee_type,
                'term' => $fs_term,
                'year' => $fs['year'],
                'amount' => $fs_amount,
                'paid' => $paid_amount,
                'balance' => $fs_amount - $paid_amount,
                'status' => $paid_amount >= $fs_amount ? 'Paid' : ($paid_amount > 0 ? 'Partial' : 'Not Paid'),
                'description' => $fs['description'] ?? ''
            ];
        }
        
        $fee_data[$child_id] = [
            'child' => [
                'id' => $child['id'],
                'name' => $child['first_name'] . ' ' . $child['last_name'],
                'admission_number' => $child['admission_number'],
                'class' => $child['class_name'] ?? 'Not assigned',
                'stream' => $child['stream_name'] ?? 'Not assigned'
            ],
            'fee_structures' => $fee_structures,
            'payments' => $payments,
            'term_balances' => $term_balances,
            'year_total_fees' => $year_total_fees,
            'year_total_paid' => $year_total_paid,
            'year_balance' => $year_balance,
            'current_year' => $current_year,
            'fee_structure_status' => $fee_structure_status
        ];
    }
    
    echo json_encode([
        'success' => true,
        'parent' => [
            'id' => $parent_id,
            'name' => $_SESSION['parent_name'] ?? '',
            'email' => $_SESSION['parent_email'] ?? '',
            'phone' => $_SESSION['parent_phone'] ?? ''
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
        'fee_data' => $fee_data,
        'current_term' => $current_term,
        'terms' => $terms,
        'current_year' => $current_year
    ]);
    
} catch (PDOException $e) {
    error_log("Parent fees API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
}
