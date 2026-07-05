<?php
// Fees Management API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$school_id = get_current_school_id();
$type = $_GET['type'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    require_school_auth();
    
    if ($type === 'structure') {
        // Get fee structures
        try {
            $query = "SELECT fs.*, c.class_name 
                     FROM fee_structure fs
                     JOIN classes c ON fs.class_id = c.id
                     WHERE fs.school_id = ?
                     ORDER BY fs.year DESC, fs.term, c.class_name";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$school_id]);
            $fee_structures = $stmt->fetchAll();
            
            success_response($fee_structures, 'Fee structures retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch fee structures: " . $e->getMessage());
            error_response('Failed to fetch fee structures', 500);
        }
        
    } elseif ($type === 'payments') {
        // Get fee payments
        try {
            $query = "SELECT fp.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name
                     FROM fee_payments fp
                     JOIN students s ON fp.student_id = s.id
                     WHERE s.school_id = ?
                     ORDER BY fp.payment_date DESC";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$school_id]);
            $payments = $stmt->fetchAll();
            
            success_response($payments, 'Payments retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch payments: " . $e->getMessage());
            error_response('Failed to fetch payments', 500);
        }
        
    } elseif ($type === 'balances') {
        // Get fee balances per term (only Tuition fees)
        $term = $_GET['term'] ?? '';
        $year = $_GET['year'] ?? '';
        
        if (empty($term) || empty($year)) {
            error_response('Term and year are required', 400);
        }
        
        try {
            $query = "SELECT s.id, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name,
                     c.class_name, fs.term, fs.year, fs.amount as fee_amount,
                     COALESCE(SUM(fp.amount), 0) as paid_amount,
                     fs.amount - COALESCE(SUM(fp.amount), 0) as balance
                     FROM students s
                     LEFT JOIN classes c ON s.class_id = c.id
                     LEFT JOIN fee_structure fs ON c.id = fs.class_id AND fs.term = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
                     LEFT JOIN fee_payments fp ON s.id = fp.student_id AND fp.term = ? AND fp.year = ?
                     WHERE s.school_id = ? AND s.status = 'active'
                     GROUP BY s.id, s.admission_number, s.first_name, s.last_name, c.class_name, fs.term, fs.year, fs.amount
                     ORDER BY s.admission_number";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$term, $year, $term, $year, $school_id]);
            $balances = $stmt->fetchAll();
            
            success_response($balances, 'Balances retrieved successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to fetch balances: " . $e->getMessage());
            error_response('Failed to fetch balances', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    if ($type === 'structure') {
        // Add or update fee structure
        $required_fields = ['class_id', 'term', 'year', 'amount'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        $class_id = (int)$input['class_id'];
        $term = sanitize_input($input['term']);
        $year = (int)$input['year'];
        $fee_type = !empty($input['fee_type']) ? sanitize_input($input['fee_type']) : 'Tuition';
        $amount = (float)$input['amount'];
        $description = !empty($input['description']) ? sanitize_input($input['description']) : null;
        $fee_id = !empty($input['id']) ? (int)$input['id'] : null;
        
        // Verify class belongs to this school
        try {
            $stmt = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND school_id = ?");
            $stmt->execute([$class_id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Class not found', 404);
            }
        } catch (PDOException $e) {
            error_log("Class verification failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
        
        try {
            if ($fee_id) {
                // Update existing fee structure
                $stmt = $pdo->prepare("UPDATE fee_structure SET class_id = ?, term = ?, year = ?, fee_type = ?, amount = ?, description = ? WHERE id = ? AND school_id = ?");
                $stmt->execute([$class_id, $term, $year, $fee_type, $amount, $description, $fee_id, $school_id]);
                
                log_security_event('FEE_STRUCTURE_UPDATED', "Fee structure updated for ID $fee_id (School ID: $school_id)");
                success_response([], 'Fee structure updated successfully');
            } else {
                // Check if fee structure already exists for this class, term, year, and fee_type
                $stmt = $pdo->prepare("SELECT id FROM fee_structure WHERE class_id = ? AND term = ? AND year = ? AND fee_type = ?");
                $stmt->execute([$class_id, $term, $year, $fee_type]);
                if ($stmt->fetch()) {
                    error_response('Fee structure already exists for this class, term, year, and fee type');
                }
                
                $stmt = $pdo->prepare("INSERT INTO fee_structure (school_id, class_id, term, year, fee_type, amount, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $class_id, $term, $year, $fee_type, $amount, $description]);
                
                log_security_event('FEE_STRUCTURE_ADDED', "Fee structure added for class $class_id (School ID: $school_id)");
                success_response([], 'Fee structure added successfully');
            }
            
        } catch (PDOException $e) {
            error_log("Failed to save fee structure: " . $e->getMessage());
            error_response('Failed to save fee structure', 500);
        }
        
    } elseif ($type === 'payment') {
        // Add payment
        $required_fields = ['admission_number', 'amount', 'payment_date', 'payment_method', 'term', 'year'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                error_response("Field '$field' is required");
            }
        }
        
        $admission_number = sanitize_input($input['admission_number']);
        $amount = (float)$input['amount'];
        $payment_date = sanitize_input($input['payment_date']);
        $payment_method = sanitize_input($input['payment_method']);
        $term = sanitize_input($input['term']);
        $year = (int)$input['year'];
        $fee_type = !empty($input['fee_type']) ? sanitize_input($input['fee_type']) : 'Tuition';
        $transaction_id = !empty($input['transaction_id']) ? sanitize_input($input['transaction_id']) : null;
        
        // Validate payment method
        $valid_methods = ['Cash', 'M-Pesa', 'Bank Transfer', 'Cheque'];
        if (!in_array($payment_method, $valid_methods)) {
            error_response('Invalid payment method');
        }
        
        // Verify student exists and belongs to this school
        try {
            $stmt = $pdo->prepare("SELECT id, admission_number FROM students WHERE admission_number = ? AND school_id = ?");
            $stmt->execute([$admission_number, $school_id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                // Log the failed lookup for debugging
                error_log("Student lookup failed - Admission Number: '$admission_number', School ID: $school_id");
                error_response('Student not found with this admission number. Please check the admission number and try again.', 404);
            }
            $student_id = $student['id'];
        } catch (PDOException $e) {
            error_log("Student verification failed: " . $e->getMessage());
            error_response('Database error', 500);
        }
        
        // Generate receipt number
        require_once __DIR__ . '/security.php';
        $receipt_number = generate_receipt_number();
        
        try {
            $stmt = $pdo->prepare("INSERT INTO fee_payments (student_id, amount, payment_date, payment_method, transaction_id, term, year, fee_type, receipt_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $amount, $payment_date, $payment_method, $transaction_id, $term, $year, $fee_type, $receipt_number]);
            
            log_security_event('PAYMENT_RECORDED', "Payment recorded: KES $amount for student $student_id (Receipt: $receipt_number)");
            
            success_response(['receipt_number' => $receipt_number], 'Payment recorded successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to record payment: " . $e->getMessage());
            error_response('Failed to record payment', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    require_school_auth();
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if (!$id) {
        error_response('ID is required');
    }
    
    if ($type === 'structure') {
        // Delete fee structure
        try {
            $stmt = $pdo->prepare("SELECT id FROM fee_structure WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Fee structure not found', 404);
            }
            
            $stmt = $pdo->prepare("DELETE FROM fee_structure WHERE id = ?");
            $stmt->execute([$id]);
            
            log_security_event('FEE_STRUCTURE_DELETED', "Fee structure deleted: ID $id (School ID: $school_id)");
            
            success_response([], 'Fee structure deleted successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to delete fee structure: " . $e->getMessage());
            error_response('Failed to delete fee structure', 500);
        }
        
    } elseif ($type === 'payment') {
        // Delete payment
        try {
            $stmt = $pdo->prepare("SELECT fp.id FROM fee_payments fp
                                   JOIN students s ON fp.student_id = s.id
                                   WHERE fp.id = ? AND s.school_id = ?");
            $stmt->execute([$id, $school_id]);
            if (!$stmt->fetch()) {
                error_response('Payment not found', 404);
            }
            
            $stmt = $pdo->prepare("DELETE FROM fee_payments WHERE id = ?");
            $stmt->execute([$id]);
            
            log_security_event('PAYMENT_DELETED', "Payment deleted: ID $id (School ID: $school_id)");
            
            success_response([], 'Payment deleted successfully');
            
        } catch (PDOException $e) {
            error_log("Failed to delete payment: " . $e->getMessage());
            error_response('Failed to delete payment', 500);
        }
        
    } else {
        error_response('Invalid type parameter', 400);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
